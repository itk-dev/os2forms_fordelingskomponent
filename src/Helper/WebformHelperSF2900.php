<?php

namespace Drupal\os2forms_fordelingskomponent\Helper;

use Drupal\advancedqueue\Entity\QueueInterface;
use Drupal\advancedqueue\Job;
use Drupal\advancedqueue\JobResult;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Render\ElementInfoManager;
use Drupal\Core\State\StateInterface;
use Drupal\os2forms_fordelingskomponent\Exception\InvalidAttachmentElementException;
use Drupal\os2forms_fordelingskomponent\Exception\RuntimeException;
use Drupal\os2forms_fordelingskomponent\Exception\SubmissionNotFoundException;
use Drupal\os2forms_fordelingskomponent\Model\Attachment;
use Drupal\os2forms_fordelingskomponent\Model\DistributionFormular;
use Drupal\os2forms_fordelingskomponent\Model\XmlRenderResult;
use Drupal\os2forms_fordelingskomponent\Plugin\AdvancedQueue\JobType\FordelingskomponentSF2900;
use Drupal\os2forms_fordelingskomponent\Plugin\WebformHandler\WebformHandlerSF2900;
use Drupal\os2forms_fordelingskomponent\Repository\AnvenderForsendelseRepository;
use Drupal\os2forms_fordelingskomponent\Settings;
use Drupal\os2forms_fordelingskomponent\Settings\DistributionObjectSettings;
use Drupal\os2forms_fordelingskomponent\Settings\HandlerSettings;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\WebformSubmissionStorageInterface;
use Drupal\webform\WebformTokenManagerInterface;
use Drupal\webform_attachment\Element\WebformAttachmentBase;
use ItkDev\Serviceplatformen\SF2900\StructType\DistributionDokumentType;
use ItkDev\Serviceplatformen\SF2900\StructType\DistributionFormularType;
use ItkDev\Serviceplatformen\SF2900\StructType\DistributionJournalPostType;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Webform helper.
 */
final class WebformHelperSF2900 implements LoggerInterface {
  use LoggerTrait;

  /**
   * The webform submission storage.
   *
   * @var \Drupal\webform\WebformSubmissionStorageInterface
   */
  protected WebformSubmissionStorageInterface $webformSubmissionStorage;

  /**
   * The queue storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $queueStorage;

  /**
   * Constructor.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    private readonly Settings $settings,
    #[Autowire(service: 'plugin.manager.element_info')]
    private readonly ElementInfoManager $elementInfoManager,
    private readonly FordelingskomponentHelper $helper,
    private readonly AnvenderForsendelseRepository $anvenderForsendelseRepository,
    #[Autowire(service: 'webform.token_manager')]
    private readonly WebformTokenManagerInterface $webformTokenManager,
    private readonly StateInterface $state,
    #[Autowire(service: 'logger.channel.os2forms_fordelingskomponent')]
    private readonly LoggerChannelInterface $logger,
    #[Autowire(service: 'logger.channel.os2forms_fordelingskomponent_submission')]
    private readonly LoggerChannelInterface $submissionLogger,
  ) {
    $this->webformSubmissionStorage = $entityTypeManager->getStorage('webform_submission');
    $this->queueStorage = $entityTypeManager->getStorage('advancedqueue_queue');
  }

  /**
   * Build distribution object.
   */
  public function buildDistributionObject(HandlerSettings $handlerSettings, WebformSubmissionInterface $submission, ?Attachment $attachment): DistributionFormularType|DistributionDokumentType|DistributionJournalPostType {
    $handlerSettings = $this->replaceTokens($handlerSettings, $submission);

    return $this->helper->buildDistributionObject(
      $submission,
      $handlerSettings,
      $attachment,
    );
  }

  /**
   * Render XML.
   */
  public function renderXml(HandlerSettings $handlerSettings, WebformSubmissionInterface $submission, bool $validateXml = TRUE): XmlRenderResult {
    $files = $this->helper->buildFileGroups($handlerSettings, $submission);

    return $this->helper->renderXml($handlerSettings, $submission, files: $files, validateXml: $validateXml);
  }

  /**
   * Get main document.
   *
   * @see WebformAttachmentController::download()
   */
  protected function getAttachment(WebformSubmissionInterface $submission, HandlerSettings $handlerSettings): ?Attachment {
    if (!in_array($handlerSettings->distributionObject->distributionType, [
      DistributionObjectSettings::DISTRIBUTION_TYPE_DOKUMENT,
      DistributionObjectSettings::DISTRIBUTION_TYPE_FORMULAR,
    ])) {
      return NULL;
    }

    // Lifted from Drupal\webform_attachment\Controller\WebformAttachmentController::download.
    $element = $handlerSettings->distributionObject->attachmentElement;
    $element = $submission->getWebform()->getElement($element) ?: [];
    if (!isset($element['#type'])) {
      throw new InvalidAttachmentElementException(sprintf('Cannot get attachment element %s', $handlerSettings->distributionObject->attachmentElement));
    }
    [$type] = explode(':', $element['#type']);
    $instance = $this->elementInfoManager->createInstance($type);

    if (!$instance instanceof WebformAttachmentBase) {
      throw new InvalidAttachmentElementException(sprintf('Attachment element must be an instance of %s. Found %s.', WebformAttachmentBase::class, $instance::class));
    }

    $fileName = $instance::getFileName($element, $submission);
    $mimeType = $instance::getFileMimeType($element, $submission);
    $content = $instance::getFileContent($element, $submission);

    return new Attachment(
      $content,
      $mimeType,
      $fileName
    );
  }

  /**
   * Load webform submission by id.
   */
  public function loadSubmission(int $id): ?WebformSubmissionInterface {
    return $this->webformSubmissionStorage->load($id);
  }

  /**
   * Load submission IDs for a webform.
   */
  public function loadSubmissionIds(WebformInterface $webform): array {
    return $this->webformSubmissionStorage->getQuery()
      ->accessCheck()
      ->condition('webform_id', $webform->id())
      ->sort('created', 'DESC')
      ->sort('sid', 'DESC')
      ->execute();
  }

  /**
   * Load latest submission on a webform.
   */
  public function loadLatestSubmission(WebformInterface $webform): ?WebformSubmissionInterface {
    $submissionIds = $this->loadSubmissionIds($webform);

    $id = reset($submissionIds);

    return $id ? $this->loadSubmission($id) : NULL;
  }

  /**
   * Load queue.
   */
  private function loadQueue(): QueueInterface {
    $id = $this->settings->getGeneralSettings()->queue ?? NULL;

    /** @var ?\Drupal\advancedqueue\Entity\QueueInterface $queue */
    $queue = $this->queueStorage->load($id);

    if (NULL === $queue) {
      throw new RuntimeException('Cannot load queue %queue_id', ['%queue_id' => $id]);
    }

    return $queue;
  }

  /**
   * {@inheritdoc}
   *
   * @param mixed $level
   *   The level.
   * @param string $message
   *   The message.
   * @param array $context
   *   The context.
   *
   * @phpstan-param array<string, mixed> $context
   */
  public function log($level, $message, array $context = []): void {
    $this->logger->log($level, $message, $context);
    // @see https://www.drupal.org/node/3020595
    if (isset($context['webform_submission']) && $context['webform_submission'] instanceof WebformSubmissionInterface) {
      $this->submissionLogger->log($level, $message, $context);
    }
  }

  /**
   * Create a job.
   *
   * @see self::processJob()
   */
  public function createJob(WebformSubmissionInterface $webformSubmission, WebformHandlerSF2900|HandlerSettings $handlerSettings, ?array $payload = []): ?Job {
    $context = [
      'handler_id' => WebformHandlerSF2900::ID,
      'webform_submission' => $webformSubmission,
    ];

    try {
      if ($handlerSettings instanceof WebformHandlerSF2900) {
        $handlerSettings = $this->settings->getHandlerSettings($handlerSettings);
      }

      $job = Job::create(FordelingskomponentSF2900::class, $payload + [
        'formId' => $webformSubmission->getWebform()->id(),
        'submissionId' => $webformSubmission->id(),
        'handlerSettings' => $handlerSettings->toArray(),
      ]);
      $queue = $this->loadQueue();
      $queue->enqueueJob($job);
      $context['@queue'] = $queue->id();
      $this->notice('Job for afsend added to the queue @queue.', $context + [
        'operation' => 'Fordelingskomponent afsend queued',
      ]);

      return $job;
    }
    catch (\Exception $exception) {
      $this->error('Error creating job for afsend.', $context + [
        'operation' => 'Fordelingskomponent afsend failed',
        'exception' => $exception,
      ]);
      return NULL;
    }
  }

  /**
   * Process a job.
   *
   * @see self::createJob()
   */
  public function processJob(Job $job): JobResult {
    $payload = $job->getPayload();
    $context = [
      'handler_id' => WebformHandlerSF2900::ID,
      'operation' => 'fordelingskomponent afsend',
    ];
    try {
      $submissionId = $payload['submissionId'];
      $submission = $this->loadSubmission($submissionId);
      if (NULL === $submission) {
        $message = 'Cannot load submission @submissionId';
        $context = [
          '@submissionId' => $submissionId,
        ];
        $this->error($message, $context);

        throw new SubmissionNotFoundException(str_replace(array_keys($context), array_values($context),
          $message));
      }

      $context['webform_submission'] = $submission;
      $handlerSettings = new HandlerSettings($payload['handlerSettings']);

      $attachment = $this->getAttachment($submission, $handlerSettings);
      $distributionObject = $this->buildDistributionObject($handlerSettings, $submission, $attachment);

      $sftpRoutingRequired = $distributionObject instanceof DistributionFormular
        && !empty($distributionObject->getFileGroups());

      if (!$sftpRoutingRequired) {
        // No SFTP files uplead and awaiting delivery needed.
        $this->helper->sendDokument($submission, $distributionObject, $attachment, $handlerSettings);
        $this->notice('Fordelingskomponent afsendt', $context);
      }
      else {
        // Start a sequence of jobs:
        //
        // 1. Upload files and trigger files. When done, create a job to
        // 2. Check that all files have been delivered. Finally
        // 3. Send distribution object.
        $state = $this->getJobState($job);
        switch ($state) {
          case self::STATE_UPLOAD_FILES:
            $files = $this->helper->uploadFiles($distributionObject, $handlerSettings);
            $this->notice('Fordelingskomponent files uploaded', $context);
            $this->createJob($submission, $handlerSettings, [self::PAYLOAD_CHECK_FILES => $files]);
            break;

          case self::STATE_CHECK_FILES:
            $files = $payload[self::PAYLOAD_CHECK_FILES];
            if (!$this->helper->checkFilesDelivered($files)) {
              return JobResult::failure(sprintf('Files not yet delivered'));
            }
            else {
              $this->notice('Fordelingskomponent files delivered', $context);
              $this->createJob($submission, $handlerSettings, [self::PAYLOAD_FILES_DELIVERED => TRUE]);
            }
            break;

          case self::STATE_SEND_DISTRIBUTION_OBJECT:
            $this->helper->sendDokument($submission, $distributionObject, $attachment, $handlerSettings);
            $this->notice('Fordelingskomponent afsendt', $context);
            break;
        }
      }

      return JobResult::success();
    }
    catch (\Exception $e) {
      $this->error('Error: @message', $context + [
        '@message' => $e->getMessage(),
        'exception' => $e,
      ]);

      return JobResult::failure($e->getMessage());
    }
  }

  /**
   * Delete messages.
   *
   * @param \Drupal\os2forms_fordelingskomponent\Plugin\WebformHandler\WebformHandlerSF2900 $handler
   *   The handler.
   * @param \Drupal\webform\WebformSubmissionInterface[] $webform_submissions
   *   The webform submissions.
   */
  public function deleteMessages(WebformHandlerSF2900 $handler, array $webform_submissions) {
    $this->anvenderForsendelseRepository->deleteBySubmissions($webform_submissions);
    // @todo Clean up
  }

  /**
   * Replace tokens in handler settings supporting tokens.
   */
  private function replaceTokens(HandlerSettings $handlerSettings, WebformSubmissionInterface $submission): HandlerSettings {
    // @todo Should we clone the settings before making changes?
    $handlerSettings->distributionContext->titel = $this->webformTokenManager->replace((string) $handlerSettings->distributionContext->titel, $submission);
    $handlerSettings->distributionContext->beskrivelse = $this->webformTokenManager->replace((string) $handlerSettings->distributionContext->beskrivelse, $submission);
    $handlerSettings->distributionContext->brugervendtNoegle = $this->webformTokenManager->replace((string) $handlerSettings->distributionContext->brugervendtNoegle, $submission);

    $handlerSettings->distributionObject->journalpostMessage = $this->webformTokenManager->replace((string) $handlerSettings->distributionObject->journalpostMessage, $submission);

    return $handlerSettings;
  }

  private const string STATE_UPLOAD_FILES = 'upload_files';

  private const string PAYLOAD_CHECK_FILES = 'check_files';
  private const string STATE_CHECK_FILES = 'check_files';

  private const string PAYLOAD_FILES_DELIVERED = 'files_delivered';
  private const string STATE_SEND_DISTRIBUTION_OBJECT = 'send_distribution_object';

  /**
   * Get state for a job.
   *
   * This is only used when we must upload files and hence the first state (and
   * the default) is "upload files".
   */
  private function getJobState(Job $job): string {
    $payload = $job->getPayload();

    if (isset($payload[self::PAYLOAD_FILES_DELIVERED])) {
      return self::STATE_SEND_DISTRIBUTION_OBJECT;
    }
    if (isset($payload[self::PAYLOAD_CHECK_FILES])) {
      return self::STATE_CHECK_FILES;
    }

    return self::STATE_UPLOAD_FILES;
  }

}

<?php

namespace Drupal\os2forms_fordelingskomponent\Helper;

use Drupal\advancedqueue\Entity\QueueInterface;
use Drupal\advancedqueue\Job;
use Drupal\advancedqueue\JobResult;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Render\ElementInfoManager;
use Drupal\os2forms_fordelingskomponent\Exception\InvalidAttachmentElementException;
use Drupal\os2forms_fordelingskomponent\Exception\SubmissionNotFoundException;
use Drupal\os2forms_fordelingskomponent\Model\Attachment;
use Drupal\os2forms_fordelingskomponent\Model\XmlRenderResult;
use Drupal\os2forms_fordelingskomponent\Plugin\AdvancedQueue\JobType\FordelingskomponentSF2900;
use Drupal\os2forms_fordelingskomponent\Plugin\WebformHandler\WebformHandlerSF2900;
use Drupal\os2forms_fordelingskomponent\Settings;
use Drupal\os2forms_fordelingskomponent\Settings\DistributionObjectSettings;
use Drupal\os2forms_fordelingskomponent\Settings\HandlerSettings;
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
    #[Autowire(service: 'webform.token_manager')]
    private readonly WebformTokenManagerInterface $webformTokenManager,
    #[Autowire(service: 'logger.channel.os2forms_fordelingskomponent')]
    private readonly LoggerChannelInterface $logger,
    #[Autowire(service: 'logger.channel.os2forms_fordelingskomponent_submission')]
    private readonly LoggerChannelInterface $submissionLogger,
  ) {
    $this->webformSubmissionStorage = $entityTypeManager->getStorage('webform_submission');
    $this->queueStorage = $entityTypeManager->getStorage('advancedqueue_queue');
  }

  /**
   *
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
   *
   */
  public function renderXml(HandlerSettings $handlerSettings, WebformSubmissionInterface $submission, bool $validateXml = TRUE): XmlRenderResult {
    return $this->helper->renderXml($handlerSettings, $submission, validateXml: $validateXml);
  }

  /**
   * Afsend med Fordelingskomponenten.
   *
   * @return array
   *   [The response, The kombi post message].
   */
  public function afsend(WebformSubmissionInterface $submission, HandlerSettings $handlerSettings): array {
    $attachment = $this->getAttachment($submission, $handlerSettings);
    $distributionObject = $this->buildDistributionObject($handlerSettings, $submission, $attachment);

    return $this->helper->sendDokument(
      $submission,
      $distributionObject,
      $attachment,
      $handlerSettings,
    );
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
      throw new InvalidAttachmentElementException(sprintf('Attachment element must be an instance of %s. Found %s.', WebformAttachmentBase::class, get_class($instance)));
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
   * Load queue.
   */
  private function loadQueue(): ?QueueInterface {
    $id = $this->settings->getGeneralSettings()->queue;

    /** @var ?\Drupal\advancedqueue\Entity\QueueInterface $queue */
    $queue = $this->queueStorage->load($id ?? NULL);

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
  public function createJob(WebformSubmissionInterface $webformSubmission, WebformHandlerSF2900 $handler): ?Job {
    $context = [
      'handler_id' => WebformHandlerSF2900::ID,
      'webform_submission' => $webformSubmission,
    ];

    try {
      $job = Job::create(FordelingskomponentSF2900::class, [
        'formId' => $webformSubmission->getWebform()->id(),
        'submissionId' => $webformSubmission->id(),
        'handlerSettings' => $this->settings->getHandlerSettings($handler)->toArray(),
      ]);
      $queue = $this->loadQueue();
      if (NULL !== $queue) {
        $queue->enqueueJob($job);
        $context['@queue'] = $queue->id();
        $this->notice('Job for afsend added to the queue @queue.', $context + [
          'operation' => 'Fordelingskomponent afsend queued',
        ]);
      }
      else {
        $this->processJob($job);
      }

      return $job;
    }
    catch (\Exception $exception) {
      $this->error('Error creating job for afsend.', $context + [
        'operation' => 'Fordelingskomponent afsend failed',
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
      $this->afsend($submission, $handlerSettings);

      $this->notice('Fordelingskomponent afsendt', $context);

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
   */
  public function deleteMessages(array $array) {
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

}

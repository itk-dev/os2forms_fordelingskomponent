<?php

namespace Drupal\os2forms_fordelingskomponent\Helper;

use Drupal\advancedqueue\Entity\QueueInterface;
use Drupal\advancedqueue\Job;
use Drupal\advancedqueue\JobResult;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\os2forms_fordelingskomponent\Exception\InvalidAttachmentElementException;
use Drupal\os2forms_fordelingskomponent\Exception\SubmissionNotFoundException;
use Drupal\os2forms_fordelingskomponent\Form\SettingsForm;
use Drupal\os2forms_fordelingskomponent\Model\Attachment;
use Drupal\os2forms_fordelingskomponent\Plugin\AdvancedQueue\JobType\FordelingskomponentSF2900;
use Drupal\os2forms_fordelingskomponent\Plugin\WebformHandler\WebformHandlerSF2900;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\WebformSubmissionStorageInterface;
use Drupal\webform\WebformTokenManagerInterface;
use Drupal\webform_attachment\Element\WebformAttachmentBase;
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
    #[Autowire(service: 'plugin.manager.element_info')]
    private readonly ElementInfoManagerInterface $elementInfoManager,
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
   * Afsend med Fordelingskomponenten.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $submission
   *   The submission.
   * @param array $handlerSettings
   *   The Handler settings.
   * @param array $submissionData
   *   Submission data. Only for overriding during testing and development.
   *
   * @return array
   *   [The response, The kombi post message].
   *
   * @phpstan-param array<string, mixed> $handlerSettings
   * @phpstan-param array<string, mixed> $submissionData
   */
  public function afsend(WebformSubmissionInterface $submission, array $handlerSettings, array $submissionData = []): array {
    $submissionData = $submissionData + $submission->getData();
    $configuration = $this->helper->getHandlerConfiguration($handlerSettings);
    $attachment = $this->getAttachment($submission, $handlerSettings);

    $titel = $this->replaceTokens($configuration[FordelingskomponentHelper::TITEL] ?? '', $submission);
    $beskrivelse = $this->replaceTokens($configuration[FordelingskomponentHelper::BESKRIVELSE] ?? '', $submission);
    $brugervendtNoegle = $this->replaceTokens($configuration[FordelingskomponentHelper::BRUGERVENDT_NOEGLE] ?? '', $submission);

    return $this->helper->sendDokument(
      $submission,
      $attachment, $configuration,
      titel: $titel,
      beskrivelse: $beskrivelse,
      brugervendtNoegle: $brugervendtNoegle,
    );
  }

  /**
   * Get main document.
   *
   * @see WebformAttachmentController::download()
   *
   * @phpstan-param array<string, mixed> $handlerSettings
   */
  protected function getAttachment(WebformSubmissionInterface $submission, array $handlerSettings): Attachment {
    // Lifted from Drupal\webform_attachment\Controller\WebformAttachmentController::download.
    $element = $handlerSettings[WebformHandlerSF2900::ATTACHMENT_ELEMENT];
    $element = $submission->getWebform()->getElement($element) ?: [];
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
    $processingSettings = $this->helper->getModuleConfig()->get(SettingsForm::SECTION_PROCESSING);

    /** @var \Drupal\advancedqueue\Entity\QueueInterface $queue */
    $queue = $this->queueStorage->load($processingSettings['queue'] ?? NULL);

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
   *
   * @phpstan-param array<string, mixed> $handlerConfiguration
   */
  public function createJob(WebformSubmissionInterface $webformSubmission, array $handlerConfiguration): ?Job {
    $context = [
      'handler_id' => WebformHandlerSF2900::ID,
      'webform_submission' => $webformSubmission,
    ];

    try {
      $job = Job::create(FordelingskomponentSF2900::class, [
        'formId' => $webformSubmission->getWebform()->id(),
        'submissionId' => $webformSubmission->id(),
        'handlerConfiguration' => $handlerConfiguration,
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
      $this->error('Error creating job for afsen.', $context + [
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
      $this->afsend($submission, $payload['handlerConfiguration']);

      $this->notice('Fordelingskomponent afsendt', $context);

      return JobResult::success();
    }
    catch (\Exception $e) {
      $this->error('Error: @message', $context + [
        '@message' => $e->getMessage(),
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
   * Replace tokens.
   */
  private function replaceTokens(string $text, WebformSubmissionInterface $submission): string {
    return $this->webformTokenManager->replace($text, $submission);
  }

}

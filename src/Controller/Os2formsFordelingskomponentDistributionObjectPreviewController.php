<?php

declare(strict_types=1);

namespace Drupal\os2forms_fordelingskomponent\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\os2forms_fordelingskomponent\Helper\WebformHelperSF2900;
use Drupal\os2forms_fordelingskomponent\Hook\ThemeHooks;
use Drupal\os2forms_fordelingskomponent\Model\Attachment;
use Drupal\os2forms_fordelingskomponent\Plugin\WebformHandler\WebformHandlerSF2900;
use Drupal\os2forms_fordelingskomponent\Settings;
use Drupal\os2forms_fordelingskomponent\Settings\HandlerSettings;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\WebformSubmissionStorageInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for Fordelingskomponent routes.
 */
final class Os2formsFordelingskomponentDistributionObjectPreviewController extends ControllerBase {

  /**
   * The webform submission storage.
   */
  private readonly WebformSubmissionStorageInterface $submissionStorage;

  public function __construct(
    private readonly Settings $settings,
    private readonly WebformHelperSF2900 $helper,
    EntityTypeManagerInterface $entityTypeManager,
  ) {
    $this->submissionStorage = $entityTypeManager->getStorage('webform_submission');
  }

  /**
   * Builds the response.
   */
  public function __invoke(Request $request, WebformInterface $webform, string $webform_handler, ?WebformSubmissionInterface $webform_submission): array {
    try {
      $handler = $webform->getHandler($webform_handler);
    }
    catch (\Exception) {
      $handler = NULL;
    }

    if (!$handler instanceof WebformHandlerSF2900) {
      throw new NotFoundHttpException();
    }

    $handlerSettings = $this->settings->getHandlerSettings($handler);

    // Get previous, self and next submission IDs.
    $submissionIds = array_keys($this->submissionStorage->getQuery()
      ->accessCheck()
      ->condition('webform_id', $webform->id())
      ->sort('created', 'DESC')
      ->execute());
    $currentSubmissionId = $webform_submission?->id();
    $index = array_search($currentSubmissionId, $submissionIds);
    if (FALSE === $index) {
      $currentSubmissionId = reset($submissionIds) ?: NULL;
      $index = array_search($currentSubmissionId, $submissionIds);
    }
    if ($currentSubmissionId) {
      $webform_submission = $this->submissionStorage->load($currentSubmissionId);
    }

    $routeName = $request->attributes->get('_route');
    $links = array_map(
      static fn($submission) => Url::fromRoute($routeName, [
        'webform' => $webform->id(),
        'webform_handler' => $handler->getHandlerId(),
        'webform_submission' => $submission,
      ]),
      array_filter([
        'prev' => $submissionIds[$index + 1] ?? NULL,
        'self' => $currentSubmissionId,
        'next' => $submissionIds[$index - 1] ?? NULL,
      ])
    );

    return [
      '#theme' => ThemeHooks::DISTRIBUTION_OBJECT_PREVIEW,
      '#webform' => $webform,
      '#submission' => $webform_submission,
      '#handler' => $handler,
      '#handler_settings' => $handlerSettings,
      '#preview' => $webform_submission ? $this->renderPreview($handler, $handlerSettings, $webform_submission) : NULL,
      '#links' => $links,
    ];
  }

  /**
   * Render preview of distribution object.
   */
  public function renderPreview(WebformHandlerSF2900 $handler, HandlerSettings $handlerSettings, WebformSubmissionInterface $submission): array {
    $exceptions = [];
    $warnings = [];

    $distributionObject = NULL;
    $xml = [];
    try {
      $attachment = new Attachment('preview', Attachment::MIME_TYPE_PDF, 'preview.pdf');
      $distributionObject = $this->helper->buildDistributionObject($handlerSettings, $submission, $attachment);
    }
    catch (\Exception $exception) {
      $exceptions[] = $exception;
    }

    try {
      $xml = $this->helper->renderXml($handlerSettings, $submission, validateXml: FALSE);
    }
    catch (\Throwable) {
      // Silently ignore any errors.
    }

    return [
      'exceptions' => $exceptions,
      'warnings' => $warnings,
      'distribution_object' => $distributionObject,
      'xml' => $xml,
    ];
  }

}

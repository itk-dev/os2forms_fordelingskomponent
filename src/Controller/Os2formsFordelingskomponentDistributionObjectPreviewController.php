<?php

declare(strict_types=1);

namespace Drupal\os2forms_fordelingskomponent\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\os2forms_fordelingskomponent\Hook\ThemeHooks;
use Drupal\os2forms_fordelingskomponent\Plugin\WebformHandler\WebformHandlerSF2900;
use Drupal\os2forms_fordelingskomponent\Settings;
use Drupal\webform\WebformInterface;
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
    EntityTypeManagerInterface $entityTypeManager,
  ) {
    $this->submissionStorage = $entityTypeManager->getStorage('webform_submission');
  }

  /**
   * Builds the response.
   */
  public function __invoke(Request $request, WebformInterface $webform, string $webform_handler): array {
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
    $currentSubmissionId = (int) $request->query->get('submission');
    $index = array_search($currentSubmissionId, $submissionIds);
    if (FALSE === $index) {
      $currentSubmissionId = reset($submissionIds) ?: NULL;
      $index = array_search($currentSubmissionId, $submissionIds);
    }

    $routeName = $request->attributes->get('_route');
    $previewUrls = array_map(
      static fn($submission) => Url::fromRoute($routeName, [
        'webform' => $webform->id(),
        'webform_handler' => $handler->getHandlerId(),
        'submission' => $submission,
      ]),
      array_filter([
        'prev' => $submissionIds[$index + 1] ?? NULL,
        'self' => $currentSubmissionId,
        'next' => $submissionIds[$index - 1] ?? NULL,
      ])
    );

    $renderUrl = NULL !== $currentSubmissionId
      ? Url::fromRoute('os2forms_fordelingskomponent.fordelingskomponent_distribution_object.preview_render', [
        'webform' => $webform->id(),
        'webform_handler' => $handler->getHandlerId(),
        'submission' => $currentSubmissionId,
      ])
      : NULL;

    return [
      '#theme' => ThemeHooks::DISTRIBUTION_OBJECT_PREVIEW,
      '#webform' => $webform,
      '#handler' => $handler,
      '#handler_settings' => $handlerSettings,
      '#render_url' => $renderUrl,
      '#preview_urls' => $previewUrls,
    ];
  }

}

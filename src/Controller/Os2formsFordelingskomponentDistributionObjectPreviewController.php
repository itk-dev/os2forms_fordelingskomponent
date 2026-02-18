<?php

declare(strict_types=1);

namespace Drupal\os2forms_fordelingskomponent\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionStorageInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Returns responses for Fordelingskomponent routes.
 */
final class Os2formsFordelingskomponentDistributionObjectPreviewController extends ControllerBase {

  /**
   * The webform submission storage.
   */
  private WebformSubmissionStorageInterface $submissionStorage;

  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
  ) {
    $this->submissionStorage = $entityTypeManager->getStorage('webform_submission');
  }

  /**
   * Builds the response.
   */
  public function __invoke(Request $request, WebformInterface $webform, string $webform_handler): array|Response {
    $handler = $webform->getHandler($webform_handler);

    // Get previous, self and next submission IDs.
    $submissionIds = array_keys($this->submissionStorage->getQuery()
      ->accessCheck()
      ->condition('webform_id', $webform->id())
      ->sort('created', 'DESC')
      ->execute());
    $currentSubmission = (int) $request->query->get('submission');
    $index = array_search($currentSubmission, $submissionIds);
    if (FALSE === $index) {
      $currentSubmission = reset($submissionIds) ?: NULL;
      $index = array_search($currentSubmission, $submissionIds);
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
        'self' => $currentSubmission,
        'next' => $submissionIds[$index - 1] ?? NULL,
      ])
    );

    $renderUrl = NULL !== $currentSubmission
      ? Url::fromRoute('os2forms_fordelingskomponent.fordelingskomponent_distribution_object.preview_render', [
        'webform' => $webform->id(),
        'webform_handler' => $handler->getHandlerId(),
        'submission' => $currentSubmission,
      ])
      : NULL;

    return [
      '#theme' => 'os2forms_fordelingskomponent_distribution_object_preview',
      '#webform' => $webform,
      '#handler' => $handler,
      '#submission' => $currentSubmission,
      '#return_url' => $webform->toUrl('handlers'),
      '#render_url' => $renderUrl,
      '#preview_urls' => $previewUrls,
    ];

  }

}

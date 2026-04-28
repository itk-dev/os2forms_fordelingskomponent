<?php

declare(strict_types=1);

namespace Drupal\os2forms_fordelingskomponent_debug\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\os2forms_fordelingskomponent\Repository\AnvenderForsendelseRepository;
use Drupal\os2forms_fordelingskomponent_debug\Hook\ThemeHooks;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Returns responses for os2forms_fordelingskomponent_debug routes.
 */
final class Os2formsFordelingskomponentDebugForsendelseController extends ControllerBase {

  public function __construct(
    private readonly AnvenderForsendelseRepository $repository,
  ) {
  }

  /**
   * Builds the response.
   */
  public function __invoke(WebformInterface $webform, WebformSubmissionInterface $webform_submission): array {
    $items = $this->repository->loadBySubmission($webform_submission);

    return [
      '#theme' => ThemeHooks::FORSENDELSER,
      '#items' => $items,
      '#webform' => $webform,
      '#webform_submission' => $webform_submission,
    ];
  }

}

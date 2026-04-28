<?php

declare(strict_types=1);

namespace Drupal\os2forms_fordelingskomponent_debug\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\os2forms_fordelingskomponent\Repository\AnvenderKvitteringRepository;
use Drupal\os2forms_fordelingskomponent_debug\Hook\ThemeHooks;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Returns responses for os2forms_fordelingskomponent_debug routes.
 */
final class Os2formsFordelingskomponentDebugKvitteringController extends ControllerBase {

  public function __construct(
    private readonly AnvenderKvitteringRepository $repository,
  ) {
  }

  /**
   * Builds the response.
   */
  public function __invoke(WebformInterface $webform, WebformSubmissionInterface $webform_submission, string $anvender_transaktions_id): array {
    $items = $this->repository->loadByAnvenderTransaktionsId($anvender_transaktions_id);

    return [
      '#theme' => ThemeHooks::KVITTERINGER,
      '#items' => $items,
    ];
  }

}

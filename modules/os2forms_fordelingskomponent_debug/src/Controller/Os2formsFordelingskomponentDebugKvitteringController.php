<?php

declare(strict_types=1);

namespace Drupal\os2forms_fordelingskomponent_debug\Controller;

use Drupal\os2forms_fordelingskomponent\Repository\AnvenderKvitteringRepository;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for os2forms_fordelingskomponent_debug routes.
 */
final class Os2formsFordelingskomponentDebugKvitteringController extends AbstractController {

  public function __construct(
    private readonly AnvenderKvitteringRepository $repository,
  ) {
  }

  /**
   * Builds the response.
   */
  public function __invoke(WebformInterface $webform, WebformSubmissionInterface $webform_submission, string $anvender_transaktions_id): array {
    $item = $this->repository->loadByAnvenderTransaktionsId($anvender_transaktions_id);

    if (NULL === $item) {
      throw new NotFoundHttpException();
    }

    return [
      [
        '#type' => 'item',
        '#title' => $this->t('anvenderTransaktionsId'),
        '#markup' => $item->anvenderTransaktionsId,
      ],
      [
        '#type' => 'item',
        '#title' => $this->t('distributionTransaktionsId'),
        '#markup' => $item->distributionTransaktionsId,
      ],
      [
        '#type' => 'item',
        '#title' => $this->t('Created at'),
        '#markup' => $this->formatDatetime($item->createdAt),
      ],
      [
        '#type' => 'item',
        '#title' => $this->t('Updated at'),
        '#markup' => $this->formatDatetime($item->updatedAt),
      ],
      [
        '#type' => 'item',
        '#title' => $this->t('Request'),
        '#markup' => $this->renderYaml($item->request),
      ],
    ];
  }

}

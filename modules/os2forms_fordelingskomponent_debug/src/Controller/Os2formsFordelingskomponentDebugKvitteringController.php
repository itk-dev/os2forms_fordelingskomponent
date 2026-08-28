<?php

declare(strict_types=1);

namespace Drupal\os2forms_fordelingskomponent_debug\Controller;

use Drupal\Core\Url;
use Drupal\os2forms_fordelingskomponent\Repository\AnvenderKvitteringRepository;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\HttpFoundation\Request;
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
  public function __invoke(Request $request, WebformInterface $webform, WebformSubmissionInterface $webform_submission, string $anvender_transaktions_id): array {
    if ($id = (int) $request->query->get('id')) {
      return $this->itemDetails($id);
    }

    $items = $this->repository->loadByAnvenderTransaktionsId($anvender_transaktions_id);

    // https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Render%21Element%21Table.php/class/Table/10
    $header = [
      'id' => $this->t('Kvittering'),
      'anvenderTransaktionsId' => $this->t('Forsendelse'),
      'distributionTransaktionsId' => $this->t('distributionTransaktionsId'),
      'createdAt' => $this->t('Created at'),
      'updatedAt' => $this->t('Updated at'),
    ];
    $rows = [];
    foreach ($items as $item) {
      $receipt = $item->request->getForretningskvittering();
      $rows[] = [
        'id' => [
          'data' => [
            '#title' => sprintf('%s: %s', $receipt->getKvitteringstype(), $receipt->getForretningsValideringsKode()),
            '#type' => 'link',
            '#url' => Url::fromRoute('os2forms_fordelingskomponent_debug.os2forms_fordelingskomponent_kvittering',
              [
                'webform' => $webform->id(),
                'webform_submission' => $webform_submission->id(),
                'anvender_transaktions_id' => $item->anvenderTransaktionsId,
                'id' => $item->id,
              ]),
          ],
        ],
        'anvenderTransaktionsId' => [
          'data' => [
            '#title' => $item->anvenderTransaktionsId,
            '#type' => 'link',
            '#url' => Url::fromRoute('os2forms_fordelingskomponent_debug.os2forms_fordelingskomponent_forsendelse',
              [
                'webform' => $webform->id(),
                'webform_submission' => $webform_submission->id(),
                'anvender_transaktions_id' => $item->anvenderTransaktionsId,
              ]),
          ],
        ],
        'distributionTransaktionsId' => [
          'data' => [
            '#markup' => $item->distributionTransaktionsId,
          ],
        ],
        'createdAt' => [
          'data' => [
            '#markup' => $this->formatDatetime($item->createdAt),
          ],
        ],
        'updatedAt' => [
          'data' => [
            '#markup' => $this->formatDatetime($item->updatedAt),
          ],
        ],
      ];
    }

    return [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No entries available.'),
    ];
  }

  /**
   * Build item details.
   */
  private function itemDetails(int $id) {
    $item = $this->repository->load($id);

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

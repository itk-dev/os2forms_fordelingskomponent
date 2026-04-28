<?php

declare(strict_types=1);

namespace Drupal\os2forms_fordelingskomponent_debug\Controller;

use Drupal\os2forms_fordelingskomponent\Model\Fordelingskomponent\AnvenderForsendelse;
use Drupal\Core\Url;
use Drupal\os2forms_fordelingskomponent\Repository\AnvenderForsendelseRepository;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for os2forms_fordelingskomponent_debug routes.
 */
final class Os2formsFordelingskomponentDebugForsendelseController extends AbstractController {

  public function __construct(
    private readonly AnvenderForsendelseRepository $repository,
  ) {
  }

  /**
   * Builds the response.
   */
  public function __invoke(Request $request, WebformInterface $webform, WebformSubmissionInterface $webform_submission): array {
    if ($anvenderTransaktionsId = $request->query->get('anvenderTransaktionsId')) {
      if ($item = $this->repository->loadByAnvenderTransaktionsId($anvenderTransaktionsId)) {
        return $this->itemDetails($item);
      }

      throw new NotFoundHttpException();
    }
    $items = $this->repository->loadBySubmission($webform_submission);

    // https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Render%21Element%21Table.php/class/Table/10
    $header = [
      'anvenderTransaktionsId' => $this->t('anvenderTransaktionsId'),
      'distributionTransaktionsId' => $this->t('distributionTransaktionsId'),
      'receipts' => $this->t('Receipts'),
      'webform handlers' => $this->t('Webform handlers'),
      'createdAt' => $this->t('Created at'),
      'updatedAt' => $this->t('Updated at'),
      'deliveredAt' => $this->t('Delivered at'),
    ];
    $rows = [];
    foreach ($items as $item) {
      $rows[] = [
        'anvenderTransaktionsId' => [
          'data' => [
            // https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Render%21Element%21Link.php/class/Link/10
            '#title' => $item->anvenderTransaktionsId,
            '#type' => 'link',
            '#url' => Url::fromRoute('<current>', [
              'anvenderTransaktionsId' => $item->anvenderTransaktionsId,
            ]),
          ],
        ],
        'distributionTransaktionsId' => [
          'data' => [
            '#markup' => $item->distributionTransaktionsId,
          ],
        ],
        'receipts' => [
          'data' => [
            '#title' => $this->t('Receipts'),
            '#type' => 'link',
            '#url' => Url::fromRoute('os2forms_fordelingskomponent_debug.os2forms_fordelingskomponent_debug_kvittering', [
              'webform' => $item->webformId,
              'webform_submission' => $item->webformSubmissionId,
              'anvender_transaktions_id' => $item->anvenderTransaktionsId,
            ]),
          ],
        ],
        'webform handlers' => [
          'data' => [
            '#title' => $this->t('Webform handlers'),
            '#type' => 'link',
            '#url' => Url::fromRoute('entity.webform.handlers', [
              'webform' => $item->webformId,
            ]),
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
        'deliveredAt' => [
          'data' => [
            '#markup' => $this->formatDatetime($item->deliveredAt),
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
  private function itemDetails(AnvenderForsendelse $item) {
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

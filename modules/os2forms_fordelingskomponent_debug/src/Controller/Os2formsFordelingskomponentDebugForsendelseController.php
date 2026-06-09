<?php

declare(strict_types=1);

namespace Drupal\os2forms_fordelingskomponent_debug\Controller;

use Drupal\Core\Url;
use Drupal\os2forms_fordelingskomponent\Repository\AnvenderForsendelseRepository;
use Drupal\os2forms_fordelingskomponent\Repository\AnvenderKvitteringRepository;
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
    private readonly AnvenderKvitteringRepository $kvitteringRepository,
  ) {
  }

  /**
   * Builds the response.
   */
  public function __invoke(Request $request, WebformInterface $webform, WebformSubmissionInterface $webform_submission): array {
    if ($anvenderTransaktionsId = $request->query->get('anvender_transaktions_id')) {
      return $this->itemDetails($anvenderTransaktionsId);
    }

    $items = $this->repository->loadBySubmission($webform_submission);

    // https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Render%21Element%21Table.php/class/Table/10
    $header = [
      'response' => $this->t('Response'),
      'anvenderTransaktionsId' => $this->t('anvenderTransaktionsId'),
      'distributionTransaktionsId' => $this->t('distributionTransaktionsId'),
      'receipts' => $this->t('Receipts'),
      'createdAt' => $this->t('Created at'),
      'updatedAt' => $this->t('Updated at'),
    ];
    $rows = [];
    foreach ($items as $item) {
      $receipt = $item->response->getForretningsKvittering();
      $receipts = $this->kvitteringRepository->loadByAnvenderTransaktionsId($item->anvenderTransaktionsId);
      $rows[] = [
        'response' => [
          'data' => [
            // https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Render%21Element%21Link.php/class/Link/10
            '#title' => sprintf('%s: %s', $receipt->getKvitteringstype(), $receipt->getForretningsValideringsKode()),
            '#type' => 'link',
            '#url' => Url::fromRoute('os2forms_fordelingskomponent_debug.os2forms_fordelingskomponent_forsendelse', [
              'webform' => $webform->id(),
              'webform_submission' => $webform_submission->id(),
              'anvender_transaktions_id' => $item->anvenderTransaktionsId,
            ]),
          ],
        ],

        'anvenderTransaktionsId' => [
          'data' => [
            // https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Render%21Element%21Link.php/class/Link/10
            '#title' => $item->anvenderTransaktionsId,
            '#type' => 'link',
            '#url' => Url::fromRoute('os2forms_fordelingskomponent_debug.os2forms_fordelingskomponent_forsendelse', [
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
        'receipts' => [
          'data' => [
            '#title' => count($receipts),
            '#type' => 'link',
            '#url' => Url::fromRoute('os2forms_fordelingskomponent_debug.os2forms_fordelingskomponent_kvittering', [
              'webform' => $webform->id(),
              'webform_submission' => $webform_submission->id(),
              'anvender_transaktions_id' => $item->anvenderTransaktionsId,
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
  private function itemDetails(string $anvenderTransaktionsId) {
    $item = $this->repository->loadByAnvenderTransaktionsId($anvenderTransaktionsId);
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
      [
        '#type' => 'item',
        '#title' => $this->t('Response'),
        '#markup' => $this->renderYaml($item->response),
      ],
    ];
  }

}

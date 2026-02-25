<?php

namespace Drupal\os2forms_fordelingskomponent\Model;

use Drupal\Core\Database\Connection;
use Drupal\os2forms_fordelingskomponent\Hook\InstallHooks;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Model for the os2forms_fordelingskomponent_anvender_forsendelse table.
 */
final class AnvenderForsendelseRepository {

  public function __construct(
    private readonly Connection $database,
  ) {
  }

  /**
   * Load forsendelser.
   *
   * @param array $criteria
   *   The criteria.
   *
   * @return AnvenderForsendelse[]
   *   The list of forsendelser.
   */
  public function loadBy(array $criteria = []): array {
    $query = $this->database
      ->select(InstallHooks::TABLE_ANVENDER_FORSENDELSE, 't')
      ->fields('t');

    foreach ($criteria as $field => $condition) {
      if (is_array($condition)) {
        $query->condition(...$condition);
      }
      else {
        $query->condition($field, $condition, '=');
      }
    }

    $statement = $query->execute();
    assert(NULL !== $statement);
    $result = $statement->fetchAll();
    return array_map(
      static fn(object $row) => new AnvenderForsendelse(
        webformHandlerId: $row->webform_handler_id,
        webformSubmissionId: $row->webform_submission_id,
        anvenderTransaktionsId: $row->anvender_transaktions_id,
        request: unserialize($row->request, options: ['allowed_classes' => TRUE]),
        distributionTransaktionsId: $row->distribution_transaktions_id,
        response: unserialize($row->response, options: ['allowed_classes' => TRUE]),
        createdAt: $row->created_at,
        updatedAt: $row->updated_at,
        deliveredAt: $row->delivered_at,
      ),
      array: $result
    );
  }

  /**
   * Load forsendelser for a submission.
   *
   * @return AnvenderForsendelse[]
   *   The result.
   */
  public function loadBySubmission(WebformSubmissionInterface $submission): array {
    return $this->loadBy(['webform_submission_id' => $submission->id()]);
  }

  /**
   * Load forsendelse by transaktions-id.
   */
  public function loadByAnvenderTransaktionsId(string $anvenderTransaktionsId): ?AnvenderForsendelse {
    $result = $this->loadBy(['anvender_transaktions_id' => $anvenderTransaktionsId]);

    if (1 !== count($result)) {
      return NULL;
    }

    return reset($result);
  }

  /**
   * Save forsendelse.
   */
  public function save(AnvenderForsendelse $forsendelse): string {
    $now = \Drupal::time()->getCurrentTime();
    $forsendelse->createdAt ??= $now;
    $forsendelse->updatedAt = $now;
    return $this->database
      // @todo Use upsert.
      ->insert(InstallHooks::TABLE_ANVENDER_FORSENDELSE)
      ->fields([
        'webform_handler_id' => $forsendelse->webformHandlerId,
        'webform_submission_id' => $forsendelse->webformSubmissionId,
        'anvender_transaktions_id' => $forsendelse->anvenderTransaktionsId,
        'request' => serialize($forsendelse->request),
        'distribution_transaktions_id' => $forsendelse->distributionTransaktionsId,
        'response' => serialize($forsendelse->response),
        'created_at' => $forsendelse->createdAt,
        'updated_at' => $forsendelse->updatedAt,
        'delivered_at' => NULL,
      ])
      ->execute();
  }

}

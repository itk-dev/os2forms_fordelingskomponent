<?php

namespace Drupal\os2forms_fordelingskomponent\Repository;

use Drupal\os2forms_fordelingskomponent\Hook\InstallHooks;
use Drupal\os2forms_fordelingskomponent\Model\Fordelingskomponent\AnvenderKvittering;

/**
 * Repository for AnvenderKvittering.
 */
final class AnvenderKvitteringRepository extends AbstractRepository {
  private const string TABLE = InstallHooks::TABLE_ANVENDER_KVITTERING;

  /**
   * Load kvitteringer.
   *
   * @param array $conditions
   *   The criteria.
   *
   * @return \Drupal\os2forms_fordelingskomponent\Model\Fordelingskomponent\AnvenderKvittering[]
   *   The list of kvittering.
   */
  private function loadBy(array $conditions = []): array {
    $query = $this->database
      ->select(self::TABLE, 't')
      ->fields('t');

    foreach ($conditions as $condition) {
      $query->condition(...$condition);
    }

    $statement = $query->execute();
    assert(NULL !== $statement);
    $result = $statement->fetchAll();
    return array_map(
      static fn(object $row) => new AnvenderKvittering(
        anvenderTransaktionsId: $row->anvender_transaktions_id,
        distributionTransaktionsId: $row->distribution_transaktions_id,
        request: unserialize($row->request, options: ['allowed_classes' => TRUE]),
        response: unserialize($row->response, options: ['allowed_classes' => TRUE]),
        createdAt: $row->created_at,
        updatedAt: $row->updated_at,
      ),
      array: $result
    );
  }

  /**
   * Load kvittering by transaktions-id.
   */
  public function loadByAnvenderTransaktionsId(string $anvenderTransaktionsId, ?string $distributionTransaktionsId = NULL): ?AnvenderKvittering {
    $criteria = [
      ['anvender_transaktions_id', $anvenderTransaktionsId],
    ];
    if (NULL !== $distributionTransaktionsId) {
      $criteria[] = ['distribution_transaktions_id', $distributionTransaktionsId];
    }
    $result = $this->loadBy($criteria);

    if (1 !== count($result)) {
      return NULL;
    }

    return reset($result);
  }

  /**
   * Save kvittering.
   */
  public function save(AnvenderKvittering $kvittering): bool {
    try {
      $now = $this->time->getRequestTime();
      $kvittering->createdAt ??= $now;
      $kvittering->updatedAt = $now;

      $fields = [
        'anvender_transaktions_id' => $kvittering->anvenderTransaktionsId,
        'request' => serialize($kvittering->request),
        'distribution_transaktions_id' => $kvittering->distributionTransaktionsId,
        'response' => serialize($kvittering->response),
        'created_at' => $kvittering->createdAt,
        'updated_at' => $kvittering->updatedAt,
      ];
      if (NULL === $this->loadByAnvenderTransaktionsId(
        anvenderTransaktionsId: $kvittering->anvenderTransaktionsId,
          distributionTransaktionsId: $kvittering->distributionTransaktionsId,
        )) {
        $this->database
          ->insert(self::TABLE)
          ->fields($fields)
          ->execute();
      }
      else {
        $this->database
          ->update(self::TABLE)
          ->condition('anvender_transaktions_id', $kvittering->anvenderTransaktionsId)
          ->condition('distribution_transaktions_id', $kvittering->distributionTransaktionsId)
          ->fields($fields)
          ->execute();
      }

      return TRUE;
    }
    catch (\Exception $exception) {
      $this->logger->error('Error saving kvittering: @message', [
        '@message' => $exception->getMessage(),
        'exception' => $exception,
      ]);
    }

    return FALSE;
  }

}

<?php

namespace Drupal\os2forms_fordelingskomponent\Repository;

use Drupal\os2forms_fordelingskomponent\Hook\InstallHooks;
use Drupal\os2forms_fordelingskomponent\Model\Fordelingskomponent\AnvenderForsendelse;
use Drupal\os2forms_fordelingskomponent\Plugin\WebformHandler\WebformHandlerSF2900;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Repository for AnvenderForsendelse.
 */
final class AnvenderForsendelseRepository extends AbstractRepository {
  private const string TABLE = InstallHooks::TABLE_ANVENDER_FORSENDELSE;

  /**
   * Load forsendelser.
   *
   * @param array $conditions
   *   The criteria.
   *
   * @return \Drupal\os2forms_fordelingskomponent\Model\Fordelingskomponent\AnvenderForsendelse[]
   *   The list of forsendelser.
   */
  private function loadBy(array $conditions = [], array $orderBy = ['created_at' => 'DESC']): array {
    $query = $this->database
      ->select(self::TABLE, 't')
      ->fields('t');

    foreach ($conditions as $condition) {
      $query->condition(...$condition);
    }

    foreach ($orderBy as $field => $direction) {
      $query->orderBy($field, $direction);
    }

    $statement = $query->execute();
    assert(NULL !== $statement);
    $result = $statement->fetchAll();
    return array_map(
      static fn(object $row) => new AnvenderForsendelse(
        webformId: $row->webform_id,
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
   * @return \Drupal\os2forms_fordelingskomponent\Model\Fordelingskomponent\AnvenderForsendelse[]
   *   The result.
   */
  public function loadBySubmission(WebformSubmissionInterface $submission): array {
    return $this->loadBy([
      ['webform_submission_id', $submission->id()],
    ]);
  }

  /**
   * Load by ...
   */
  public function loadByWebformAndHandler(WebformInterface $webform, WebformHandlerSF2900 $handler) {
    return $this->loadBy([
      ['webform_id', $webform->id()],
      ['webform_handler_id', $handler->gethandlerId()],
    ]);
  }

  /**
   * Delete by submission.
   *
   * @param \Drupal\webform\WebformSubmissionInterface[] $submissions
   *   The submissions.
   */
  public function deleteBySubmissions(array $submissions): int {
    // @todo Delete kvitteringer.
    if (empty($submissions)) {
      return 0;
    }

    try {
      $ids = array_map(static fn(WebformSubmissionInterface $submission) => $submission->id(), $submissions);
      return $this->database->delete(self::TABLE)
        ->condition('webform_submission_id', $ids, 'IN')
        ->execute();
    }
    catch (\Exception $exception) {
      $this->logger->error('Error deleting forsendelser for submissions @submissions: @message', [
        '@submission' => implode(', ', $ids),
        '@message' => $exception->getMessage(),
        'exception' => $exception,
      ]);
    }

    return 0;
  }

  /**
   * Load forsendelse by transaktions-id.
   */
  public function loadByAnvenderTransaktionsId(string $anvenderTransaktionsId, ?string $distributionTransaktionsId = NULL): ?AnvenderForsendelse {
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
   * Save forsendelse.
   */
  public function save(AnvenderForsendelse $forsendelse): bool {
    try {
      $now = $this->time->getRequestTime();
      $forsendelse->createdAt ??= $now;
      $forsendelse->updatedAt = $now;

      $fields = [
        'webform_id' => $forsendelse->webformId,
        'webform_handler_id' => $forsendelse->webformHandlerId,
        'webform_submission_id' => $forsendelse->webformSubmissionId,
        'anvender_transaktions_id' => $forsendelse->anvenderTransaktionsId,
        'request' => serialize($forsendelse->request),
        'distribution_transaktions_id' => $forsendelse->distributionTransaktionsId,
        'response' => serialize($forsendelse->response),
        'created_at' => $forsendelse->createdAt,
        'updated_at' => $forsendelse->updatedAt,
        'delivered_at' => $forsendelse->deliveredAt,
      ];
      if (NULL === $this->loadByAnvenderTransaktionsId($forsendelse->anvenderTransaktionsId)) {
        $this->database
          ->insert(self::TABLE)
          ->fields($fields)
          ->execute();
      }
      else {
        $this->database
          ->update(self::TABLE)
          ->condition('anvender_transaktions_id', $forsendelse->anvenderTransaktionsId)
          ->fields($fields)
          ->execute();
      }

      return TRUE;
    }
    catch (\Exception $exception) {
      $this->logger->error('Error saving forsendelse: @message', [
        '@message' => $exception->getMessage(),
        'exception' => $exception,
      ]);
    }

    return FALSE;
  }

}

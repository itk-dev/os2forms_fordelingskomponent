<?php

namespace Drupal\os2forms_fordelingskomponent\Repository;

use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\os2forms_fordelingskomponent\Hook\InstallHooks;
use Drupal\os2forms_fordelingskomponent\Model\Fordelingskomponent\AnvenderForsendelse;
use Drupal\os2forms_fordelingskomponent\Plugin\WebformHandler\WebformHandlerSF2900;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Repository for AnvenderForsendelse.
 */
final class AnvenderForsendelseRepository {

  public function __construct(
    private readonly Connection $database,
    #[Autowire(service: 'logger.channel.os2forms_fordelingskomponent')]
    private readonly LoggerChannelInterface $logger,
  ) {
  }

  /**
   * Load forsendelser.
   *
   * @param array $conditions
   *   The criteria.
   *
   * @return \Drupal\os2forms_fordelingskomponent\Model\Fordelingskomponent\AnvenderForsendelse[]
   *   The list of forsendelser.
   */
  private function loadBy(array $conditions = []): array {
    $query = $this->database
      ->select(InstallHooks::TABLE_ANVENDER_FORSENDELSE, 't')
      ->fields('t');

    foreach ($conditions as $condition) {
      $query->condition(...$condition);
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
    if (empty($submissions)) {
      return 0;
    }

    try {
      $ids = array_map(static fn(WebformSubmissionInterface $submission) => $submission->id(), $submissions);
      return $this->database->delete(InstallHooks::TABLE_ANVENDER_FORSENDELSE)
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
  }

  /**
   * Load forsendelse by transaktions-id.
   */
  public function loadByAnvenderTransaktionsId(string $anvenderTransaktionsId): ?AnvenderForsendelse {
    $result = $this->loadBy([
      ['anvender_transaktions_id', $anvenderTransaktionsId],
    ]);

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
      $now = \Drupal::time()->getCurrentTime();
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
          ->insert(InstallHooks::TABLE_ANVENDER_FORSENDELSE)
          ->fields($fields)
          ->execute();
      }
      else {
        $this->database
          ->update(InstallHooks::TABLE_ANVENDER_FORSENDELSE)
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

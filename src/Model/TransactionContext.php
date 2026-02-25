<?php

namespace Drupal\os2forms_fordelingskomponent\Model;

use Drupal\os2forms_fordelingskomponent\Settings\HandlerSettings;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Transaction context.
 */
final readonly class TransactionContext {

  /**
   * Constructor.
   */
  public function __construct(
    public string $transactionId,
    public HandlerSettings $handlerSettings,
    public WebformSubmissionInterface $submission,
  ) {
  }

}

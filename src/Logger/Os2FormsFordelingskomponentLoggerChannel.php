<?php

namespace Drupal\os2forms_fordelingskomponent\Logger;

use Drupal\Core\Logger\LoggerChannel as BaseLoggerChannel;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Site\Settings;

/**
 * Os2Forms fordelingskomponent logger channel.
 */
class Os2FormsFordelingskomponentLoggerChannel extends BaseLoggerChannel {

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function log($level, \Stringable|string $message, array $context = []): void {
    $rfcLogLevel = $this->levelTranslation[$level] ?? RfcLogLevel::ERROR;
    $logLevel = $this->getLogLevel();
    if ($logLevel >= $rfcLogLevel) {
      parent::log($level, $message, $context);
    }
  }

  /**
   * Get log level.
   */
  private function getLogLevel(): int {
    return (int) (Settings::get('os2forms_fordelingskomponent')['log_level'] ?? RfcLogLevel::ERROR);
  }

}

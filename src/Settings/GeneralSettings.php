<?php

namespace Drupal\os2forms_fordelingskomponent\Settings;

/**
 * General module settings.
 */
final class GeneralSettings extends AbstractSettings {
  const string NAME = 'general';

  const string TEST_MODE = 'test_mode';
  public bool $testMode = TRUE;

  const string QUEUE = 'queue';
  public ?string $queue = NULL;

}

<?php

namespace Drupal\os2forms_fordelingskomponent\Settings\SenderSettings;

use Drupal\os2forms_fordelingskomponent\Settings\AbstractSettings;

/**
 *
 */
final class SftpSettings extends AbstractSettings {
  const string NAME = 'sftp';

  const string USERNAME = 'username';
  public ?string $username = NULL;

  const string PRIVATE_KEY = 'private_key';
  public ?string $privateKey = NULL;

}

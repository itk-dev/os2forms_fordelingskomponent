<?php

namespace Drupal\os2forms_fordelingskomponent\Settings\DistributionObjectSettings;

use Drupal\os2forms_fordelingskomponent\Settings\AbstractSettings;

/**
 * Files settings.
 */
class FilesSettings extends AbstractSettings {
  const string NAME = 'files';

  public const string FILSPECIFIKATION = 'filspecifikation';
  public ?string $filspecifikation = '';

  public const string RECIPIENT_IT_SYSTEM_PATTERN = self::UUID_PATTERN;
  public const string RECIPIENT_IT_SYSTEM = 'recipient_it_system';
  public ?string $recipientItSystem = NULL;

  public const string RECIPIENT_IT_SYSTEM_LOOK_UP = 'recipient_it_system_look_up';
  public bool $recipientItSystemLookUp = TRUE;

  public const string RECIPIENT_AUTHORITY_PATTERN = self::CVR_PATTERN;
  public const string RECIPIENT_AUTHORITY = 'recipient_authority';
  public ?string $recipientAuthority = '';

}

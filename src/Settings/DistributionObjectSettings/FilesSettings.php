<?php

namespace Drupal\os2forms_fordelingskomponent\Settings\DistributionObjectSettings;

use Drupal\os2forms_fordelingskomponent\Settings\AbstractSettings;

class FilesSettings extends AbstractSettings
{
  const string NAME = 'files';

  public const string FILSPECIFIKATION = 'filspecifikation';
  public ?string $filspecifikation = '';

  public const string RECIPIENT_AUTHORITY = 'recipient_authority';
  public ?string $recipientAuthority = '';

}

<?php

namespace Drupal\os2forms_fordelingskomponent\Settings;

use Drupal\os2forms_fordelingskomponent\Settings\SenderSettings\SftpSettings;

/**
 * @see https://rimi-itk.github.io/digitaliseringskataloget.dk/digitaliseringskataloget.dk/sf2900/2.4/SF2900%20-%20Fordelingskomponent%20V2.4.pdf
 */
final class SenderSettings extends AbstractSettings {
  const string NAME = 'sender';

  protected static array $settingsProperties = [
    self::SFTP => SftpSettings::class,
  ];

  const string ROUTING_MYNDIGHED = 'routing_myndighed';
  public ?string $routingMyndighed = NULL;

  const string SENDER_ID = 'sender_id';
  public ?string $senderId = NULL;

  const string REGISTRERING_IT_SYSTEM = 'registrering_it_system';
  public ?string $registreringItSystem = NULL;

  const string CERTIFICATE = 'certificate';
  public ?string $certificate = NULL;

  const string SFTP = 'sftp';
  public ?SftpSettings $sftp = NULL;

  public function __construct(array $values, bool $throwExceptionOnMissingProperty = FALSE) {
    $this->sftp = new SftpSettings([]);
    parent::__construct($values, $throwExceptionOnMissingProperty);
  }

}

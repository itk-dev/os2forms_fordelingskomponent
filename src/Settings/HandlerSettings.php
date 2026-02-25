<?php

namespace Drupal\os2forms_fordelingskomponent\Settings;

/**
 * Webform handler settings.
 */
final class HandlerSettings extends AbstractSettings {
  protected static array $settingsProperties = [
    self::SENDER => SenderSettings::class,
    self::DISTRIBUTION_CONTEXT => DistributionContextSettings::class,
    self::DISTRIBUTION_OBJECT => DistributionObjectSettings::class,
  ];

  const string HANDLER_ID = 'handler_id';
  public string $handlerId;

  const string SENDER = 'sender';
  public ?SenderSettings $sender = NULL;

  const string DISTRIBUTION_CONTEXT = 'distribution_context';
  public ?DistributionContextSettings $distributionContext = NULL;

  const string DISTRIBUTION_OBJECT = 'distribution_object';
  public ?DistributionObjectSettings $distributionObject = NULL;

}

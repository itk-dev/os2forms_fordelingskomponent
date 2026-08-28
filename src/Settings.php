<?php

namespace Drupal\os2forms_fordelingskomponent;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\os2forms_fordelingskomponent\Plugin\WebformHandler\WebformHandlerSF2900;
use Drupal\os2forms_fordelingskomponent\Settings\DistributionContextSettings;
use Drupal\os2forms_fordelingskomponent\Settings\DistributionObjectSettings;
use Drupal\os2forms_fordelingskomponent\Settings\GeneralSettings;
use Drupal\os2forms_fordelingskomponent\Settings\SenderSettings;
use Drupal\os2forms_fordelingskomponent\Settings\HandlerSettings;

/**
 * Settings for module and handler.
 */
class Settings {
  const string CONFIG_NAME = 'os2forms_fordelingskomponent.settings';

  /**
   * The config.
   */
  private readonly ImmutableConfig $config;

  public function __construct(
    ConfigFactoryInterface $configFactory,
  ) {
    $this->config = $configFactory->get(self::CONFIG_NAME);
  }

  /**
   * Get general settings.
   */
  public function getGeneralSettings(): GeneralSettings {
    return new GeneralSettings($this->getValue(GeneralSettings::NAME));
  }

  /**
   * Get sender settings.
   */
  public function getSenderSettings(array $values = []): SenderSettings {
    return (new SenderSettings($this->getValue(SenderSettings::NAME)))
      ->apply($values);
  }

  /**
   * Get distribution context settings.
   */
  public function getDistributionContextSettings(array $values = []): DistributionContextSettings {
    return (new DistributionContextSettings($this->getValue(DistributionContextSettings::NAME)))
      ->apply($values);
  }

  /**
   * Get distribution object settings.
   */
  public function getDistributionObjectSettings(array $values = []): DistributionObjectSettings {
    return (new DistributionObjectSettings($this->getValue(DistributionObjectSettings::NAME)))
      ->apply($values);
  }

  /**
   * Get handler settings.
   *
   * The settings are the global settings with handler specific settings on top.
   */
  public function getHandlerSettings(WebformHandlerSF2900 $handler): HandlerSettings {
    $handlerSettings = $handler->getSettings();
    $settings = new HandlerSettings([
      HandlerSettings::HANDLER_ID => $handler->gethandlerId(),
      HandlerSettings::SENDER => $this->getSenderSettings($handlerSettings[SenderSettings::NAME] ?? []),
      HandlerSettings::DISTRIBUTION_CONTEXT => $this->getDistributionContextSettings($handlerSettings[DistributionContextSettings::NAME] ?? []),
      HandlerSettings::DISTRIBUTION_OBJECT => $this->getDistributionObjectSettings($handlerSettings[DistributionObjectSettings::NAME] ?? []),
    ]);

    return $settings;
  }

  /**
   * Get settings value.
   *
   * @return array<string, mixed>
   *   The settings values.
   */
  private function getValue(string $section): array {
    $values = $this->config->get($section);

    return is_array($values) ? $values : [];
  }

}

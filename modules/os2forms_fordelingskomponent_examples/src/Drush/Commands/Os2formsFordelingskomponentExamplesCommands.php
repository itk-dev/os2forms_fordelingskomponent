<?php

namespace Drupal\os2forms_fordelingskomponent_examples\Drush\Commands;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\Extension\ModuleExtensionList;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Drush command file for OS2Forms Fordelingskomponent examples.
 */
final class Os2formsFordelingskomponentExamplesCommands extends DrushCommands {
  use AutowireTrait;

  private const CONFIG_NAME_PATTERNS = [
    '/^webform\.webform\.os2forms_fdk_/',
    '/^webform\.webform\.o2f_fdk_/',
  ];

  /**
   * Constructs an Os2formsFordelingskomponentExamplesCommands object.
   */
  public function __construct(
    private readonly ConfigManagerInterface $configManager,
    #[Autowire(service: 'extension.list.module')]
    private readonly ModuleExtensionList $moduleExtensionList,
  ) {
    parent::__construct();
  }

  /**
   * The config keys to clear.
   */
  private static array $configKeysToClear = [
    'uuid',
    '_core',
    'third_party_settings.webform_revisions',
    'third_party_settings.os2forms_permissions_by_term',
  ];

  /**
   * Command description here.
   */
  #[CLI\Command(name: 'os2forms_fordelingskomponent_examples:export-examples')]
  public function commandName() {
    $io = $this->io();

    $io->info(array_merge([
      'Exporting webforms with IDs matching one of',
    ], self::CONFIG_NAME_PATTERNS)
    );

    $configFactory = $this->configManager->getConfigFactory();
    $configNames = array_values(
      array_filter(
        $configFactory->listAll(),
        static fn (string $name): bool => !empty(array_filter(
          array_map(
            static fn (string $pattern) => preg_match($pattern, $name),
            self::CONFIG_NAME_PATTERNS
          )
        )),
      )
    );

    $moduleDir = $this->moduleExtensionList->getPath('os2forms_fordelingskomponent_examples');
    $targetDir = $moduleDir . '/config/install';

    foreach ($configNames as $name) {
      $targetName = $targetDir . '/' . $name . '.yml';

      $io->section($name);
      $config = $configFactory->getEditable($name);
      foreach (static::$configKeysToClear as $key) {
        $io->writeln(dt('Clearing key %key', ['%key' => $key]));
        $config->clear($key);
      }
      // @todo (Hon) Can we use the config manager (or factory) to do this?
      file_put_contents($targetName, Yaml::encode($config->get()));
      $io->success(dt('Config written to %file', ['%file' => $targetName]));
    }
  }

}

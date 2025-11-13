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
    $configFactory = $this->configManager->getConfigFactory();
    $configNames = array_values(
      array_filter(
        $configFactory->listAll(),
        static fn (string $name): bool => (bool) preg_match('/^webform\.webform\.os2forms_fdk_/', $name)
      )
    );

    $moduleDir = $this->moduleExtensionList->getPath('os2forms_fordelingskomponent_examples');
    $targetDir = $moduleDir . '/config/install';

    foreach ($configNames as $name) {
      $targetName = $targetDir . '/' . $name . '.yml';

      $this->io()->section($name);
      $config = $configFactory->getEditable($name);
      foreach (static::$configKeysToClear as $key) {
        $this->io()->writeln(dt('Clearing key %key', ['%key' => $key]));
        $config->clear($key);
      }
      // @todo (Hon) Can we use the config manager (or factory) to do this?
      file_put_contents($targetName, Yaml::encode($config->get()));
      $this->io()->success(dt('Config written to %file', ['%file' => $targetName]));
    }
  }

}

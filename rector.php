<?php

/**
 * @file
 */

declare(strict_types=1);

use DrupalRector\Set\Drupal11SetList;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
  ->withPaths([
    __DIR__ . '/src',
  ])
  // Composer autoloads this module's classes (see autoload-dev), but not the
  // contrib classes they extend. Let phpstan-drupal register all Drupal
  // extension namespaces so Rector can load them.
  ->withPHPStanConfigs([__DIR__ . '/vendor/mglaman/phpstan-drupal/extension.neon'])
  ->withBootstrapFiles([__DIR__ . '/vendor/mglaman/phpstan-drupal/drupal-autoloader.php'])
  ->withSets([
    Drupal11SetList::DRUPAL_114,
  ])
  ->withPhpSets(php83: TRUE)
  ->withTypeCoverageLevel(0);

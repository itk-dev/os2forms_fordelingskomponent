<?php

namespace Drupal\os2forms_fordelingskomponent_debug\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DrupalDateTime;
use Symfony\Component\Yaml\Yaml;

/**
 * Abstract controller.
 */
abstract class AbstractController extends ControllerBase {

  /**
   * Format timestamp.
   */
  protected function formatDatetime(?int $timestamp): ?string {
    return $timestamp ? DrupalDateTime::createFromTimestamp($timestamp)->format(\DateTimeImmutable::ATOM) : NULL;
  }

  /**
   * Render YAML.
   */
  protected function renderYaml(?\JsonSerializable $value): string {
    return '<pre><code>'
      . htmlspecialchars(Yaml::dump(json_decode(json_encode($value), TRUE), inline: PHP_INT_MAX))
      . '</code></pre>';
  }

}

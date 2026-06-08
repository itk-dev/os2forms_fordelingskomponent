<?php

declare(strict_types=1);

namespace Drupal\os2forms_fordelingskomponent;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extension.
 */
final class Os2formsFordelingskomponentTwigExtension extends AbstractExtension {

  /**
   * {@inheritdoc}
   */
  public function getFunctions(): array {
    $functions[] = new TwigFunction(
      'os2forms_fordelingskomponent_intval',
      intval(...),
    );

    $functions[] = new TwigFunction(
      'os2forms_fordelingskomponent_floatval',
      static function (string $argument, ?string $langcode = NULL): float {
        $replacements = match ($langcode) {
          // Remove group separators and replace comma with point.
          'da' => ['.' => '', ',' => '.'],
          // Remove group separators.
          default => [',' => ''],
        };

        $argument = str_replace(array_keys($replacements), array_values($replacements), $argument);

        return floatval($argument);
      }
    );

    $functions[] = new TwigFunction(
      'os2forms_fordelingskomponent_gettype',
      gettype(...),
    );

    return $functions;
  }

}

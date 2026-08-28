<?php

declare(strict_types=1);

namespace Drupal\os2forms_fordelingskomponent;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Twig extension.
 */
final class Os2formsFordelingskomponentTwigExtension extends AbstractExtension {

  /**
   * {@inheritdoc}
   */
  #[\Override]
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

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function getFilters(): array {
    $filters[] = new TwigFilter(
      'os2forms_fordelingskomponent_xml_encode',
      static function (array $value, string $numeric_key_prefix = 'key_'): string {
        // https://stackoverflow.com/a/19987539
        $toXml = static function (\SimpleXMLElement $object, array $data) use (&$toXml, $numeric_key_prefix): void {
          foreach ($data as $key => $value) {
            // If the key is an integer, it needs text with it to actually work.
            $valid_key  = is_numeric($key) ? $numeric_key_prefix . $key : $key;
            $new_object = $object->addChild(
              $valid_key,
              is_array($value) ? NULL : htmlspecialchars((string) $value)
            );

            if (is_array($value)) {
              $toXml($new_object, $value);
            }
          }
        };

        $sxe = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><root/>');
        $toXml($sxe, $value);

        $fragment = '';
        foreach ($sxe->xpath('/root/*') as $child) {
          $fragment .= $child->asXML();
        }

        return $fragment;
      },
      ['is_safe' => ['html']]
    );

    return $filters;
  }

}

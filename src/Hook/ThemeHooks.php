<?php

namespace Drupal\os2forms_fordelingskomponent\Hook;

/**
 * Theme hook implementations.
 */
final class ThemeHooks {
  const ROUTING_INFO = 'os2forms_fordelingskomponent_routing_info';
  const DISTRIBUTION_OBJECT_PREVIEW = 'os2forms_fordelingskomponent_distribution_object_preview';
  const DISTRIBUTION_OBJECT_PREVIEW_RENDER = 'os2forms_fordelingskomponent_distribution_object_preview_render';

  /**
   * Implements hook_theme().
   */
  public function theme(array $existing, string $type, string $theme, string $path): array {
    {
    return [
      self::ROUTING_INFO => [
        'variables' => [
          'webform' => NULL,
          'handler' => NULL,
          'handler_settings' => NULL,
          'info' => NULL,
          'return_url' => NULL,
        ],
      ],

      self::DISTRIBUTION_OBJECT_PREVIEW => [
        'variables' => [
          'webform' => NULL,
          'handler' => NULL,
          'handler_settings' => NULL,
          'render_url' => NULL,
          'preview_urls' => [
            'prev' => NULL,
            'self' => NULL,
            'next' => NULL,
          ],
        ],
      ],

      self::DISTRIBUTION_OBJECT_PREVIEW_RENDER => [
        'variables' => [
          'webform' => NULL,
          'handler' => NULL,
          'handler_settings' => NULL,
          'submission' => NULL,
          'exceptions' => NULL,
          'warnings' => NULL,
          'distribution_object' => NULL,
          'xml' => NULL,
        ],
      ],
    ];
    }
  }

}

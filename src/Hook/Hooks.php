<?php

namespace Drupal\os2forms_fordelingskomponent\Hook;

/**
 * Hook implementations.
 */
class Hooks {

  /**
   * Implements hook_theme().
   */
  public function theme(array $existing, string $type, string $theme, string $path): array {
    {
    return [
      'os2forms_fordelingskomponent_distribution_object_preview' => [
        'variables' => [
          'webform' => NULL,
          'handler' => NULL,
          'submission' => NULL,
          'return_url' => NULL,
          'render_url' => NULL,
          'preview_urls' => [
            'prev' => NULL,
            'self' => NULL,
            'next' => NULL,
          ],
        ],
      ],

      'os2forms_fordelingskomponent_distribution_object_preview_render' => [
        'variables' => [
          'webform' => NULL,
          'handler' => NULL,
          'handler_settings' => NULL,
          'submission' => NULL,
          'exceptions' => NULL,
          'warnings' => NULL,
          'context' => NULL,
          'distribution_object' => NULL,
          'distribution_type' => NULL,
        ],
      ],
    ];
    }
  }

}

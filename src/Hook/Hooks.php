<?php

namespace Drupal\os2forms_fordelingskomponent\Hook;

/**
 * Hook implementations.
 */
class Hooks {

  /**
   * Implements hook_theme().
   */
  public function theme(array $existing, string $type, string $theme, string $path) : array {
    {
    return [
      'os2forms_fordelingskomponent_payload_preview' => [
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

      'os2forms_fordelingskomponent_payload_preview_render_xml' => [
        'variables' => [
          'webform' => NULL,
          'handler' => NULL,
          'submission' => NULL,
          'exceptions' => NULL,
          'warnings' => NULL,
          'context' => NULL,
          'template' => NULL,
          'xml' => NULL,
        ],
      ],
    ];
    }
  }

}

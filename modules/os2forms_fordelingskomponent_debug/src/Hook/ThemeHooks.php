<?php

namespace Drupal\os2forms_fordelingskomponent_debug\Hook;

/**
 * Theme hook implementations.
 */
final class ThemeHooks {
  const string SFTP_FILES = 'os2forms_fordelingskomponent_debug_sftp_files';
  const string FORSENDELSER = 'os2forms_fordelingskomponent_debug_forsendelser';
  const string KVITTERINGER = 'os2forms_fordelingskomponent_debug_kvitteringer';

  /**
   * Implements hook_theme().
   */
  public function theme(array $existing, string $type, string $theme, string $path): array {
    {
    return [
      self::SFTP_FILES => [
        'variables' => [
          'files' => NULL,
          'parent_dir' => NULL,
        ],
      ],

      self::FORSENDELSER => [
        'variables' => [
          'items' => NULL,
          'webform' => NULL,
          'webform_submission' => NULL,
        ],
      ],

      self::KVITTERINGER => [
        'variables' => [
          'items' => NULL,
        ],
      ],
    ];
    }
  }

}

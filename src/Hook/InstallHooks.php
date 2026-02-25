<?php

namespace Drupal\os2forms_fordelingskomponent\Hook;

/**
 * Install hook implementations.
 */
final class InstallHooks {
  const TABLE_ANVENDER_FORSENDELSE = 'os2forms_fordelingskomponent_anvender_forsendelse';
  const TABLE_ANVENDER_KVITTERING = 'os2forms_fordelingskomponent_anvender_kvittering';
  const TABLE_MODTAGER_FORSENDELSE = 'os2forms_fordelingskomponent_modtager_forsendelse';

  /**
   * Implements hook_schema().
   */
  public function schema(): array {
    $baseSchema = [
      'fields' => [
        'anvender_transaktions_id' => [
          'description' => 'UUID',
          'type' => 'varchar',
          'length' => 36,
          'not null' => TRUE,
        ],
        'created_at' => [
          'description' => 'The Unix timestamp when the item was created.',
          'type' => 'int',
          'size' => 'big',
          'unsigned' => TRUE,
          'not null' => TRUE,
        ],
        'updated_at' => [
          'description' => 'The Unix timestamp when the item was updated.',
          'type' => 'int',
          'size' => 'big',
          'unsigned' => TRUE,
        ],
        'distribution_transaktions_id' => [
          'description' => 'UUID',
          'type' => 'varchar',
          'length' => 36,
        ],
        'request' => [
          'description' => 'The request (serialized).',
          'type' => 'text',
          'size' => 'big',
          'not null' => TRUE,
        ],
        'response' => [
          'description' => 'The response (serialized).',
          'type' => 'text',
          'size' => 'big',
        ],
      ],
      'primary key' => [
        'anvender_transaktions_id',
      ],
      'indexes' => [
        'distribution_transaktions_id' => [
          'distribution_transaktions_id',
        ],
      ],
    ];

    $createTable = static function (string $description, array $fields = [], array $foreignKeys = [], array $indexes = []) use ($baseSchema): array {
      $table = $baseSchema + [
        'description' => $description,
      ];

      foreach ($fields as $name => $spec) {
        $table['fields'][$name] = $spec;
      }

      foreach ($foreignKeys as $name => $spec) {
        $table['foreign keys'][$name] = $spec;
      }

      foreach ($indexes as $name => $spec) {
        $table['indexes'][$name] = $spec;
      }

      return $table;
    };

    $schema[self::TABLE_ANVENDER_FORSENDELSE] = $createTable(
      description: 'Stores data on forsendelser.',
      fields: [
        'webform_handler_id' => [
          'description' => 'Webform handler ID.',
          'type' => 'varchar',
          'length' => '256',
        ],
        'webform_submission_id' => [
          'description' => 'Webform submission ID. References {webform_submissions}.sid.',
          'type' => 'int',
          'size' => 'big',
          'unsigned' => TRUE,
        ],
        'delivered_at' => [
          'description' => 'The Unix timestamp when the item was delivered.',
          'type' => 'int',
          'size' => 'big',
          'unsigned' => TRUE,
        ],
      ],
      foreignKeys: [
        'webform_submission_id' => [
          'table' => 'webform_submission',
          'columns' => [
            'webform_submission_id' => 'sid',
          ],
        ],
      ],
      indexes: [
        'webform_submission' => ['webform_handler_id', 'webform_submission_id'],
      ]
    );

    $schema[self::TABLE_ANVENDER_KVITTERING] = $createTable(
      description: 'Stores data on kvitteringer.',
    );

    $schema[self::TABLE_MODTAGER_FORSENDELSE] = $createTable(
      description: 'Stores data on forsendelser.',
      fields: [
        'confirmed_at' => [
          'description' => 'The Unix timestamp when the item was delivered.',
          'type' => 'int',
          'size' => 'big',
          'unsigned' => TRUE,
        ],
      ],
    );

    return $schema;
  }

}

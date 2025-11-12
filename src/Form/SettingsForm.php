<?php

declare(strict_types=1);

namespace Drupal\os2forms_fordelingskomponent\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\os2forms_fordelingskomponent\Helper\FordelingskomponentHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Fordelingskomponent settings for this site.
 */
final class SettingsForm extends ConfigFormBase {
  use StringTranslationTrait;

  public const string CONFIG_NAME = 'os2forms_fordelingskomponent.settings';

  public const string TEST_MODE = 'test_mode';

  public const string SECTION_SF2900 = 'sf2900';

  public const string SENDER_ID = 'sender_id';
  public const string ROUTING_MYNDIGHED = 'routing_myndighed';
  public const string REGISTRERING_IT_SYSTEM = 'registrering_it_system';
  public const string CERTIFICATE = 'certificate';

  public const string SECTION_SFTP = 'sftp';
  public const string HOST = 'host';
  public const string USERNAME = 'username';
  public const string PRIVATE_KEY = 'private_key';

  public const string SECTION_PROCESSING = 'processing';
  public const string QUEUE = 'queue';

  /**
   * The queue storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private readonly EntityStorageInterface $queueStorage;

  /**
   * Constructor.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    TypedConfigManagerInterface $typedConfigManager,
    EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct($config_factory, $typedConfigManager);
    $this->queueStorage = $entityTypeManager->getStorage('advancedqueue_queue');
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'os2forms_fordelingskomponent_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [self::CONFIG_NAME];
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config(self::CONFIG_NAME);

    $form['test_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Test mode'),
      '#default_value' => $config->get(self::TEST_MODE),
    ];

    $this->buildFormSf2900($form, $form_state);
    $this->buildFormProcessing($form, $form_state);

    return parent::buildForm($form, $form_state);
  }

  /**
   * Build form section "SF2900".
   */
  private function buildFormSf2900(array &$form, FormStateInterface $formState): void {
    $config = $this->config(self::CONFIG_NAME)->get(self::SECTION_SF2900) ?? [];

    $form[self::SECTION_SF2900] = [
      '#type' => 'fieldset',
      '#title' => $this->t('SF2900'),
      '#tree' => TRUE,
    ];

    $form[self::SECTION_SF2900][self::SENDER_ID] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sender ID'),
      '#required' => TRUE,
      '#default_value' => $config[self::SENDER_ID] ?? NULL,
      '#description' => $this->t('Sender ID (CVR).'),
    ];

    $form[self::SECTION_SF2900][self::ROUTING_MYNDIGHED] = [
      '#type' => 'textfield',
      '#title' => $this->t('Routing myndighed'),
      // '#required' => TRUE,
      '#default_value' => $config[self::ROUTING_MYNDIGHED] ?? NULL,
      '#description' => $this->t('Default routing myndighed (CVR). May be overwritten by handler settings.'),
    ];

    $form[self::SECTION_SF2900][self::REGISTRERING_IT_SYSTEM] = [
      '#type' => 'textfield',
      '#title' => $this->t('Registrering it-system'),
      // '#required' => TRUE,
      '#default_value' => $config[self::REGISTRERING_IT_SYSTEM] ?? NULL,
      '#description' => $this->t('@todo: Registrering it-system (UUID).'),
    ];

    $form[self::SECTION_SF2900]['certificate'] = [
      '#type' => 'key_select',
      '#key_filters' => [
        'type' => 'os2web_key_certificate',
      ],
      '#title' => $this->t('Certificate'),
      '#required' => TRUE,
      '#default_value' => $config['certificate'] ?? NULL,
      '#description' => $this->t('Passwordless certificate.'),
    ];

    $form[self::SECTION_SF2900][self::SECTION_SFTP] = [
      '#type' => 'fieldset',
      '#title' => $this->t('SFTP'),
      '#tree' => TRUE,
    ];

    $form[self::SECTION_SF2900][self::SECTION_SFTP][self::USERNAME] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#required' => TRUE,
      '#default_value' => $config[self::SECTION_SFTP][self::USERNAME] ?? NULL,
      '#description' => $this->t('SFTP username.'),
    ];

    $form[self::SECTION_SF2900][self::SECTION_SFTP][self::PRIVATE_KEY] = [
      '#type' => 'key_select',
      '#title' => $this->t('Private key'),
      '#required' => TRUE,
      '#default_value' => $config[self::SECTION_SFTP][self::PRIVATE_KEY] ?? NULL,
      '#description' => $this->t('SFTP private key.'),
    ];

  }

  /**
   * Build form section "Processing".
   */
  private function buildFormProcessing(array &$form, FormStateInterface $formState): void {
    $config = $this->config(self::CONFIG_NAME)->get(self::SECTION_PROCESSING);

    $form[self::SECTION_PROCESSING] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Processing'),
      '#tree' => TRUE,
    ];

    $defaultValue = $config[self::QUEUE] ?? NULL;
    $description = empty($defaultValue)
      ? $this->t('Optional queue for fordelingskomponent jobs. If no queue is specified, all fordelingskomponent jobs are run immediately.')
      : $this->t("Optional queue for fordelingskomponent jobs. If no queue is specified, all fordelingskomponent jobs are run immediately. <a href=':queue_url'>The queue</a> must be run via Drupal's cron or via <code>drush advancedqueue:queue:process @queue</code> (in a cron job).",
        [
          '@queue' => $defaultValue,
          ':queue_url' => '/admin/config/system/queues/jobs/' . urlencode((string) $defaultValue),
        ]);
    $form[self::SECTION_PROCESSING][self::QUEUE] = [
      '#type' => 'select',
      '#title' => $this->t('Queue'),
      '#options' => array_map(
        static fn(EntityInterface $queue) => $queue->label(),
        $this->queueStorage->loadMultiple()
      ),
      '#empty_option' => $this->t('No queue'),
      '#default_value' => $defaultValue,
      '#description' => $description,
    ];
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $value = $form_state->getValue(self::SECTION_SF2900)[self::SENDER_ID] ?? '';
    if (!FordelingskomponentHelper::isValidCvr($value)) {
      $form_state->setErrorByName('sf2900][sender_id', $this->t('The sender ID is not a valid CVR.'));
    }

    $value = $form_state->getValue(self::SECTION_SF2900)[self::ROUTING_MYNDIGHED] ?? '';
    if (!empty($value) && !FordelingskomponentHelper::isValidCvr($value)) {
      $form_state->setErrorByName('sf2900][routing_myndighed', $this->t('The routing myndighed is not a valid CVR.'));
    }

    $value = $form_state->getValue(self::SECTION_SF2900)[self::REGISTRERING_IT_SYSTEM] ?? '';
    if (!empty($value) && !FordelingskomponentHelper::isValidUuid($value)) {
      $form_state->setErrorByName('sf2900][registrering_it_system', $this->t('The registrering it system is not a valid UUID.'));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config(self::CONFIG_NAME)
      ->set(self::TEST_MODE, $form_state->getValue(self::TEST_MODE))
      ->set(self::SECTION_SF2900, $form_state->getValue(self::SECTION_SF2900))
      ->set(self::SECTION_PROCESSING, $form_state->getValue(self::SECTION_PROCESSING))
      ->save();
    parent::submitForm($form, $form_state);
  }

}

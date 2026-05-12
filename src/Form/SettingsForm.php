<?php

declare(strict_types=1);

namespace Drupal\os2forms_fordelingskomponent\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\os2forms_fordelingskomponent\Helper\FordelingskomponentHelper;
use Drupal\os2forms_fordelingskomponent\Settings;
use Drupal\os2forms_fordelingskomponent\Settings\GeneralSettings;
use Drupal\os2forms_fordelingskomponent\Settings\SenderSettings;
use Drupal\os2forms_fordelingskomponent\Settings\SenderSettings\SftpSettings;

/**
 * Configure Fordelingskomponent settings for this site.
 */
final class SettingsForm extends ConfigFormBase {
  use StringTranslationTrait;
  use AutowireTrait;

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
    private readonly Settings $settings,
  ) {
    parent::__construct($config_factory, $typedConfigManager);
    $this->queueStorage = $entityTypeManager->getStorage('advancedqueue_queue');
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
    return [Settings::CONFIG_NAME];
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form[SenderSettings::NAME] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Sender'),
      '#tree' => TRUE,
    ] + $this->buildFormSender();

    $form[GeneralSettings::NAME] = [
      '#type' => 'fieldset',
      '#title' => $this->t('General'),
      '#tree' => TRUE,
    ] + $this->buildFormGeneral();

    return parent::buildForm($form, $form_state);
  }

  /**
   * Build form section "SF2900".
   */
  private function buildFormSender(): array {
    $settings = $this->settings->getSenderSettings();

    $section[SenderSettings::SENDER_ID] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sender ID'),
      '#required' => TRUE,
      '#default_value' => $settings->senderId,
      '#description' => $this->t('Sender ID (CVR).'),
    ];

    $section[SenderSettings::ROUTING_MYNDIGHED] = [
      '#type' => 'textfield',
      '#title' => $this->t('Routing myndighed'),
      // '#required' => TRUE,
      '#default_value' => $settings->routingMyndighed,
      '#description' => $this->t('Default routing myndighed (CVR). May be overwritten by handler settings.'),
    ];

    $section[SenderSettings::REGISTRERING_IT_SYSTEM] = [
      '#type' => 'textfield',
      '#title' => $this->t('Registrering it-system'),
      '#required' => TRUE,
      '#default_value' => $settings->registreringItSystem,
    ];

    $section[SenderSettings::CERTIFICATE] = [
      '#type' => 'key_select',
      '#key_filters' => [
        'type' => 'os2web_key_certificate',
      ],
      '#title' => $this->t('Certificate'),
      '#required' => TRUE,
      '#default_value' => $settings->certificate,
      '#description' => $this->t('Passwordless certificate.'),
    ];

    $section[SftpSettings::NAME] = [
      '#type' => 'fieldset',
      '#title' => $this->t('SFTP'),
      '#tree' => TRUE,
    ];

    $section[SftpSettings::NAME][SftpSettings::USERNAME] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#required' => TRUE,
      '#default_value' => $settings->sftp?->username,
      '#description' => $this->t('SFTP username.'),
    ];

    $section[SftpSettings::NAME][SftpSettings::PRIVATE_KEY] = [
      '#type' => 'key_select',
      '#title' => $this->t('Private key'),
      '#required' => TRUE,
      '#default_value' => $settings->sftp?->privateKey,
      '#description' => $this->t('SFTP private key.'),
    ];

    return $section;
  }

  /**
   * Build form section "General".
   */
  private function buildFormGeneral(): array {
    $settings = $this->settings->getGeneralSettings();

    $description = empty($settings->queue)
      ? $this->t('Queue for Fordelingskomponent jobs.')
      : $this->t("Queue for Fordelingskomponent jobs. <a href=':queue_url'>The queue</a> must be run via Drupal's cron or via <code>drush advancedqueue:queue:process @queue</code> (in a cron job).",
        [
          '@queue' => $settings->queue,
          ':queue_url' => '/admin/config/system/queues/jobs/' . urlencode((string) $settings->queue),
        ]);
    $section[GeneralSettings::QUEUE] = [
      '#type' => 'select',
      '#title' => $this->t('Queue'),
      '#options' => array_map(
        static fn(EntityInterface $queue) => $queue->label(),
        $this->queueStorage->loadMultiple()
      ),
      '#empty_option' => $this->t('No queue'),
      '#default_value' => $settings->queue,
      '#description' => $description,
    ];

    $section[GeneralSettings::TEST_MODE] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Test mode'),
      '#default_value' => $settings->testMode,
    ];

    return $section;
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $setError = static fn (string|array $path, TranslatableMarkup $message) =>   $form_state->setErrorByName(implode('][', (array) $path), $message);

    $value = $form_state->getValue(SenderSettings::NAME)[SenderSettings::SENDER_ID] ?? '';
    if (!FordelingskomponentHelper::isValidCvr($value)) {
      $setError([SenderSettings::NAME, SenderSettings::SENDER_ID], $this->t('The sender ID is not a valid CVR.'));
    }

    $value = $form_state->getValue(SenderSettings::NAME)[SenderSettings::ROUTING_MYNDIGHED] ?? '';
    if (!empty($value) && !FordelingskomponentHelper::isValidCvr($value)) {
      $setError([SenderSettings::NAME, SenderSettings::ROUTING_MYNDIGHED], $this->t('The routing myndighed is not a valid CVR.'));
    }

    $value = $form_state->getValue(SenderSettings::NAME)[SenderSettings::REGISTRERING_IT_SYSTEM] ?? '';
    if (!empty($value) && !FordelingskomponentHelper::isValidUuid($value)) {
      $setError([SenderSettings::NAME, SenderSettings::REGISTRERING_IT_SYSTEM], $this->t('The registrering it system is not a valid UUID.'));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->config(Settings::CONFIG_NAME);
    foreach ([
      SenderSettings::NAME,
      GeneralSettings::NAME,
    ] as $name) {
      $config->set($name, $form_state->getValue($name));
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

}

<?php

namespace Drupal\os2forms_fordelingskomponent\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\os2forms_fordelingskomponent\Helper\FordelingskomponentHelper;
use Drupal\os2forms_fordelingskomponent\Helper\WebformHelperSF2900;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Fordelingskomponent Webform Handler.
 *
 * @WebformHandler(
 *   id = "os2forms_fordelingskomponent_sf2900",
 *   label = @Translation("Fordelingskomponent (sf2900)"),
 *   category = @Translation("Web services"),
 *   description = @Translation("Sends webform submission to Fordelingskomponenten."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_IGNORED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */
final class WebformHandlerSF2900 extends WebformHandlerBase {
  use StringTranslationTrait;

  public const string ID = 'os2forms_fordelingskomponent_sf2900';

  public const string SECTION_SF2900 = 'sf2900';
  public const string ATTACHMENT_ELEMENT = 'attachment_element';

  /**
   * The webform helper.
   */
  private readonly WebformHelperSF2900 $helper;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->helper = $container->get(WebformHelperSF2900::class);

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form[static::SECTION_SF2900] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Fordelingskomponent'),
      '#tree' => TRUE,
    ];

    $configuration = $this->configuration[static::SECTION_SF2900] ?? NULL;

    $form[static::SECTION_SF2900][FordelingskomponentHelper::KLE_EMNE] = [
      '#title' => $this->t('KLE-emne'),
      '#type' => 'textfield',
      '#default_value' => $configuration[FordelingskomponentHelper::KLE_EMNE] ?? NULL,
      '#required' => TRUE,
      '#attributes' => [
        'pattern' => FordelingskomponentHelper::KLE_EMNE_PATTERN,
      ],
      '#description' => $this->t('KLE-emne (format: dd.dd.dd)'),
    ];

    $form[static::SECTION_SF2900][FordelingskomponentHelper::HANDLING_FACET] = [
      '#title' => $this->t('Handling-facet'),
      '#type' => 'textfield',
      '#default_value' => $configuration[FordelingskomponentHelper::HANDLING_FACET] ?? NULL,
      '#attributes' => [
        'pattern' => FordelingskomponentHelper::HANDLING_FACET_PATTERN,
      ],
    ];

    $availableElements = $this->getAttachmentElements();
    $form[static::SECTION_SF2900][static::ATTACHMENT_ELEMENT] = [
      '#type' => 'select',
      '#title' => $this->t('Element that contains the document to send'),
      '#default_value' => $configuration[static::ATTACHMENT_ELEMENT] ?? NULL,
      '#required' => TRUE,
      '#options' => $availableElements,
    ];

    $form[static::SECTION_SF2900][FordelingskomponentHelper::BRUGERVENDT_NOEGLE] = [
      '#title' => $this->t('Brugervendt nøgle'),
      '#type' => 'textfield',
      '#default_value' => $configuration[FordelingskomponentHelper::BRUGERVENDT_NOEGLE] ?? NULL,
      '#required' => TRUE,
      '#description' => 'WHAT IS THIS?!',
    ];

    $form[static::SECTION_SF2900][FordelingskomponentHelper::TITEL] = [
      '#title' => $this->t('Titel'),
      '#type' => 'textfield',
      '#default_value' => $configuration[FordelingskomponentHelper::TITEL] ?? NULL,
      '#required' => TRUE,
    ];

    $form[static::SECTION_SF2900][FordelingskomponentHelper::BESKRIVELSE] = [
      '#title' => $this->t('Beskrivelse'),
      '#type' => 'textarea',
      '#default_value' => $configuration[FordelingskomponentHelper::BESKRIVELSE] ?? NULL,
      '#required' => TRUE,
    ];

    return $this->setSettingsParents($form);
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue(static::SECTION_SF2900);
    $kleEMne = $values[FordelingskomponentHelper::KLE_EMNE];
    if (!preg_match('/' . FordelingskomponentHelper::KLE_EMNE_PATTERN . '/', (string) $kleEMne)) {
      $form_state->setErrorByName(static::SECTION_SF2900 . '][' . FordelingskomponentHelper::KLE_EMNE, $this->t('Invalid KLE-emne: %kle_emne.', ['%kle_emne' => $kleEMne]));
    }

    $handling_facet = $values[FordelingskomponentHelper::HANDLING_FACET];
    if (!empty($handling_facet) && !preg_match('/' . FordelingskomponentHelper::HANDLING_FACET_PATTERN . '/', (string) $handling_facet)) {
      $form_state->setErrorByName(static::SECTION_SF2900 . '][' . FordelingskomponentHelper::HANDLING_FACET,
        $this->t('Invalid Handling-facet: %handling_facet.', ['%handling_facet' => $handling_facet]));
    }

    parent::validateConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $this->configuration[static::SECTION_SF2900] = $form_state->getValue(static::SECTION_SF2900);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    // Run only when submission is completed.
    if (!$webform_submission->isCompleted()) {
      return;
    }

    $this->helper->createJob($webform_submission, $this->configuration[static::SECTION_SF2900]);
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-return void
   */
  public function postDelete(WebformSubmissionInterface $webform_submission) {
    $this->helper->deleteMessages([$webform_submission]);
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-return void
   */
  public function postPurge(array $webform_submissions) {
    $this->helper->deleteMessages($webform_submissions);
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $kleEmne = $this->configuration[self::SECTION_SF2900][FordelingskomponentHelper::KLE_EMNE];
    $handlingFacet = $this->configuration[self::SECTION_SF2900][FordelingskomponentHelper::HANDLING_FACET];

    $build = [
      'info' => [
        '#prefix' => '<div>',
        '#suffix' => '</div>',
        '#markup' => $this->t('KLE-emne: %kle_emne; Handling-facet: %handling_facet',
          [
            '%kle_emne' => $kleEmne,
            '%handling_facet' => $handlingFacet,
          ]),
      ],
    ];

    $items = [];

    $items[] = Link::createFromRoute(
      $this->t('Preview payload'),
      'os2forms_fordelingskomponent.fordelingskomponent_payload.preview', [
        'webform' => $this->getWebform()->id(),
        'webform_handler' => $this->getHandlerId(),
      ]
    );

    $items[] = Link::createFromRoute(
      $this->t('Edit handler'),
      'entity.webform.handler.edit_form', [
        'webform' => $this->getWebform()->id(),
        'webform_handler' => $this->getHandlerId(),
      ]
    );

    if ($kleEmne) {
      $items[] = Link::createFromRoute(
          $this->t('Show routing info'),
          'os2forms_fordelingskomponent.routing_info', [
            'webform' => $this->getWebform()->id(),
            'handler' => $this->getHandlerId(),
          ]
        );
    }

    $build['links'] = [
      '#theme' => 'item_list',
      '#items' => $items,
    ];

    return $build;
  }

  /**
   * Get attachment elements.
   *
   * @phpstan-return array<string, mixed>
   */
  private function getAttachmentElements(): array {
    $elements = $this->getWebform()->getElementsDecodedAndFlattened();

    $elementTypes = [
      'webform_entity_print_attachment:pdf',
      'os2forms_attachment',
    ];
    $elements = array_filter(
      $elements,
      static fn(array $element) => in_array($element['#type'], $elementTypes, TRUE)
    );

    return array_map(static fn(array $element) => $element['#title'], $elements);
  }

}

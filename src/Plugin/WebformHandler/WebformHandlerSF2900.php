<?php

namespace Drupal\os2forms_fordelingskomponent\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\os2forms_fordelingskomponent\Exception\InvalidXmlTemplateException;
use Drupal\os2forms_fordelingskomponent\Helper\FordelingskomponentHelper;
use Drupal\os2forms_fordelingskomponent\Helper\WebformHelperSF2900;
use Drupal\os2forms_fordelingskomponent\Helper\XmlHelper;
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

  /**
   * The webform helper.
   */
  private readonly WebformHelperSF2900 $helper;

  /**
   * The XML helper.
   */
  private readonly XmlHelper $xmlHelper;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->helper = $container->get(WebformHelperSF2900::class);
    $instance->xmlHelper = $container->get(XmlHelper::class);

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form[self::SECTION_SF2900] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Fordelingskomponent'),
      '#tree' => TRUE,
    ];

    $configuration = $this->configuration[self::SECTION_SF2900] ?? NULL;

    $form[self::SECTION_SF2900][FordelingskomponentHelper::KLE_EMNE] = [
      '#title' => $this->t('KLE-emne'),
      '#type' => 'textfield',
      '#default_value' => $configuration[FordelingskomponentHelper::KLE_EMNE] ?? NULL,
      '#required' => TRUE,
      '#attributes' => [
        'pattern' => FordelingskomponentHelper::KLE_EMNE_PATTERN,
      ],
      '#description' => $this->t('KLE-emne (format: dd.dd.dd)'),
    ];

    $form[self::SECTION_SF2900][FordelingskomponentHelper::HANDLING_FACET] = [
      '#title' => $this->t('Handling-facet'),
      '#type' => 'textfield',
      '#default_value' => $configuration[FordelingskomponentHelper::HANDLING_FACET] ?? NULL,
      '#attributes' => [
        'pattern' => FordelingskomponentHelper::HANDLING_FACET_PATTERN,
      ],
    ];

    $form[self::SECTION_SF2900][FordelingskomponentHelper::BRUGERVENDT_NOEGLE] = [
      '#title' => $this->t('Brugervendt nøgle'),
      '#type' => 'textfield',
      '#default_value' => $configuration[FordelingskomponentHelper::BRUGERVENDT_NOEGLE] ?? NULL,
      '#required' => TRUE,
      '#description' => 'WHAT IS THIS?!',
    ];

    $form[self::SECTION_SF2900][FordelingskomponentHelper::TITEL] = [
      '#title' => $this->t('Titel'),
      '#type' => 'textfield',
      '#default_value' => $configuration[FordelingskomponentHelper::TITEL] ?? NULL,
      '#required' => TRUE,
    ];

    $form[self::SECTION_SF2900][FordelingskomponentHelper::BESKRIVELSE] = [
      '#title' => $this->t('Beskrivelse'),
      '#type' => 'textarea',
      '#default_value' => $configuration[FordelingskomponentHelper::BESKRIVELSE] ?? NULL,
      '#required' => TRUE,
    ];

    $this->buildDistributionForm($form, $configuration, $form_state);

    return $this->setSettingsParents($form);
  }

  /**
   * Build distribution form.
   */
  private function buildDistributionForm(array &$form, array $configuration): void {
    $form[self::SECTION_SF2900][FordelingskomponentHelper::DISTRIBUTION_TYPE] = [
      '#title' => $this->t('Distribution type'),
      '#type' => 'select',
      '#options' => [
        FordelingskomponentHelper::DISTRIBUTION_TYPE_JOURNALPOST => $this->t('Journalpost'),
        FordelingskomponentHelper::DISTRIBUTION_TYPE_DOKUMENT => $this->t('Dokument'),
        FordelingskomponentHelper::DISTRIBUTION_TYPE_FORMULAR => $this->t('Formular'),
      ],
      '#default_value' => $configuration[FordelingskomponentHelper::DISTRIBUTION_TYPE] ?? NULL,
      '#required' => TRUE,
    ];

    /*
     * Set "visible" and "required" states on element depending on
     * distribution types.
     */
    $setStates = function (array &$element, array $distributionTypes, string $connector = 'or', bool $require = TRUE): void {
      $conditions = array_map(static fn (string $type) => ['value' => $type], $distributionTypes);
      // Insert connector between all conditions.
      $numberOfValues = count($conditions);
      for ($i = 0; $i < $numberOfValues - 1; $i++) {
        array_splice($conditions, 2 * $i + 1, 0, [$connector]);
      }
      $state = [
        ':input[name="settings[' . self::SECTION_SF2900 . '][' . FordelingskomponentHelper::DISTRIBUTION_TYPE . ']"]' => $conditions,
      ];
      $element['#states']['visible'][] = $state;
      if ($require) {
        $element['#states']['required'][] = $state;
      }
    };

    $availableElements = $this->getAttachmentElements();
    $form[self::SECTION_SF2900][FordelingskomponentHelper::ATTACHMENT_ELEMENT] = [
      '#type' => 'select',
      '#title' => $this->t('Element that contains the document to send'),
      '#default_value' => $configuration[FordelingskomponentHelper::ATTACHMENT_ELEMENT] ?? NULL,
      '#options' => $availableElements,
    ];
    $setStates($form[self::SECTION_SF2900][FordelingskomponentHelper::ATTACHMENT_ELEMENT], [
      FordelingskomponentHelper::DISTRIBUTION_TYPE_DOKUMENT,
      FordelingskomponentHelper::DISTRIBUTION_TYPE_FORMULAR,
    ]);

    $form[self::SECTION_SF2900][FordelingskomponentHelper::XML_TEMPLATE] = [
      '#type' => 'textarea',
      '#rows' => 30,
      '#title' => $this->t('XML template'),
      '#default_value' => $configuration[FordelingskomponentHelper::XML_TEMPLATE] ?? NULL,
    ];
    $setStates($form[self::SECTION_SF2900][FordelingskomponentHelper::XML_TEMPLATE], [
      FordelingskomponentHelper::DISTRIBUTION_TYPE_FORMULAR,
    ]);

    $form[self::SECTION_SF2900][FordelingskomponentHelper::XSD_URL] = [
      '#type' => 'textfield',
      '#title' => $this->t('XSD URL'),
      '#default_value' => $configuration[FordelingskomponentHelper::XSD_URL] ?? NULL,
    ];
    $setStates($form[self::SECTION_SF2900][FordelingskomponentHelper::XSD_URL], [
      FordelingskomponentHelper::DISTRIBUTION_TYPE_FORMULAR,
    ], require: FALSE);

    $form[self::SECTION_SF2900][FordelingskomponentHelper::JOURNALPOST_MESSAGE] = [
      '#type' => 'textarea',
      '#rows' => 30,
      '#title' => $this->t('Message'),
      '#default_value' => $configuration[FordelingskomponentHelper::JOURNALPOST_MESSAGE] ?? NULL,
    ];
    $setStates($form[self::SECTION_SF2900][FordelingskomponentHelper::JOURNALPOST_MESSAGE], [
      FordelingskomponentHelper::DISTRIBUTION_TYPE_JOURNALPOST,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue(self::SECTION_SF2900);
    $kleEMne = $values[FordelingskomponentHelper::KLE_EMNE];
    if (!preg_match('/' . FordelingskomponentHelper::KLE_EMNE_PATTERN . '/', (string) $kleEMne)) {
      $form_state->setErrorByName(self::SECTION_SF2900 . '][' . FordelingskomponentHelper::KLE_EMNE, $this->t('Invalid KLE-emne: %kle_emne.', ['%kle_emne' => $kleEMne]));
    }

    $handlingFacet = $values[FordelingskomponentHelper::HANDLING_FACET];
    if (!empty($handlingFacet) && !preg_match('/' . FordelingskomponentHelper::HANDLING_FACET_PATTERN . '/', (string) $handlingFacet)) {
      $form_state->setErrorByName(self::SECTION_SF2900 . '][' . FordelingskomponentHelper::HANDLING_FACET,
        $this->t('Invalid Handling-facet: %handling_facet.', ['%handling_facet' => $handlingFacet]));
    }

    $type = $values[FordelingskomponentHelper::DISTRIBUTION_TYPE];
    if (FordelingskomponentHelper::DISTRIBUTION_TYPE_FORMULAR === $type) {
      $template = (string) ($values[FordelingskomponentHelper::XML_TEMPLATE] ?? NULL);
      try {
        $this->xmlHelper->validateXml($template);
        $this->xmlHelper->validateTemplate($template);
      }
      catch (InvalidXmlTemplateException $e) {
        $form_state->setErrorByName(self::SECTION_SF2900 . '][' . FordelingskomponentHelper::XML_TEMPLATE,
          $this->t('Invalid XML template: %message.', ['%message' => $e->getMessage()]));
      }

      $url = (string) ($values[FordelingskomponentHelper::XSD_URL] ?? NULL);
      if ($url) {
        $contents = @file_get_contents($url);
        if (FALSE === $contents) {
          $form_state->setErrorByName(self::SECTION_SF2900 . '][' . FordelingskomponentHelper::XSD_URL,
            $this->t('Cannot read XSD URL %url.', ['%url' => $url]));
        }
      }
    }

    parent::validateConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $this->configuration[self::SECTION_SF2900] = $form_state->getValue(self::SECTION_SF2900);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    // Run only when submission is completed.
    if (!$webform_submission->isCompleted()) {
      return;
    }

    $this->helper->createJob($webform_submission, $this->configuration[self::SECTION_SF2900]);
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
      $this->t('Preview distribution object'),
      'os2forms_fordelingskomponent.fordelingskomponent_distribution_object.preview', [
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

    $items[] = Link::createFromRoute(
      $this->t('Edit handler'),
      'entity.webform.handler.edit_form', [
        'webform' => $this->getWebform()->id(),
        'webform_handler' => $this->getHandlerId(),
      ]
    );

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

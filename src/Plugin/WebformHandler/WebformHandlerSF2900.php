<?php

namespace Drupal\os2forms_fordelingskomponent\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\os2forms_fordelingskomponent\Exception\InvalidXmlException;
use Drupal\os2forms_fordelingskomponent\Helper\WebformHelperSF2900;
use Drupal\os2forms_fordelingskomponent\Helper\XmlHelper;
use Drupal\os2forms_fordelingskomponent\Settings;
use Drupal\os2forms_fordelingskomponent\Settings\DistributionContextSettings;
use Drupal\os2forms_fordelingskomponent\Settings\DistributionObjectSettings;
use Drupal\os2forms_fordelingskomponent\Settings\DistributionObjectSettings\FilesSettings as DistributionObjectFilesSettings;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\Utility\WebformDialogHelper;
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
   * The settings.
   */
  private Settings $settingsService;

  /**
   * The webform helper.
   */
  private WebformHelperSF2900 $helper;

  /**
   * The XML helper.
   */
  private XmlHelper $xmlHelper;

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->settingsService = $container->get(Settings::class);
    $instance->helper = $container->get(WebformHelperSF2900::class);
    $instance->xmlHelper = $container->get(XmlHelper::class);

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getOffCanvasWidth(): string {
    return WebformDialogHelper::DIALOG_NONE;
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form[DistributionContextSettings::NAME] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Fordelingskomponent'),
      '#tree' => TRUE,
    ] + $this->buildConfigurationFormDistributionContext();

    $form[DistributionObjectSettings::NAME] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Fordelingsobjekt'),
      '#tree' => TRUE,
    ] + $this->buildConfigurationFormDistributionObject();

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * Build distribution context form section.
   */
  private function buildConfigurationFormDistributionContext(): array {
    $settings = $this->settingsService->getDistributionContextSettings((array) ($this->getSettings()[DistributionContextSettings::NAME] ?? NULL));
    $globalSettings = $this->settingsService->getDistributionContextSettings();

    $section[DistributionContextSettings::ROUTING_MODTAGER_AKTOER] = [
      '#title' => $this->t('Routing modtager aktoer'),
      '#type' => 'textfield',
      '#attributes' => [
        'pattern' => DistributionContextSettings::ROUTING_MODTAGER_AKTOER_PATTERN,
      ],
      '#default_value' => $settings->routingModtagerAktoer,
      '#description' => $this->t('Routing modtager aktoer (UUID). If set, any routing rules (using %kle_emne and %handling_facet) will be ignored.', [
        '%kle_emne' => $this->t('KLE-emne'),
        '%handling_facet' => $this->t('Handling facet'),
      ]),
    ];

    $section[DistributionContextSettings::KLE_EMNE] = [
      '#title' => $this->t('KLE-emne'),
      '#type' => 'textfield',
      '#default_value' => $settings->kleEmne,
      // @todo Show default global value for all fields.
      '#placeholder' => $globalSettings->kleEmne,
      '#required' => TRUE,
      '#attributes' => [
        'pattern' => DistributionContextSettings::KLE_EMNE_PATTERN,
      ],
      '#description' => $this->t('KLE-emne (format: dd.dd.dd)'),
    ];

    $section[DistributionContextSettings::HANDLING_FACET] = [
      '#title' => $this->t('Handling-facet'),
      '#type' => 'textfield',
      '#default_value' => $settings->handlingFacet,
      '#attributes' => [
        'pattern' => DistributionContextSettings::HANDLING_FACET_PATTERN,
      ],
      '#description' => $this->t('handlingfacet (format: [A-Å]dd)'),
    ];

    $section[DistributionContextSettings::BRUGERVENDT_NOEGLE] = [
      '#title' => $this->t('Brugervendt nøgle'),
      '#type' => 'textfield',
      '#default_value' => $settings->brugervendtNoegle,
      '#required' => TRUE,
      '#description' => 'WHAT IS THIS?!',
    ];

    $section[DistributionContextSettings::TITEL] = [
      '#title' => $this->t('Titel'),
      '#type' => 'textfield',
      '#default_value' => $settings->titel,
      '#required' => TRUE,
    ];

    $section[DistributionContextSettings::BESKRIVELSE] = [
      '#title' => $this->t('Beskrivelse'),
      '#type' => 'textarea',
      '#default_value' => $settings->beskrivelse,
      '#required' => TRUE,
    ];

    return $section;
  }

  /**
   * Build distribution object form section.
   */
  private function buildConfigurationFormDistributionObject(): array {
    $settings = $this->settingsService->getDistributionObjectSettings((array) ($this->getSettings()[DistributionObjectSettings::NAME] ?? NULL));

    $section[DistributionObjectSettings::DISTRIBUTION_TYPE] = [
      '#title' => $this->t('Distribution type'),
      '#type' => 'select',
      '#options' => [
        DistributionObjectSettings::DISTRIBUTION_TYPE_JOURNALPOST => $this->t('Journalpost'),
        DistributionObjectSettings::DISTRIBUTION_TYPE_DOKUMENT => $this->t('Dokument'),
        DistributionObjectSettings::DISTRIBUTION_TYPE_FORMULAR => $this->t('Formular'),
      ],
      '#default_value' => $settings->distributionType,
      '#required' => TRUE,
    ];

    /*
     * Set "visible" and "required" states on element depending on
     * distribution types.
     */
    $setStates = function (
      array &$element,
      array $distributionTypes,
      string $connector = 'or',
      bool $require = TRUE,
    ): void {
      $conditions = array_map(static fn(string $type) => ['value' => $type], $distributionTypes);
      // Insert connector between all conditions.
      $numberOfValues = count($conditions);
      for ($i = 0; $i < $numberOfValues - 1; $i++) {
        array_splice($conditions, 2 * $i + 1, 0, [$connector]);
      }
      $state = [
        ':input[name="settings[' . DistributionObjectSettings::NAME . '][' . DistributionObjectSettings::DISTRIBUTION_TYPE . ']"]' => $conditions,
      ];
      $element['#states']['visible'][] = $state;
      if ($require) {
        $element['#states']['required'][] = $state;
      }
    };

    $section[DistributionObjectSettings::JOURNALPOST_MESSAGE] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message'),
      '#default_value' => $settings->journalpostMessage,
      '#description' => $this->t('Journal post message. Supports tokens.'),
    ];
    $setStates($section[DistributionObjectSettings::JOURNALPOST_MESSAGE], [
      DistributionObjectSettings::DISTRIBUTION_TYPE_JOURNALPOST,
    ]);

    $attachmentElements = $this->getAttachmentElements();
    $section[DistributionObjectSettings::ATTACHMENT_ELEMENT] = [
      '#type' => 'select',
      '#title' => $this->t('Element that contains the document to send'),
      '#default_value' => $settings->attachmentElement,
      '#options' => $attachmentElements,
    ];
    $setStates($section[DistributionObjectSettings::ATTACHMENT_ELEMENT], [
      DistributionObjectSettings::DISTRIBUTION_TYPE_DOKUMENT,
      DistributionObjectSettings::DISTRIBUTION_TYPE_FORMULAR,
    ]);

    $section[DistributionObjectSettings::FORMULAR_TYPE] = [
      '#type' => 'textfield',
      '#title' => $this->t('Formulartype'),
      '#default_value' => $settings->formularType,
    ];
    $setStates($section[DistributionObjectSettings::FORMULAR_TYPE], [
      DistributionObjectSettings::DISTRIBUTION_TYPE_FORMULAR,
    ], require: FALSE);

    $section[DistributionObjectSettings::FILES] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Files'),
    ];
    $setStates($section[DistributionObjectSettings::FILES], [
      DistributionObjectSettings::DISTRIBUTION_TYPE_FORMULAR,
    ]);

    $section[DistributionObjectSettings::FILES][DistributionObjectFilesSettings::FILSPECIFIKATION] = [
      '#type' => 'textfield',
      '#title' => $this->t('Filspecifikation (InfRef)'),
      '#default_value' => $settings->files->filspecifikation,
      '#description' => $this->t('Filspecifikation matching %formular_type', [
        '%formular_type' => $this->t('Formulartype'),
      ]),
    ];
    $setStates($section[DistributionObjectSettings::FILES][DistributionObjectFilesSettings::FILSPECIFIKATION], [
      DistributionObjectSettings::DISTRIBUTION_TYPE_FORMULAR,
    ], require: FALSE);

    $section[DistributionObjectSettings::FILES][DistributionObjectFilesSettings::RECIPIENT_IT_SYSTEM_LOOK_UP] = [
      '#title' => $this->t('Look up %recipient_it_system based on distribution object routing', [
        '%recipient_it_system' => $this->t('Recipient IT system'),
      ]),
      '#type' => 'checkbox',
      '#default_value' => $settings->files->recipientItSystemLookUp,
    ];

    $section[DistributionObjectSettings::FILES][DistributionObjectFilesSettings::RECIPIENT_IT_SYSTEM] = [
      '#title' => $this->t('Recipient IT system'),
      '#type' => 'textfield',
      '#attributes' => [
        'pattern' => DistributionObjectFilesSettings::RECIPIENT_IT_SYSTEM_PATTERN,
      ],
      '#default_value' => $settings->files->recipientItSystem,
      '#description' => $this->t('Recipient IT system (UUID). Leave empty for implicit file routing.'),
      '#states' => [
        'visible' => [
          ':input[name="settings[' . DistributionObjectSettings::NAME . '][' . DistributionObjectSettings::FILES . '][' . DistributionObjectFilesSettings::RECIPIENT_IT_SYSTEM_LOOK_UP . ']"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $section[DistributionObjectSettings::FILES][DistributionObjectFilesSettings::RECIPIENT_AUTHORITY] = [
      '#title' => $this->t('Recipient authority'),
      '#type' => 'textfield',
      '#attributes' => [
        'pattern' => DistributionObjectFilesSettings::RECIPIENT_AUTHORITY_PATTERN,
      ],
      '#default_value' => $settings->files->recipientAuthority,
      '#description' => $this->t('CVR for recipient'),
    ];
    $setStates($section[DistributionObjectSettings::FILES][DistributionObjectFilesSettings::RECIPIENT_AUTHORITY], [
      DistributionObjectSettings::DISTRIBUTION_TYPE_FORMULAR,
    ], require: FALSE);

    $section[DistributionObjectSettings::XML_TEMPLATE] = [
      '#type' => 'textarea',
      '#title' => $this->t('XML template'),
      '#default_value' => $settings->xmlTemplate,
    ];
    $setStates($section[DistributionObjectSettings::XML_TEMPLATE], [
      DistributionObjectSettings::DISTRIBUTION_TYPE_FORMULAR,
    ]);

    $section[DistributionObjectSettings::XSD_URL] = [
      '#type' => 'textfield',
      '#title' => $this->t('XSD URL'),
      '#default_value' => $settings->xsdUrl,
    ];
    $setStates($section[DistributionObjectSettings::XSD_URL], [
      DistributionObjectSettings::DISTRIBUTION_TYPE_FORMULAR,
    ], require: FALSE);

    return $section;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $setError = static fn(string|array $path, TranslatableMarkup $message) => $form_state->setErrorByName(implode('][',
      (array) $path), $message);

    $value = $form_state->getValue(DistributionContextSettings::NAME)[DistributionContextSettings::ROUTING_MODTAGER_AKTOER] ?? '';
    if (!empty($value)
      && !preg_match('/' . DistributionContextSettings::ROUTING_MODTAGER_AKTOER_PATTERN . '/', (string) $value)) {
      $setError(
        [DistributionContextSettings::NAME, DistributionContextSettings::ROUTING_MODTAGER_AKTOER],
        $this->t('Invalid routing modtager aktør: %value.', ['%value' => $value])
      );
    }

    $value = $form_state->getValue(DistributionContextSettings::NAME)[DistributionContextSettings::KLE_EMNE] ?? '';
    if (!preg_match('/' . DistributionContextSettings::KLE_EMNE_PATTERN . '/', $value)) {
      $setError(
        [DistributionContextSettings::NAME, DistributionContextSettings::KLE_EMNE],
        $this->t('Invalid KLE-emne: %value.', ['%value' => $value])
      );
    }

    $value = $form_state->getValue(DistributionContextSettings::NAME)[DistributionContextSettings::HANDLING_FACET] ?? '';
    if (!empty($value)
      && !preg_match('/' . DistributionContextSettings::HANDLING_FACET_PATTERN . '/', (string) $value)) {
      $setError(
        [DistributionContextSettings::NAME, DistributionContextSettings::HANDLING_FACET],
        $this->t('Invalid Handling-facet: %value.', ['%value' => $value])
      );
    }

    $value = $form_state->getValue(DistributionObjectSettings::NAME)[DistributionObjectFilesSettings::RECIPIENT_IT_SYSTEM] ?? '';
    if ($value && !preg_match('/' . DistributionObjectFilesSettings::RECIPIENT_IT_SYSTEM_PATTERN . '/', $value)) {
      $setError(
        [DistributionObjectSettings::NAME, DistributionObjectFilesSettings::RECIPIENT_IT_SYSTEM],
        $this->t('Invalid recipient IT system: %value.', ['%value' => $value])
      );
    }

    $type = $form_state->getValue(DistributionObjectSettings::NAME)[DistributionObjectSettings::DISTRIBUTION_TYPE] ?? '';
    if (DistributionObjectSettings::DISTRIBUTION_TYPE_FORMULAR === $type) {
      $template = (string) ($form_state->getValue(DistributionObjectSettings::NAME)[DistributionObjectSettings::XML_TEMPLATE] ?? NULL);
      try {
        $this->xmlHelper->validateXml($template);
        $this->xmlHelper->validateTemplate($template);
      }
      catch (InvalidXmlException $e) {
        $form_state->setErrorByName(self::SECTION_SF2900 . '][' . DistributionObjectSettings::XML_TEMPLATE,
          $this->t('Invalid XML template: %message.', ['%message' => $e->getMessage()]));
      }

      $url = (string) ($form_state->getValue(DistributionObjectSettings::NAME)[DistributionObjectSettings::XSD_URL] ?? NULL);
      if ($url) {
        $contents = @file_get_contents($url);
        if (FALSE === $contents) {
          $form_state->setErrorByName(self::SECTION_SF2900 . '][' . DistributionObjectSettings::XSD_URL,
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

    foreach ([
      DistributionContextSettings::NAME,
      DistributionObjectSettings::NAME,
               // SenderSettings::NAME,.
    ] as $name) {
      $this->configuration[$name] = $form_state->getValue($name);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    // Run only when submission is completed.
    // @todo Run on update?
    if (!$webform_submission->isCompleted()) {
      return;
    }

    $this->helper->createJob($webform_submission, $this);
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-return void
   */
  public function postDelete(WebformSubmissionInterface $webform_submission) {
    $this->helper->deleteMessages($this, [$webform_submission]);
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-return void
   */
  public function postPurge(array $webform_submissions) {
    $this->helper->deleteMessages($this, $webform_submissions);
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  public function getSummary() {
    $settings = $this->settingsService->getHandlerSettings($this);

    $build = [
      'info' => [
        '#prefix' => '<div>',
        '#suffix' => '</div>',
        '#markup' => $this->t('KLE-emne: %kle_emne; Handling-facet: %handling_facet',
          [
            '%kle_emne' => $settings->distributionContext->kleEmne,
            '%handling_facet' => $settings->distributionContext->handlingFacet,
          ]),
      ],
    ];

    $items = [];

    if ($settings->distributionContext->kleEmne) {
      $items[] = Link::createFromRoute(
        $this->t('Show routing info'),
        'os2forms_fordelingskomponent.routing_info', [
          'webform' => $this->getWebform()->id(),
          'webform_handler' => $this->getHandlerId(),
        ]
      );
    }

    if ($submission = $this->helper->loadLatestSubmission($this->getWebform())) {
      $items[] = Link::createFromRoute(
        $this->t('Preview distribution object'),
        'os2forms_fordelingskomponent.fordelingskomponent_distribution_object.preview', [
          'webform' => $this->getWebform()->id(),
          'webform_handler' => $this->getHandlerId(),
          'webform_submission' => $submission->id(),
        ]
      );

      $items[] = Link::createFromRoute(
        $this->t('Distribution objects'),
        'os2forms_fordelingskomponent.distribution_object.index', [
          'webform' => $this->getWebform()->id(),
          'webform_handler' => $this->getHandlerId(),
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

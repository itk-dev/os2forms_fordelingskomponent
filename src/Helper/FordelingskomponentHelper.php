<?php

namespace Drupal\os2forms_fordelingskomponent\Helper;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\key\KeyRepositoryInterface;
use Drupal\os2forms_fordelingskomponent\Form\SettingsForm;
use Drupal\os2forms_fordelingskomponent\Model\Attachment;
use Drupal\os2forms_fordelingskomponent\Plugin\WebformHandler\WebformHandlerSF2900;
use Drupal\os2web_audit\Service\Logger as AuditLogger;
use Drupal\os2web_key\KeyHelper;
use Drupal\webform\WebformSubmissionInterface;
use ItkDev\Serviceplatformen\Service\SF1601\Serializer;
use ItkDev\Serviceplatformen\Service\SF2900\SF2900;
use ItkDev\Serviceplatformen\SF2900\EnumType\AktoerTypeType;
use ItkDev\Serviceplatformen\SF2900\EnumType\DokumenttypeType;
use ItkDev\Serviceplatformen\SF2900\EnumType\FremdriftType;
use ItkDev\Serviceplatformen\SF2900\EnumType\LivscyklusKodeType;
use ItkDev\Serviceplatformen\SF2900\EnumType\RetningType;
use ItkDev\Serviceplatformen\SF2900\EnumType\VariantRolleType;
use ItkDev\Serviceplatformen\SF2900\StructType\AttributterListeType;
use ItkDev\Serviceplatformen\SF2900\StructType\AttributterType;
use ItkDev\Serviceplatformen\SF2900\StructType\DelAttributterType;
use ItkDev\Serviceplatformen\SF2900\StructType\DistributionDokumentType;
use ItkDev\Serviceplatformen\SF2900\StructType\DokumentRegistreringType;
use ItkDev\Serviceplatformen\SF2900\StructType\RelationsListe;
use ItkDev\Serviceplatformen\SF2900\StructType\TilstandListeType;
use ItkDev\Serviceplatformen\SF2900\StructType\TilstandType;
use ItkDev\Serviceplatformen\SF2900\StructType\UUID_URN;
use ItkDev\Serviceplatformen\SF2900\StructType\VariantAttributterType;
use ItkDev\Serviceplatformen\SF2900\StructType\VariantListeType;
use ItkDev\Serviceplatformen\SF2900\StructType\VariantType;
use ItkDev\Serviceplatformen\SF2900\StructType\VirkningType;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use WsdlToPhp\PackageBase\AbstractStructBase;

/**
 * Fordelingskomponent helper.
 *
 * @template T
 */
final class FordelingskomponentHelper implements LoggerInterface {
  use LoggerTrait;

  public const string ROUTING_MYNDIGHED = 'routing_myndighed';
  public const string KLE_EMNE = 'kle_emne';
  public const string HANDLING_FACET = 'handling_facet';
  public const string KLE_EMNE_PATTERN = '^[0-9]{2}\.[0-9]{2}\.[0-9]{2}$';
  public const string HANDLING_FACET_PATTERN = '^[A-Z,Æ,Ø,Å][0-9][0-9]$';
  public const string TITEL = 'titel';
  public const string BESKRIVELSE = 'beskrivelse';
  public const string BRUGERVENDT_NOEGLE = 'brugervendt_noegle';

  /**
   * Constructor.
   */
  public function __construct(
    private readonly ConfigFactoryInterface $configFactory,
    private readonly EventDispatcherInterface $eventDispatcher,
    #[Autowire(service: 'key.repository')]
    private readonly KeyRepositoryInterface $keyRepository,
    private readonly KeyHelper $keyHelper,
    #[Autowire(service: 'logger.channel.os2forms_fordelingskomponent')]
    private readonly LoggerChannelInterface $logger,
    #[Autowire(service: 'logger.channel.os2forms_fordelingskomponent_submission')]
    private readonly LoggerChannelInterface $submissionLogger,
    #[Autowire(service: 'os2web_audit.logger')]
    private readonly AuditLogger $auditLogger,
  ) {
  }

  /**
   * Get module config.
   */
  public function getModuleConfig(): ImmutableConfig {
    return $this->configFactory->get(SettingsForm::CONFIG_NAME);
  }

  /**
   * Get routing info.
   */
  public function getRoutingInfo(
    string $routingMyndighed,
    string $kleEmne,
    ?string $handlingFacet,
  ): mixed {
    return $this->sf2900()->getModtagerList(
      routingMyndighed: $routingMyndighed,
      routingKLEEmne: $kleEmne,
      routingHandlingFacet: $handlingFacet,
    );
  }

  /**
   * Send journalpost.
   */
  public function sendJournalpost(
    WebformSubmissionInterface $submission,
    Attachment $attachment,
    array $configuration,
    string $brugervendtNoegle,
    string $titel,
    string $beskrivelse,
  ) {
    $msg = sprintf('Fordelingskomponent afsend journalpost.');
    // If the cause is a submission, add webform id to audit logging message.
    $msg .= $submission ? sprintf(' Webform id %s.', $submission->getWebform()->id()) : '';
    $this->auditLogger->info('Fordelingskomponent', $msg);
  }

  /**
   * Send dokument.
   *
   * @return array
   *   [The response, The kombi post message].
   *
   * @phpstan-return array<int, mixed>
   */
  public function sendDokument(
    WebformSubmissionInterface $submission,
    Attachment $attachment,
    array $configuration,
    // @todo Hvad er brugervendt nøgle?
    string $brugervendtNoegle,
    string $titel,
    string $beskrivelse,
  ) {
    $sf2900 = $this->sf2900();
    $sftp = $sf2900->sftp();

    $transactionId = Serializer::createUuid();
    $dokumentFilNavn = $sftp->putContents($attachment->contents, $attachment->filename);

    $virkning = $this->buildVirkning($configuration);

    $id = Serializer::createUuid();
    $fraTidsPunkt = new \DateTime();
    $brevDato = new \DateTime();
    $routingKLEEmne = $configuration[self::KLE_EMNE];
    $registreringItSystem = $configuration[SettingsForm::REGISTRERING_IT_SYSTEM];

    $routingMyndighed = $configuration[self::ROUTING_MYNDIGHED];
    $routingHandlingFacet = $configuration[self::HANDLING_FACET] ?: NULL;
    // @todo This is probably not correct!
    $routingModtagerAktoer = NULL;

    $dokument = new DistributionDokumentType(
      iD: $id,
      kLEEmneForslag: $routingKLEEmne,
      // handlingFacetForslag: null,.
      registrering: new DokumentRegistreringType(
        fraTidsPunkt: SF2900::formatDateTime($fraTidsPunkt),
        livscyklusKode: LivscyklusKodeType::VALUE_OPRETTET,
        registreringItSystem: new UUID_URN($registreringItSystem),
        relationListe: new RelationsListe(
          variantListe: new VariantListeType([
            new VariantType(
              // If we don't clone the “virking", the XML serializer adds an
              // ID and references which SF2900 does not handle.
              virkning: $this->clone($virkning),
              rolle: VariantRolleType::VALUE_VARIANT,
              indeks: '1',
              variantAttributter: new VariantAttributterType(
                variantType: 'PDF',
              ),
              delAttributter: new DelAttributterType(
                delTekst: 'Hele dokumentet',
              ),
            ),
          ]),
        ),
        tilstandsListe: [
          new TilstandListeType(
            tilstand: [
              new TilstandType(
                // @todo Hvad er fremdrift?
                fremdrift: FremdriftType::VALUE_ENDELIGT,
                virkning: $this->clone($virkning),
              ),
            ]
          ),
        ],
        attributListe: new AttributterListeType([
          new AttributterType(
            brugervendtNoegleTekst: $brugervendtNoegle ?? $titel,
            titelTekst: $titel,
            beskrivelseTekst: $beskrivelse,
            dokumenttype: DokumenttypeType::VALUE_ANDEN,
            retning: RetningType::VALUE_UDGAAENDE,
            brevdato: SF2900::formatDate($brevDato),
            virkning: $this->clone($virkning),
          ),
        ]),
      // importTidspunkt: null,
      // brugerRef: null,.
      )
    );

    $response = $sf2900->afsend(
      transactionId: $transactionId,
      document: $dokument,
      routingMyndighed: $routingMyndighed,
      routingKLEEmne: $routingKLEEmne,
      routingHandlingFacet: $routingHandlingFacet,
      routingModtagerAktoer: $routingModtagerAktoer,
      dokumentFilNavn: $dokumentFilNavn,
    );

    $msg = sprintf('Fordelingskomponent afsend dokument.');
    // If the cause is a submission, add webform id to audit logging message.
    $msg .= $submission ? sprintf(' Webform id %s.', $submission->getWebform()->id()) : '';
    $this->auditLogger->info('Fordelingskomponent', $msg);

    return [$response, $sf2900->getLastRequest()];
  }

  /**
   * {@inheritdoc}
   *
   * @param mixed $level
   *   The level.
   * @param string $message
   *   The message.
   * @param array $context
   *   The context.
   *
   * @phpstan-param array<string, mixed> $context
   */
  public function log($level, $message, array $context = []): void {
    $this->logger->log($level, $message, $context);
    // @see https://www.drupal.org/node/3020595
    if (isset($context['webform_submission']) && $context['webform_submission'] instanceof WebformSubmissionInterface) {
      $this->submissionLogger->log($level, $message, $context);
    }
  }

  /**
   * Check if a string is a valid CVR.
   */
  public static function isValidCvr(string $value): bool {
    return (bool) preg_match('/^[0-9]{8}$/', $value);
  }

  /**
   * Check if a string is a valid UUID.
   */
  public static function isValidUuid(string $value): bool {
    return (bool) preg_match('/[a-fA-F0-9]{8}-[a-fA-F0-9]{4}-[a-fA-F0-9]{4}-[a-fA-F0-9]{4}-[a-fA-F0-9]{12}/', $value);
  }

  /**
   * The SF2900 singleton.
   */
  private SF2900 $sf2900;

  /**
   * Get a singleton instance of SF2900.
   */
  private function sf2900(): SF2900 {
    if (!isset($this->sf2900)) {
      $options = $this->getModuleConfig()->get('sf2900');
      $certificateKey = $this->keyRepository->getKey($options['certificate']);
      // @todo Handle other key types?
      $certificates = $this->keyHelper->getCertificates($certificateKey);
      $certificate = implode(PHP_EOL, $certificates);

      $privateKeyKey = $this->keyRepository->getKey($options['sftp']['private_key']);
      $privateKey = $privateKeyKey->getKeyValue();
      $sf2900options = [
        'authority_cvr' => $options['sender_id'],
        'certificate' => $certificate,
        'sftp' => [
          'private_key' => $privateKey,
          // $options['sftp']['private_key_pass'] ?? '',
          // 'private_key_password' => '',
          'username' => $options['sftp']['username'],
        ],
      ];

      $this->sf2900 = new SF2900($this->eventDispatcher, $sf2900options);
    }

    return $this->sf2900;
  }

  /**
   * Get handler settings combined with select module setting.
   *
   * @param \Drupal\os2forms_fordelingskomponent\Plugin\WebformHandler\WebformHandlerSF2900 $handlerSettings
   *   The handler settings.
   *
   * @return array
   *   The combined configuration.
   */
  public function getHandlerConfiguration(
    array|WebformHandlerSF2900 $handlerSettings,
  ): array {
    if ($handlerSettings instanceof WebformHandlerSF2900) {
      $handlerSettings = (array) $handlerSettings->getSetting(WebformHandlerSF2900::SECTION_SF2900);
    }
    $settings = $handlerSettings;
    $options = $this->getModuleConfig()->get('sf2900');

    $settings += [
      FordelingskomponentHelper::ROUTING_MYNDIGHED => $options[FordelingskomponentHelper::ROUTING_MYNDIGHED],
      SettingsForm::REGISTRERING_IT_SYSTEM => $options[SettingsForm::REGISTRERING_IT_SYSTEM],
      SettingsForm::SENDER_ID => $options[SettingsForm::SENDER_ID],
    ];

    return $settings;
  }

  /**
   * Build a Virkning object.
   */
  private function buildVirkning(array $configuration): VirkningType {
    $aktoer = $configuration[SettingsForm::REGISTRERING_IT_SYSTEM];

    return new VirkningType(
      aktoer: new UUID_URN($aktoer),
      aktoerType: AktoerTypeType::VALUE_IT_SYSTEM,
    // fraTidsPunkt: null,
    // tilTidspunkt: null,
    // noteTekst: null,.
    );
  }

  /**
   * Deep clone an object.
   *
   * @param \WsdlToPhp\PackageBase\AbstractStructBase<T> $object
   *   The object to clone.
   *
   * @return \WsdlToPhp\PackageBase\AbstractStructBase<T>
   *   The cloned object.
   */
  private function clone(AbstractStructBase $object): AbstractStructBase {
    return unserialize(serialize($object));
  }

}

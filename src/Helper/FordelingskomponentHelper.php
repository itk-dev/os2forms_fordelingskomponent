<?php

namespace Drupal\os2forms_fordelingskomponent\Helper;

use Digitaliseringskataloget\Sftp\FileContentDescriptorType;
use Digitaliseringskataloget\Sftp\FileDescriptorType;
use Digitaliseringskataloget\Sftp\SFTPDynamicRoutingInfoType;
use Digitaliseringskataloget\Sftp\TechnicalReceipt;
use Digitaliseringskataloget\Sftp\Trigger;
use ItkDev\Serviceplatformen\Service\SF2900\SF2900\SftpHelper;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\file\Entity\File;
use Drupal\file\FileStorageInterface;
use Drupal\key\KeyRepositoryInterface;
use Drupal\os2forms_fordelingskomponent\Exception\Exception;
use Drupal\os2forms_fordelingskomponent\Exception\InvalidAttachmentElementException;
use Drupal\os2forms_fordelingskomponent\Exception\InvalidXmlException;
use Drupal\os2forms_fordelingskomponent\Exception\RuntimeException;
use Drupal\os2forms_fordelingskomponent\Model\Attachment;
use Drupal\os2forms_fordelingskomponent\Model\DistributionFormular;
use Drupal\os2forms_fordelingskomponent\Model\Fordelingskomponent\AnvenderForsendelse;
use Drupal\os2forms_fordelingskomponent\Model\TransactionContext;
use Drupal\os2forms_fordelingskomponent\Model\XmlRenderResult;
use Drupal\os2forms_fordelingskomponent\Repository\AnvenderForsendelseRepository;
use Drupal\os2forms_fordelingskomponent\Settings;
use Drupal\os2forms_fordelingskomponent\Settings\DistributionObjectSettings;
use Drupal\os2forms_fordelingskomponent\Settings\HandlerSettings;
use Drupal\os2web_audit\Service\Logger as AuditLogger;
use Drupal\os2web_key\KeyHelper;
use Drupal\webform\WebformSubmissionInterface;
use ItkDev\Serviceplatformen\Service\SF2900\Serializer;
use ItkDev\Serviceplatformen\Service\SF2900\Event\AfterServiceCallEvent;
use ItkDev\Serviceplatformen\Service\SF2900\SF2900;
use ItkDev\Serviceplatformen\SF2900\EnumType\AktoerTypeType;
use ItkDev\Serviceplatformen\SF2900\EnumType\DokumenttypeType;
use ItkDev\Serviceplatformen\SF2900\EnumType\FremdriftType;
use ItkDev\Serviceplatformen\SF2900\EnumType\JournalPostRolleType;
use ItkDev\Serviceplatformen\SF2900\EnumType\LivscyklusKodeType;
use ItkDev\Serviceplatformen\SF2900\EnumType\RetningType;
use ItkDev\Serviceplatformen\SF2900\EnumType\VariantRolleType;
use ItkDev\Serviceplatformen\SF2900\StructType\AttributterListeType;
use ItkDev\Serviceplatformen\SF2900\StructType\AttributterType;
use ItkDev\Serviceplatformen\SF2900\StructType\DelAttributterType;
use ItkDev\Serviceplatformen\SF2900\StructType\DistributionDokumentType;
use ItkDev\Serviceplatformen\SF2900\StructType\DistributionFormularType;
use ItkDev\Serviceplatformen\SF2900\StructType\DistributionJournalPostType;
use ItkDev\Serviceplatformen\SF2900\StructType\DokumentRegistreringType;
use ItkDev\Serviceplatformen\SF2900\StructType\FordelingsmodtagerListResponseType;
use ItkDev\Serviceplatformen\SF2900\StructType\FordelingsobjektAfsendRequestType;
use ItkDev\Serviceplatformen\SF2900\StructType\FordelingsobjektAfsendResponseType;
use ItkDev\Serviceplatformen\SF2900\StructType\FormularType;
use ItkDev\Serviceplatformen\SF2900\StructType\FormularXMLType;
use ItkDev\Serviceplatformen\SF2900\StructType\JournalNotatEgenskaberType;
use ItkDev\Serviceplatformen\SF2900\StructType\JournalPostRegistreringType;
use ItkDev\Serviceplatformen\SF2900\StructType\JournalPostRelationsListeType;
use ItkDev\Serviceplatformen\SF2900\StructType\JournalPostType;
use ItkDev\Serviceplatformen\SF2900\StructType\MeddelelseType;
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
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Fordelingskomponent helper.
 *
 * @template T
 */
final class FordelingskomponentHelper implements LoggerInterface, EventSubscriberInterface {
  use LoggerTrait;

  private const array FILE_ELEMENT_TYPES = [
    'managed_file',
    'webform_document_file',
    'webform_image_file',
  ];

  private const string ROUTING_V1_0_0 = 'ROUTING_V1_0_0';

  private const string SFTP_MESSAGE_SUCCESS = 'SUCCESS';

  /**
   * The file storage.
   */
  private FileStorageInterface $fileStorage;

  /**
   * The serializer.
   */
  private Serializer $serializer;

  /**
   * Constructor.
   */
  public function __construct(
    private readonly Settings $settings,
    private readonly EventDispatcherInterface $eventDispatcher,
    private readonly XmlHelper $xmlHelper,
    #[Autowire(service: 'key.repository')]
    private readonly KeyRepositoryInterface $keyRepository,
    private readonly KeyHelper $keyHelper,
    private readonly AnvenderForsendelseRepository $anvenderForsendelseRepository,
    EntityTypeManagerInterface $entityTypeManager,
    #[Autowire(service: 'logger.channel.os2forms_fordelingskomponent')]
    private readonly LoggerChannelInterface $logger,
    #[Autowire(service: 'logger.channel.os2forms_fordelingskomponent_submission')]
    private readonly LoggerChannelInterface $submissionLogger,
    #[Autowire(service: 'os2web_audit.logger')]
    private readonly AuditLogger $auditLogger,
  ) {
    $this->fileStorage = $entityTypeManager->getStorage('file');
    $this->serializer = new Serializer();
  }

  /**
   * Get routing info.
   */
  public function getRoutingInfo(HandlerSettings $handlerSettings): ?FordelingsmodtagerListResponseType {
    return $this->sf2900()->getModtagerList(
      routingMyndighed: (string) $handlerSettings->sender->routingMyndighed,
      routingKLEEmne: (string) $handlerSettings->distributionContext->kleEmne,
      routingHandlingFacet: $handlerSettings->distributionContext->handlingFacet,
    );
  }

  /**
   * Build distribution object.
   */
  public function buildDistributionObject(
    WebformSubmissionInterface $submission,
    HandlerSettings $handlerSettings,
    ?Attachment $attachment,
  ): DistributionFormularType|DistributionDokumentType|DistributionJournalPostType {
    $virkning = $this->buildVirkning($handlerSettings);

    $id = Serializer::createUuid();
    $fraTidsPunkt = new \DateTime();
    $brevDato = new \DateTime();

    $type = $handlerSettings->distributionObject->distributionType;
    $distributionObject = match ($type) {
      DistributionObjectSettings::DISTRIBUTION_TYPE_JOURNALPOST => $this->buildDistributionJournalPostType(
        id: $id,
        fraTidsPunkt: $fraTidsPunkt,
        virkning: $virkning,
        handlerSettings: $handlerSettings,
      ),
      DistributionObjectSettings::DISTRIBUTION_TYPE_DOKUMENT => $this->buildDistributionDokumentType(
        id: $id,
        fraTidsPunkt: $fraTidsPunkt,
        brevDato: $brevDato,
        virkning: $virkning,
        submission: $submission,
        handlerSettings: $handlerSettings,
      ),
      DistributionObjectSettings::DISTRIBUTION_TYPE_FORMULAR => $this->buildDistributionFormularType(
        id: $id,
        submission: $submission,
        handlerSettings: $handlerSettings,
        attachment: $attachment,
      ),
      default => throw new Exception(sprintf('Invalid distribution type: %s', $type)),
    };

    return $distributionObject;
  }

  /**
   * Build distribution object for "Journalnotat".
   */
  private function buildDistributionJournalPostType(
    string $id,
    \DateTimeInterface $fraTidsPunkt,
    VirkningType $virkning,
    HandlerSettings $handlerSettings,
  ): DistributionJournalPostType {
    // @todo DO we need a specific “titel" property?
    $titel = $handlerSettings->distributionContext->titel;
    $notat = $handlerSettings->distributionObject->journalpostMessage;

    return new DistributionJournalPostType(
      iD: $id,
      kLEEmneForslag: $handlerSettings->distributionContext->kleEmne,
      handlingFacetForslag: $handlerSettings->distributionContext->handlingFacet,
      registrering: new JournalPostRegistreringType(
        fraTidsPunkt: SF2900::formatDateTime($fraTidsPunkt),
        livscyklusKode: LivscyklusKodeType::VALUE_OPRETTET,
        registreringItSystem: new UUID_URN($handlerSettings->sender->registreringItSystem),
        relationListe: new JournalPostRelationsListeType([
          new JournalPostType(
            virkning: $virkning,
            rolle: JournalPostRolleType::VALUE_JOURNALPOST,
            // @todo What is "indeks"?
            indeks: '1',
            journalnotatAttributter: new JournalNotatEgenskaberType(
              notat: $notat,
              titel: $titel,
            )
          ),
        ])
      )
    );
  }

  /**
   * Build distribution object for "Dokument".
   */
  private function buildDistributionDokumentType(
    string $id,
    \DateTimeInterface $fraTidsPunkt,
    \DateTimeInterface $brevDato,
    VirkningType $virkning,
    WebformSubmissionInterface $submission,
    HandlerSettings $handlerSettings,
  ): DistributionDokumentType {
    return new DistributionDokumentType(
      iD: $id,
      kLEEmneForslag: $handlerSettings->distributionContext->kleEmne,
      registrering: new DokumentRegistreringType(
        fraTidsPunkt: SF2900::formatDateTime($fraTidsPunkt),
        livscyklusKode: LivscyklusKodeType::VALUE_OPRETTET,
        registreringItSystem: new UUID_URN($handlerSettings->sender->registreringItSystem),
        relationListe: new RelationsListe(
          variantListe: new VariantListeType([
            new VariantType(
            // If we don't clone the “virking", the XML serializer adds an
            // ID and references which SF2900 does not handle.
              virkning: $this->cloneVirkning($virkning),
              rolle: VariantRolleType::VALUE_VARIANT,
              indeks: '1',
              variantAttributter: new VariantAttributterType(
              // @todo What to use here?
                variantType: Attachment::FORMAT_NAME_PDF,
              ),
              delAttributter: new DelAttributterType(
              // @todo What to use here?
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
                virkning: $this->cloneVirkning($virkning),
              ),
            ]
          ),
        ],
        attributListe: new AttributterListeType([
          new AttributterType(
            brugervendtNoegleTekst: $handlerSettings->distributionObject->brugervendtNoegle,
            titelTekst: $handlerSettings->distributionContext->titel,
            beskrivelseTekst: $handlerSettings->distributionContext->beskrivelse,
            // @todo What to use here?
            dokumenttype: DokumenttypeType::VALUE_ANDEN,
            retning: RetningType::VALUE_UDGAAENDE,
            brevdato: SF2900::formatDate($brevDato),
            virkning: $this->cloneVirkning($virkning),
          ),
        ]),
      // importTidspunkt: null,
      // brugerRef: null,.
      ),
      handlingFacetForslag: $handlerSettings->distributionContext->handlingFacet
    );
  }

  /**
   * Build distribution object for "Formular".
   */
  private function buildDistributionFormularType(
    string $id,
    WebformSubmissionInterface $submission,
    HandlerSettings $handlerSettings,
    Attachment $attachment,
  ): DistributionFormular {
    $files = $this->buildFileGroups($handlerSettings, $submission);
    $renderResult = $this->renderXml($handlerSettings, $submission, $files);
    if ($renderResult->exception) {
      throw $renderResult->exception;
    }

    $xml = (string) $renderResult->rendered;
    $xsdUrl = $handlerSettings->distributionObject->xsdUrl;

    $this->xmlHelper->validateXml($xml);
    if (!empty($xsdUrl)) {
      $this->xmlHelper->validateXml($xml, $xsdUrl, loadXsdContent: TRUE);
    }

    // The attachment must be a PDF.
    $titelTekst = basename($attachment->filename);
    $formatNavn = pathinfo($attachment->filename, PATHINFO_EXTENSION);
    $formularIndhold = base64_encode($attachment->contents);

    // The XML will be embedded in an SOAP:Envelope element, so we have to
    // make sure that the XML declaration is not included when embedding.
    // Passing a DOMDocument to FormularXMLType takes care of this.
    $dom = new \DOMDocument();
    $dom->loadXML($xml);
    $formularXML = new FormularXMLType($dom);

    $meddelelse = new MeddelelseType(
      formularType: $handlerSettings->distributionObject->formularType,
      formular: new FormularType(
        titelTekst: $titelTekst,
        formatNavn: $formatNavn,
        formularIndhold: $formularIndhold,
        formularXML: $formularXML,
      ),
    );

    return (new DistributionFormular(
      iD: $id,
      kLEEmneForslag: $handlerSettings->distributionContext->kleEmne,
      meddelelse: $meddelelse,
      handlingFacetForslag: $handlerSettings->distributionContext->handlingFacet,
    ))
      ->setFileGroups($files);
  }

  /**
   * Render XML.
   */
  public function renderXml(
    HandlerSettings $handlerSettings,
    WebformSubmissionInterface $submission,
    ?array $files,
    bool $validateXml = TRUE,
  ): XmlRenderResult {
    $template = $handlerSettings->distributionObject->xmlTemplate;
    if (empty(trim((string) $template))) {
      throw new RuntimeException('Missing XML template');
    }

    $context = $this->xmlHelper->getRenderContext($handlerSettings, $submission, $files);

    $rendered = NULL;
    $exception = NULL;
    try {
      $rendered = $this->xmlHelper->render($template, $context, validateXml: $validateXml);
    }
    catch (\Exception $e) {
      $exception = $e;
    }

    return new XmlRenderResult(
      template: $template,
      context: $context,
      rendered: $rendered,
      exception: $exception,
    );
  }

  /**
   * Build files for a distribution object.
   *
   * @return array<string, array<int, array{sftp_filename: string, file: \Drupal\file\FileInterface}>>
   *   The file groups.
   */
  public function buildFileGroups(HandlerSettings $handlerSettings, WebformSubmissionInterface $submission): array {
    $groups = [];
    $elements = $submission->getWebform()->getElementsDecodedAndFlattened();
    $fileElements = array_filter($elements,
      static fn(array $element) => in_array($element['#type'] ?? NULL, self::FILE_ELEMENT_TYPES));
    foreach ($fileElements as $type => $_) {
      $groups[$type] = [];
      $values = $submission->getData()[$type] ?? NULL;
      if ($values) {
        /** @var \Drupal\file\FileInterface[] $files */
        $files = $this->fileStorage->loadMultiple((array) $values);
        foreach ($files as $file) {
          $groups[$type][] = [
            'sftp_filename' => $this->getSftpFilename($handlerSettings, $submission, $file->getFilename()),
            'file' => $file,
          ];
        }
      }
    }

    return $groups;
  }

  /**
   * Get SFTP filename.
   */
  private function getSftpFilename(
    HandlerSettings $handlerSettings,
    WebformSubmissionInterface $submission,
    string $filename,
  ): string {
    return implode('_', [
      'os2forms_fordelingskomponent',
      $handlerSettings->handlerId,
      $submission->uuid(),
      $filename,
    ]);
  }

  /**
   * Send dokument.
   *
   * @return array
   *   [The response, The kombi post message].
   *
   * @phpstan-return array<int, mixed>
   */
  public function uploadFiles(
    DistributionFormularType|DistributionDokumentType|DistributionJournalPostType $distributionObject,
    HandlerSettings $handlerSettings,
    WebformSubmissionInterface $submission,
  ): array {
    $sf2900 = $this->sf2900();
    $transactionId = Serializer::createUuid();

    $triggerObjects = [];
    if ($distributionObject instanceof DistributionFormular) {
      $files = $distributionObject->getFileGroups();
      $sftp = $sf2900->sftp();
      $recipientItSystem = NULL;
      if ($handlerSettings->distributionObject->files->recipientItSystemLookUp) {
        $routingInfo = $this->getRoutingInfo($handlerSettings);
        $system = $routingInfo->getSystemer()->getSystem();
        if (1 !== count($system)) {
          throw new \RuntimeException('Cannot find single recipient system');
        }
        $recipientItSystem = $system[0]->getSystemUUID();
      }
      foreach ($files as $items) {
        foreach ($items as $item) {
          /** @var \Drupal\file\Entity\File $file */
          $file = $item['file'];
          $sftp->putFile($file->getFileUri(), $file->getFilename(), $item['sftp_filename']);
          $triggerObject = $this->buildTriggerFile($file, $item['sftp_filename'], $handlerSettings, $submission, $transactionId,
            recipientItSystem: $recipientItSystem);
          $sftp->putContents($triggerObject, $item['sftp_filename'], $item['sftp_filename'] . '.trigger');
          $triggerObjects[] = $triggerObject;
        }
      }
    }

    return $triggerObjects;
  }

  /**
   * Check if all files are delivered.
   *
   * @todo Report back if delivery has failed, i.e. if receipts exist but
   * report errors.
   */
  public function checkFilesDelivered(
    array $triggerObjects,
    WebformSubmissionInterface $submission,
  ): bool {
    $context = [
      'webform_submission' => $submission,
    ];
    foreach ($triggerObjects as $xml) {
      try {
        $trigger = $this->serializer->deserialize($xml, Trigger::class);
        $filename = $trigger->getFileDescriptor()->getFileName();
        if (empty($filename)) {
          throw new \RuntimeException('Cannot get file name');
        }
        $this->debug('Checking file %filename', $context + [
          '%filename' => $filename,
        ]);

        $receipt = $this->sf2900()->sftp()->getContents($filename . '.sftpreceipt', SftpHelper::INCOMING_FOLDER);
        $receipt = $this->serializer->deserialize($receipt, TechnicalReceipt::class);
        $errors = $receipt->getErrorMessage();
        if (!empty($errors)) {
          $error = $errors[0];
          $this->logger->error('Error checking file %filename: %code_description (%code): %description', $context + [
            '%filename' => $filename,
            '%code' => $error->getErrorCode(),
            '%code_description' => $error->getErrorCodeDescription(),
            '%description' => $error->getErrorDescription(),
            'error' => $this->serializer->serialize($error),
          ]);

          throw new \RuntimeException(sprintf('SFTP error for %s: %s', $filename, $error->getErrorCodeDescription()));
        }

        $message = $receipt->getReceipt()->getMessage();

        $this->debug('`Status for file %filename: %status', $context + [
          '%filename' => $filename,
          '%status' => $message,
        ]);
        if (self::SFTP_MESSAGE_SUCCESS !== $message) {
          throw new \RuntimeException(sprintf('Message for %s: %s', $filename, $message));
        }
      }
      catch (\Exception $exception) {
        $this->logger->warning('Error checking file %filename: %message', $context + [
          '%filename' => $filename ?? NULL,
          '%message' => $exception->getMessage(),
          'exception' => $exception,
        ]);
        return FALSE;
      }
    }

    return TRUE;
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
    DistributionFormularType|DistributionDokumentType|DistributionJournalPostType $dokument,
    ?Attachment $attachment,
    HandlerSettings $handlerSettings,
  ) {
    $sf2900 = $this->sf2900();
    $transactionId = Serializer::createUuid();

    $dokumentFilNavn = NULL;
    if ($dokument instanceof DistributionDokumentType) {
      if (NULL === $attachment) {
        throw new InvalidAttachmentElementException(sprintf('Missing attachment for %s', $dokument::class));
      }
      $sftp = $sf2900->sftp();
      $sftpFilename = $this->getSftpFilename($handlerSettings, $submission, $attachment->filename);
      $dokumentFilNavn = $sftp->putContents($attachment->contents, $attachment->filename, $sftpFilename);
      // @todo Create trigger object?
    }

    $this->setTransactionContext($transactionId, new TransactionContext(
      transactionId: $transactionId,
      handlerSettings: $handlerSettings,
      submission: $submission,
    ));

    $response = $sf2900->afsend(
      transactionId: $transactionId,
      document: $dokument,
      routingMyndighed: $handlerSettings->sender->routingMyndighed,
      routingKLEEmne: $handlerSettings->distributionContext->kleEmne,
      routingHandlingFacet: $handlerSettings->distributionContext->handlingFacet,
      routingModtagerAktoer: $handlerSettings->distributionContext->routingModtagerAktoer,
      dokumentFilNavn: $dokumentFilNavn,
    );

    $msg = sprintf('Fordelingskomponent afsend dokument.');
    // If the cause is a submission, add webform id to audit logging message.
    $msg .= sprintf(' Webform id %s.', $submission->getWebform()->id());
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
    return (bool) preg_match('/^[a-fA-F0-9]{8}-[a-fA-F0-9]{4}-[a-fA-F0-9]{4}-[a-fA-F0-9]{4}-[a-fA-F0-9]{12}$/', $value);
  }

  /**
   * The SF2900 singleton.
   */
  private SF2900 $sf2900;

  /**
   * Get a singleton instance of SF2900.
   */
  public function sf2900(): SF2900 {
    if (!isset($this->sf2900)) {
      $settings = $this->settings->getSenderSettings();
      $certificateKey = $this->keyRepository->getKey($settings->certificate);
      // @todo Handle other key types?
      $certificates = $this->keyHelper->getCertificates($certificateKey);
      $certificate = implode(PHP_EOL, $certificates);

      $privateKeyKey = $this->keyRepository->getKey($settings->sftp->privateKey);
      $privateKey = $privateKeyKey->getKeyValue();
      $sf2900options = [
        'test_mode' => $this->settings->getGeneralSettings()->testMode,
        'authority_cvr' => $settings->senderId,
        'certificate' => $certificate,
        'sftp' => [
          'private_key' => $privateKey,
          // @todo Do we need to be able to handle a password here?
          // 'private_key_password' => '',
          'username' => $settings->sftp->username,
        ],
      ];

      $this->sf2900 = new SF2900($this->eventDispatcher, $sf2900options);
    }

    return $this->sf2900;
  }

  /**
   * Build a Virkning object.
   */
  private function buildVirkning(HandlerSettings $handlerSettings): VirkningType {
    $aktoer = $handlerSettings->sender->registreringItSystem;

    return new VirkningType(
      aktoer: new UUID_URN($aktoer),
      aktoerType: AktoerTypeType::VALUE_IT_SYSTEM,
    // fraTidsPunkt: null,
    // tilTidspunkt: null,
    // noteTekst: null,.
    );
  }

  /**
   * Deep clone a virking.
   *
   * @param \ItkDev\Serviceplatformen\SF2900\StructType\VirkningType $virkning
   *   The object to clone.
   *
   * @return \ItkDev\Serviceplatformen\SF2900\StructType\VirkningType
   *   The cloned object.
   */
  private function cloneVirkning(VirkningType $virkning): VirkningType {
    return unserialize(serialize($virkning));
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      // BeforeServiceCallEvent::class => 'beforeServiceCall',.
      AfterServiceCallEvent::class => 'afterServiceCall',
    ];
  }

  /**
   * AfterServiceCallEvent event handler.
   */
  public function afterServiceCall(AfterServiceCallEvent $event): void {
    $request = $event->getRequest();
    $response = $event->getResponse();

    if ($request instanceof FordelingsobjektAfsendRequestType) {
      assert($response instanceof FordelingsobjektAfsendResponseType);
      $anvenderTransaktionsId = $request->getAnmodning()->getDistributionContext()->getAnvenderTransaktionsID();
      $context = $this->getTransactionContext($anvenderTransaktionsId);
      if (NULL !== $context) {
        $this->anvenderForsendelseRepository->save(
          new AnvenderForsendelse(
            webformId: $context->submission->getWebform()->id(),
            webformHandlerId: $context->handlerSettings->handlerId,
            webformSubmissionId: $context->submission->id(),
            anvenderTransaktionsId: $anvenderTransaktionsId,
            request: $request,
            distributionTransaktionsId: $response->getDistributionContext()->getDistributionTransktionsID(),
            response: $response
          )
        );
      }
    }
  }

  /**
   * The transaction contexts.
   *
   * @var array<string, TransactionContext>
   */
  private array $transactionContexts = [];

  /**
   * Set transaction context.
   */
  private function setTransactionContext(
    string $transactionId,
    TransactionContext $transactionContext,
  ) {
    $this->transactionContexts[$transactionId] = $transactionContext;
  }

  /**
   * Get transaction context.
   */
  private function getTransactionContext(
    string $transactionId,
  ): ?TransactionContext {
    return $this->transactionContexts[$transactionId] ?? NULL;
  }

  /**
   * Build trigger file.
   *
   * @see https://rimi-itk.github.io/digitaliseringskataloget.dk/digitaliseringskataloget.dk/sf1415/0.6/Integrationsbeskrivelse_SF1415.pdf
   */
  private function buildTriggerFile(
    File $file,
    string $sftpFilename,
    HandlerSettings $handlerSettings,
    WebformSubmissionInterface $submission,
    string $transactionId,
    ?string $recipientItSystem = NULL,
  ): string {
    $trigger = new Trigger();
    $trigger->setFileDescriptor(
      (new FileDescriptorType())
        ->setFileName($sftpFilename)
        ->setSizeInBytes($file->getSize())
        ->setSender($handlerSettings->sender->sftp->username)
        ->setSendersFileId($file->uuid())
        ->setRecipients([self::ROUTING_V1_0_0])
    );

    $infRef = $handlerSettings->distributionObject->files->filspecifikation;
    $senderItSystem = $handlerSettings->sender->registreringItSystem;
    $senderAuthority = sprintf('urn:oio:cvr-nr:%08d', $handlerSettings->sender->routingMyndighed);

    $recipientItSystem = trim((string) ($recipientItSystem ?? $handlerSettings->distributionObject->files->recipientItSystem));
    $recipientAuthority = trim($handlerSettings->distributionObject->files->recipientAuthority);
    if ('' === $recipientAuthority) {
      $recipientAuthority = $handlerSettings->sender->routingMyndighed;
    }
    $recipientAuthority = sprintf('urn:oio:cvr-nr:%08d', $recipientAuthority);

    $routingInfo = (new SFTPDynamicRoutingInfoType())
      ->setInfRef($infRef)
      ->setSenderItSystem($senderItSystem)
      ->setSenderAuthority($senderAuthority)
      ->setTransactionId($transactionId)
      ->setSenderTimestamp(new \DateTime())
      ->setRecipientAuthority($recipientAuthority);
    if (!empty($recipientItSystem)) {
      $routingInfo->setRecipientItSystem($recipientItSystem);
    }
    $trigger->setFileContentDescriptor(
      (new FileContentDescriptorType())
        ->setSFTPDynamicRoutingInfo($routingInfo)
    );

    $xml = $this->serializer->serialize($trigger);

    try {
      $this->xmlHelper->validateXml($xml,
        'module://os2forms_fordelingskomponent/resources/ServiceContract-SFTP-20230926/xsd/SFTPTypes.xsd');
    }
    catch (InvalidXmlException $e) {
      $this->logger->error('Invalid XML in trigger file: %message.', [
        'webform_submission' => $submission,
        '%message' => $e->getMessage(),
        'exception' => $e,
      ]);
      throw new RuntimeException(sprintf('Invalid XML in trigger file: %s', $e->getMessage()));
    }

    return $xml;
  }

}

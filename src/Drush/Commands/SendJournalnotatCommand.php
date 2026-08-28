<?php

declare(strict_types=1);

namespace Drupal\os2forms_fordelingskomponent\Drush\Commands;

use Drupal\os2forms_fordelingskomponent\Settings\SenderSettings;
use ItkDev\Serviceplatformen\Service\SF2900\Serializer;
use ItkDev\Serviceplatformen\Service\SF2900\SF2900;
use ItkDev\Serviceplatformen\SF2900\EnumType\AktoerTypeType;
use ItkDev\Serviceplatformen\SF2900\EnumType\JournalPostRolleType;
use ItkDev\Serviceplatformen\SF2900\EnumType\LivscyklusKodeType;
use ItkDev\Serviceplatformen\SF2900\StructType\DistributionJournalPostType;
use ItkDev\Serviceplatformen\SF2900\StructType\JournalNotatEgenskaberType;
use ItkDev\Serviceplatformen\SF2900\StructType\JournalPostRegistreringType;
use ItkDev\Serviceplatformen\SF2900\StructType\JournalPostRelationsListeType;
use ItkDev\Serviceplatformen\SF2900\StructType\JournalPostType;
use ItkDev\Serviceplatformen\SF2900\StructType\UUID_URN;
use ItkDev\Serviceplatformen\SF2900\StructType\VirkningType;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

// phpcs:disable Drupal.Commenting.ClassComment.Missing
#[AsCommand(
  name: 'os2forms-fordelingskomponent:send:journalnotat',
  description: 'Send journalnotat',
)]
final class SendJournalnotatCommand extends AbstractCommand {

  /**
   * {@inheritdoc}
   *
   * @see https://www.drush.org/13.x/commands/
   */
  #[\Override]
  protected function configure(): void {
    $this
      ->addArgument('routingKleEmne', InputArgument::REQUIRED, 'The KLE-emne')
      ->addArgument('routingHandlingFacet', InputArgument::OPTIONAL, 'The routingHandlingFacet')
      ->addOption('titel', NULL, InputOption::VALUE_OPTIONAL, 'The "titel"')
      ->addOption('notat', NULL, InputOption::VALUE_OPTIONAL, 'The "notat"');
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $io = new SymfonyStyle($input, $output);

    $senderSettings = $this->settings->getSenderSettings();
    $routingMyndighed = $senderSettings->routingMyndighed;
    $routingKleEmne = $input->getArgument('routingKleEmne');
    $routingHandlingFacet = $input->getArgument('routingHandlingFacet');
    $titel = $input->getOption('titel') ?? sprintf('Journalnotat %s', (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM));
    $notat = $input->getOption('notat') ?? $titel;

    $transactionId = Serializer::createUuid();
    $document = $this->buildJournalPostType(
      id: $transactionId,
      kLEEmneForslag: $routingKleEmne,
      handlingFacetForslag: $routingHandlingFacet,
      senderSettings: $senderSettings,
      notat: $notat,
      titel: $titel,
    );

    $io->section('Document');
    $io->writeln(json_encode($document, JSON_PRETTY_PRINT));

    $response = $this->helper->sf2900()->afsend(
      transactionId: $transactionId,
      document: $document,
      routingMyndighed: $routingMyndighed,
      routingKLEEmne: $routingKleEmne,
      routingHandlingFacet: $routingHandlingFacet,
    );

    $io->section('Response');
    $io->writeln(json_encode($response, JSON_PRETTY_PRINT));

    return self::SUCCESS;
  }

  /**
   * Build journal post type.
   */
  private function buildJournalPostType(
    string $id,
    string $kLEEmneForslag,
    ?string $handlingFacetForslag,
    SenderSettings $senderSettings,
    string $notat,
    string $titel,
  ) : DistributionJournalPostType {
    $fraTidsPunkt = new \DateTime();
    $virkning = new VirkningType(
      aktoer: new UUID_URN($senderSettings->registreringItSystem),
      aktoerType: AktoerTypeType::VALUE_IT_SYSTEM,
    );

    return new DistributionJournalPostType(
      iD: $id,
      kLEEmneForslag: $kLEEmneForslag,
      handlingFacetForslag: $handlingFacetForslag,
      registrering: new JournalPostRegistreringType(
        fraTidsPunkt: SF2900::formatDateTime($fraTidsPunkt),
        livscyklusKode: LivscyklusKodeType::VALUE_OPRETTET,
        registreringItSystem: new UUID_URN($senderSettings->registreringItSystem),
        relationListe: new JournalPostRelationsListeType([
          new JournalPostType(
            virkning: $virkning,
            rolle: JournalPostRolleType::VALUE_JOURNALPOST,
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

}

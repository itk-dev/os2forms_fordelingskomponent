<?php

declare(strict_types=1);

namespace Drupal\os2forms_fordelingskomponent\Drush\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

// phpcs:disable Drupal.Commenting.ClassComment.Missing
#[AsCommand(
  name: 'os2forms-fordelingskomponent:fordelingsmodtager:list',
)]
final class FordelingsmodtagerListCommand extends AbstractCommand {

  /**
   * {@inheritdoc}
   *
   * @see https://www.drush.org/13.x/commands/
   */
  protected function configure(): void {
    $this
      ->addArgument('routingMyndighed', InputArgument::REQUIRED, 'The routing myndighed')
      ->addArgument('routingKleEmne', InputArgument::REQUIRED, 'The KLE-emne')
      ->addArgument('routingHandlingFacet', InputArgument::OPTIONAL, 'The routingHandlingFacet');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $io = new SymfonyStyle($input, $output);

    $routingMyndighed = $input->getArgument('routingMyndighed');
    $routingKLEEmne = $input->getArgument('routingKleEmne');
    $routingHandlingFacet = $input->getArgument('routingHandlingFacet');

    $info = $this->helper->sf2900()->getModtagerList(
      routingMyndighed: $routingMyndighed,
      routingKLEEmne: $routingKLEEmne,
      routingHandlingFacet: $routingHandlingFacet,
    );
    $io->writeln(json_encode($info, JSON_PRETTY_PRINT));

    return self::SUCCESS;
  }

}

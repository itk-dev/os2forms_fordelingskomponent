<?php

declare(strict_types=1);

namespace Drupal\os2forms_fordelingskomponent\Drush\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
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
  protected function configure(): void {
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $io = new SymfonyStyle($input, $output);

    $io->warning('This command is a no-op');

    return self::SUCCESS;
  }

}

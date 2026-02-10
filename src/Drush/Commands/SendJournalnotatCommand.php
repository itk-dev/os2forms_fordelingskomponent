<?php

declare(strict_types=1);

namespace Drupal\os2forms_fordelingskomponent\Drush\Commands;

use Drupal\os2forms_fordelingskomponent\Helper\FordelingskomponentHelper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

// phpcs:disable Drupal.Commenting.ClassComment.Missing
#[AsCommand(
  name: 'os2forms-fordelingskomponent:send:journalnotat',
  description: 'Send journalnotat',
)]
final class SendJournalnotatCommand extends Command {

  public function __construct(
    private readonly FordelingskomponentHelper $helper,
  ) {
    parent::__construct();
  }

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
    return self::SUCCESS;
  }

}

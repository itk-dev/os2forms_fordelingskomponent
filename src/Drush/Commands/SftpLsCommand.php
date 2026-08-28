<?php

declare(strict_types=1);

namespace Drupal\os2forms_fordelingskomponent\Drush\Commands;

use ItkDev\Serviceplatformen\Service\SF2900\SF2900\SftpHelper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

// phpcs:disable Drupal.Commenting.ClassComment.Missing
#[AsCommand(
  name: 'os2forms-fordelingskomponent:sftp:ls',
  description: 'List files on SFTP server',
)]
final class SftpLsCommand extends AbstractCommand {

  /**
   * {@inheritdoc}
   *
   * @see https://www.drush.org/13.x/commands/
   */
  #[\Override]
  protected function configure(): void {
    $this
      ->addArgument('dir', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'List of directory paths', [SftpHelper::OUTGOING_FOLDER]);
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $io = new SymfonyStyle($input, $output);
    $sftp = $this->helper->sf2900()->sftp();
    $dirs = (array) $input->getArgument('dir');
    foreach ($dirs as $dir) {
      // @todo getFiles does not complain when using an invalid directory …
      $files = $sftp->getFiles($dir);
      $files = array_filter($files, fn (string $file) => !preg_match('/^[.]+$/', $file));
      $io->section($dir);
      foreach ($files as $file) {
        $io->writeln($file);
      }
    }

    return self::SUCCESS;
  }

}

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
  name: 'os2forms-fordelingskomponent:sftp:get',
  description: 'Get file from SFTP server',
)]
final class SftpGetCommand extends AbstractCommand {

  /**
   * {@inheritdoc}
   *
   * @see https://www.drush.org/13.x/commands/
   */
  #[\Override]
  protected function configure(): void {
    $this
      ->addArgument('filename', InputArgument::REQUIRED, 'Name of file to get')
      ->addArgument('dir', InputArgument::OPTIONAL, 'Directory', SftpHelper::OUTGOING_FOLDER);
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $io = new SymfonyStyle($input, $output);
    $sftp = $this->helper->sf2900()->sftp();

    $filename = $input->getArgument('filename');
    $dir = $input->getArgument('dir');

    try {
      $contents = $sftp->getContents($filename, $dir);

      echo $contents;

      return self::SUCCESS;
    }
    catch (\Exception $exception) {
      $io->error($exception->getMessage());

      return self::FAILURE;
    }
  }

}

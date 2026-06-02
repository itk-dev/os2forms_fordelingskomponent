<?php

declare(strict_types=1);

namespace Drupal\os2forms_fordelingskomponent\Drush\Commands;

use _PHPStan_2d0955352\Symfony\Component\Console\Exception\InvalidArgumentException;
use Composer\Console\Input\InputOption;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\os2forms_fordelingskomponent\Helper\FordelingskomponentHelper;
use Drupal\os2forms_fordelingskomponent\Helper\WebformHelperSF2900;
use Drupal\os2forms_fordelingskomponent\Plugin\WebformHandler\WebformHandlerSF2900;
use Drupal\os2forms_fordelingskomponent\Settings;
use Drupal\webform\WebformEntityStorageInterface;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionStorageInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

// phpcs:disable Drupal.Commenting.ClassComment.Missing
#[AsCommand(
  name: 'os2forms-fordelingskomponent:validate-xml',
  description: 'Validate generated XML on submissions',
)]
final class ValidateXmlCommand extends AbstractCommand {

  /**
   * The webform storage.
   */
  private readonly WebformEntityStorageInterface $webformStorage;

  /**
   * The submission starage.
   */
  private readonly WebformSubmissionStorageInterface $submissionStorage;

  public function __construct(
    FordelingskomponentHelper $helper,
    Settings $settings,
    EntityTypeManagerInterface $entityTypeManager,
    private readonly WebformHelperSF2900 $webformHelper,
  ) {
    parent::__construct($helper, $settings);
    $this->webformStorage = $entityTypeManager->getStorage('webform');
    $this->submissionStorage = $entityTypeManager->getStorage('webform_submission');
  }

  /**
   * {@inheritdoc}
   *
   * @see https://www.drush.org/13.x/commands/
   */
  protected function configure(): void {
    $this
      ->addArgument('webform-id', InputArgument::REQUIRED, 'Webform ID')
      ->addArgument('handler-id', InputArgument::REQUIRED, 'Handler ID')
      ->addOption('show-xml', NULL, InputOption::VALUE_NONE, 'Show XML')
      ->addOption('break-on-error', NULL, InputOption::VALUE_OPTIONAL, 'Break on error. If set, terminate after first error.', TRUE);
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    // I long for "invokable commands"
    // (https://symfony.com/doc/7.4/console.html#creating-a-command) ...
    $webformId = $input->getArgument('webform-id');
    $handlerId = $input->getArgument('handler-id');
    $showXml = (bool) $input->getOption('show-xml');
    $breakOnError = filter_var($input->getOption('break-on-error') ?? TRUE, FILTER_VALIDATE_BOOLEAN);

    $io = new SymfonyStyle($input, $output);

    $webform = $this->loadWebform($webformId);
    $handler = $this->getHandler($handlerId, $webform);
    $submissions = $this->loadSubmissions($webform);

    foreach ($submissions as $submission) {
      $preview = $this->webformHelper->renderPreview($handler, $submission);
      $hasErrors = count($preview['exceptions']) > 0;
      if ($hasErrors) {
        foreach ($preview['exceptions'] as $exception) {
          $io->error([$submission->label(), $exception->getMessage()]);
        }
      }
      else {
        $io->success($submission->label());
      }

      if ($showXml) {
        $io->writeln($preview['xml']->rendered);
      }

      if ($hasErrors) {
        if ($breakOnError) {
          return self::FAILURE;
        }
        continue;
      }

    }

    return self::SUCCESS;
  }

  /**
   * Load webform.
   */
  private function loadWebform(string $id): WebformInterface {
    /** @var ?WebformInterface $webform */
    $webform = $this->webformStorage->load($id);

    if (NULL === $webform) {
      throw new InvalidArgumentException(sprintf('Cannot load webform %s', $id));
    }

    return $webform;
  }

  /**
   * Get webform handler.
   */
  private function getHandler(string $handlerId, WebformInterface $webform): WebformHandlerSF2900 {
    try {
      $handler = $webform->getHandler($handlerId);
    }
    catch (\Exception $e) {
      throw new InvalidArgumentException(sprintf('Cannot load handler: %s', $handlerId), previous: $e);
    }
    if (!$handler instanceof WebformHandlerSF2900) {
      throw new InvalidArgumentException(sprintf('Handler must be an instance of %s; found %s', WebformHandlerSF2900::class, $handler::class));
    }

    return $handler;
  }

  /**
   * Load webform submissions.
   *
   * @return \Drupal\webform\WebformSubmissionInterface[]
   *   The submissions.
   */
  private function loadSubmissions(WebformInterface $webform): array {
    return $this->submissionStorage->loadByProperties([
      'webform_id' => $webform->id(),
    ]);
  }

}

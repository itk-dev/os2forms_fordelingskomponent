<?php

declare(strict_types=1);

namespace Drupal\os2forms_fordelingskomponent\Drush\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\os2forms_fordelingskomponent\Helper\FordelingskomponentHelper;
use Drupal\os2forms_fordelingskomponent\Helper\WebformHelperSF2900;
use Drupal\os2forms_fordelingskomponent\Plugin\WebformHandler\WebformHandlerSF2900;
use Drupal\os2forms_fordelingskomponent\Settings;
use Drupal\webform\WebformEntityStorageInterface;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionStorageInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
  #[\Override]
  protected function configure(): void {
    $this
      ->addArgument('webform-id', InputArgument::REQUIRED, 'Webform ID')
      ->addArgument('handler-id', InputArgument::REQUIRED, 'Handler ID')
      ->addOption('show-xml', NULL, InputOption::VALUE_NONE, 'Show XML')
      ->addOption('show-render-context', NULL, InputOption::VALUE_NONE, 'Show render context')
      ->addOption('break-on-error', NULL, InputOption::VALUE_OPTIONAL, 'Break on error. If set, terminate after first error.', FALSE);
  }

  /**
   * {@inheritdoc}
   */
  #[\Override]
  protected function execute(InputInterface $input, OutputInterface $output): int {
    // I long for "invokable commands"
    // (https://symfony.com/doc/7.4/console.html#creating-a-command) ...
    $webformId = $input->getArgument('webform-id');
    $handlerId = $input->getArgument('handler-id');
    $showXml = (bool) $input->getOption('show-xml');
    $showRenderContext = (bool) $input->getOption('show-render-context');
    $breakOnError = filter_var($input->getOption('break-on-error') ?? TRUE, FILTER_VALIDATE_BOOLEAN);

    $io = new SymfonyStyle($input, $output);

    $webform = $this->loadWebform($webformId);
    $handler = $this->getHandler($handlerId, $webform);
    $submissions = $this->loadSubmissions($webform);

    if (0 === count($submissions)) {
      $io->warning(sprintf('No submissions on form %s', $webform->label()));
    }
    else {
      foreach ($submissions as $submission) {
        $preview = $this->webformHelper->renderPreview($handler, $submission);
        $previewUrl = Url::fromRoute('os2forms_fordelingskomponent.fordelingskomponent_distribution_object.preview', [
          'webform' => $webform->id(),
          'webform_handler' => $handler->getHandlerId(),
          'webform_submission' => $submission->id(),
        ])
          ->setAbsolute()
          ->toString(TRUE)->getGeneratedUrl();
        $hasErrors = count($preview['exceptions']) > 0;
        if ($hasErrors) {
          $io->error([
            $submission->label(),
            $previewUrl,
            ...array_map(static fn (\Exception $exception) => $exception->getMessage(), $preview['exceptions']),
          ]);
        }
        else {
          $io->success([
            $submission->label(),
            $previewUrl,
          ]);
        }

        if ($showXml) {
          if (NULL === $preview['xml']->rendered) {
            $io->warning('Cannot render XML');
          }
          else {
            $io->writeln($preview['xml']->rendered);
          }
        }

        if ($showRenderContext) {
          $io->writeln(json_encode($preview['xml']->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        }

        if ($hasErrors) {
          if ($breakOnError) {
            return self::FAILURE;
          }
          continue;
        }
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

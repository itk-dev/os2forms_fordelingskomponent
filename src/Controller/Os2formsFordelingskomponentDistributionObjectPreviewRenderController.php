<?php

declare(strict_types=1);

namespace Drupal\os2forms_fordelingskomponent\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\RendererInterface;
use Drupal\os2forms_fordelingskomponent\Helper\WebformHelperSF2900;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Returns responses for Fordelingskomponent routes.
 */
final class Os2formsFordelingskomponentDistributionObjectPreviewRenderController extends ControllerBase {

  public function __construct(
    private readonly WebformHelperSF2900 $helper,
    private readonly RendererInterface $renderer,
  ) {
  }

  /**
   * Builds the response.
   */
  public function __invoke(WebformInterface $webform, string $webform_handler, WebformSubmissionInterface $submission): Response {
    $handler = $webform->getHandler($webform_handler);
    $handlerSettings = $handler->getSetting('sf2900');

    $exceptions = [];
    $warnings = [];

    // @todo Set these for a better preview in case of errors.
    $xml = NULL;
    $context = [];

    try {
      $distributionObject = $this->helper->buildDistributionObject($submission, $handlerSettings);
    }
    catch (\Exception $exception) {
      $exceptions[] = $exception;
    }

    $build = [
      '#theme' => 'os2forms_fordelingskomponent_distribution_object_preview_render',
      '#webform' => $webform,
      '#handler' => $handler,
      '#handler_settings' => $handlerSettings,
      '#submission' => $submission,
      '#exceptions' => $exceptions,
      '#warnings' => $warnings,
      '#distribution_object' => $distributionObject ?? NULL,
      '#distribution_type' => $handlerSettings['distribution_type'] ?? NULL,
      '#context' => $context,
      '#xml' => $xml,
    ];

    return new Response((string) $this->renderer->renderRoot($build));
  }

}

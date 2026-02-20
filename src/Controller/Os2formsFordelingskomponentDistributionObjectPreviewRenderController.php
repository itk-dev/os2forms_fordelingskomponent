<?php

declare(strict_types=1);

namespace Drupal\os2forms_fordelingskomponent\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\RendererInterface;
use Drupal\os2forms_fordelingskomponent\Helper\WebformHelperSF2900;
use Drupal\os2forms_fordelingskomponent\Helper\XmlHelper;
use Drupal\os2forms_fordelingskomponent\Hook\ThemeHooks;
use Drupal\os2forms_fordelingskomponent\Plugin\WebformHandler\WebformHandlerSF2900;
use Drupal\os2forms_fordelingskomponent\Settings;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for Fordelingskomponent routes.
 */
final class Os2formsFordelingskomponentDistributionObjectPreviewRenderController extends ControllerBase {

  public function __construct(
    private readonly Settings $settings,
    private readonly WebformHelperSF2900 $helper,
    private readonly RendererInterface $renderer,
    private readonly XmlHelper $xmlHelper,
  ) {
  }

  /**
   * Builds the response.
   */
  public function __invoke(WebformInterface $webform, string $webform_handler, WebformSubmissionInterface $submission): Response {
    try {
      $handler = $webform->getHandler($webform_handler);
    }
    catch (\Exception) {
      $handler = NULL;
    }

    if (!$handler instanceof WebformHandlerSF2900) {
      throw new NotFoundHttpException();
    }

    $handlerSettings = $this->settings->getHandlerSettings($handler);

    $exceptions = [];
    $warnings = [];

    $distributionObject = NULL;
    $xml = [];
    try {
      $distributionObject = $this->helper->buildDistributionObject($handlerSettings, $submission);
    }
    catch (\Exception $exception) {
      $exceptions[] = $exception;
    }

    try {
      $xml = $this->helper->renderXml($handlerSettings, $submission, validateXml: FALSE);
    }
    catch (\Throwable) {
      // Silently ignore any errors.
    }

    $build = [
      '#theme' => ThemeHooks::DISTRIBUTION_OBJECT_PREVIEW_RENDER,
      '#webform' => $webform,
      '#handler' => $handler,
      '#handler_settings' => $handlerSettings,
      '#submission' => $submission,
      '#exceptions' => $exceptions,
      '#warnings' => $warnings,
      '#distribution_object' => $distributionObject,
      '#xml' => $xml,
    ];

    return new Response((string) $this->renderer->renderRoot($build));
  }

}

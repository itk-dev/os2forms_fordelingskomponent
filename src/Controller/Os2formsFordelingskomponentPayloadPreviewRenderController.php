<?php

declare(strict_types=1);

namespace Drupal\os2forms_fordelingskomponent\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\RendererInterface;
use Drupal\os2forms_fordelingskomponent\Exception\Exception;
use Drupal\os2forms_fordelingskomponent\Helper\XmlHelper;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Returns responses for Fordelingskomponent routes.
 */
final class Os2formsFordelingskomponentPayloadPreviewRenderController extends ControllerBase {

  public function __construct(
    private readonly XmlHelper $xmlHelper,
    private readonly RendererInterface $renderer,
  ) {
  }

  /**
   * Builds the response.
   */
  public function __invoke(WebformInterface $webform, string $handler, WebformSubmissionInterface $submission): Response {
    $handler = $webform->getHandler($handler);
    $handlerSettings = $handler->getSetting('sf2900');

    $exceptions = [];
    $warnings = [];

    $template = $handlerSettings['xml_template'] ?? NULL;
    if (NULL === $template) {
      // @todo Handle this
    }

    /** @var ?string $xml */
    $xml = NULL;
    try {
      $context = $this->xmlHelper->getRenderContext($handler, $submission);
      $xml = $this->xmlHelper->render($template, $context);

      $this->xmlHelper->validateXml($xml);

      $xsdUrl = $handlerSettings['xsd_url'] ?? NULL;
      if (NULL === $xsdUrl) {
        $warnings[] = new \RuntimeException('XSD URL not defined');
      }
      else {
        $this->xmlHelper->validateXml($xml, $xsdUrl, loadXsdContent: TRUE);
      }
    }
    catch (Exception $e) {
      $exceptions[] = $e;
    }

    $build = [
      '#theme' => 'os2forms_fordelingskomponent_payload_preview_render_xml',
      '#webform' => $webform,
      '#handler' => $handler,
      '#submission' => $submission,
      '#exceptions' => $exceptions,
      '#warnings' => $warnings,
      '#template' => $template,
      '#context' => $context,
      '#xml' => $xml ?? NULL,
    ];

    return new Response((string) $this->renderer->renderRoot($build));
  }

}

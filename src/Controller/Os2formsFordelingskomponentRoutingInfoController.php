<?php

declare(strict_types=1);

namespace Drupal\os2forms_fordelingskomponent\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\os2forms_fordelingskomponent\Helper\FordelingskomponentHelper;
use Drupal\os2forms_fordelingskomponent\Hook\ThemeHooks;
use Drupal\os2forms_fordelingskomponent\Plugin\WebformHandler\WebformHandlerSF2900;
use Drupal\os2forms_fordelingskomponent\Settings;
use Drupal\webform\WebformInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for Fordelingskomponent routes.
 */
final class Os2formsFordelingskomponentRoutingInfoController extends ControllerBase {

  /**
   * Constructor.
   */
  public function __construct(
    private readonly Settings $settings,
    private readonly FordelingskomponentHelper $helper,
  ) {
  }

  /**
   * Builds the response.
   */
  public function __invoke(WebformInterface $webform, string $webform_handler): array {
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
    $info = $this->helper->getRoutingInfo($handlerSettings);

    return [
      '#theme' => ThemeHooks::ROUTING_INFO,
      '#webform' => $webform,
      '#handler' => $handler,
      '#handler_settings' => $handlerSettings,
      '#info' => $info,
      '#return_url' => $webform->toUrl('handlers'),
    ];
  }

}

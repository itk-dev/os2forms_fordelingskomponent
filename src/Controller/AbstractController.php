<?php

declare(strict_types=1);

namespace Drupal\os2forms_fordelingskomponent\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\os2forms_fordelingskomponent\Plugin\WebformHandler\WebformHandlerSF2900;
use Drupal\webform\WebformInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Abstract controller.
 */
abstract class AbstractController extends ControllerBase {

  /**
   * Get handler.
   */
  protected function getHandler(WebformInterface $webform, string $handlerID): WebformHandlerSF2900 {
    try {
      $handler = $webform->getHandler($handlerID);
    }
    catch (\Exception) {
      $handler = NULL;
    }

    if (!$handler instanceof WebformHandlerSF2900) {
      throw new NotFoundHttpException();
    }

    return $handler;
  }

}

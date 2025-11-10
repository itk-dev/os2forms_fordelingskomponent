<?php

declare(strict_types=1);

namespace Drupal\os2forms_fordelingskomponent\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\os2forms_fordelingskomponent\Helper\FordelingskomponentHelper;
use Drupal\os2forms_fordelingskomponent\Plugin\WebformHandler\WebformHandlerSF2900;
use Drupal\webform\WebformInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for Fordelingskomponent routes.
 */
final class Os2formsFordelingskomponentRoutingInfoController extends ControllerBase {

  private EntityStorageInterface $webformStorage;

  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    private readonly FordelingskomponentHelper $helper,
  ) {
    $this->webformStorage = $entityTypeManager->getStorage('webform');
  }

  /**
   * Builds the response.
   */
  public function __invoke(WebformInterface $webform, string $handler): array {
    try {
      $handler = $webform->getHandler($handler);
    }
    catch (\Exception) {
      $handler = NULL;
    }

    if (!$handler instanceof WebformHandlerSF2900) {
      throw new NotFoundHttpException();
    }

    $settings = $this->helper->getHandlerConfiguration($handler->getConfiguration());
    $info = $this->helper->getRoutingInfo(
      routingMyndighed: $settings[FordelingskomponentHelper::ROUTING_MYNDIGHED],
      kleEmne: $settings[FordelingskomponentHelper::KLE_EMNE],
      handlingFacet: $settings[FordelingskomponentHelper::HANDLING_FACET] ?: null,
    );
    return [
      'stuff' => [
        '#prefix' => '<pre>',
        '#suffix' => '<pre>',
        '#markup' => Yaml::encode([
          'webform' => $webform->label(),
          'handler' => $handler->label(),
          'settings' => $settings,
          'info' => json_encode($info->jsonSerialize(), JSON_PRETTY_PRINT),
        ]),
      ],
    ];
  }

}

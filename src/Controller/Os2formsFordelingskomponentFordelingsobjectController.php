<?php

declare(strict_types=1);

namespace Drupal\os2forms_fordelingskomponent\Controller;

use Drupal\os2forms_fordelingskomponent\Repository\AnvenderForsendelseRepository;
use Drupal\webform\WebformInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses ...
 */
final class Os2formsFordelingskomponentFordelingsobjectController extends AbstractController {

  /**
   * Constructor.
   */
  public function __construct(
    private readonly AnvenderForsendelseRepository $repository,
  ) {
  }

  /**
   * Builds the response.
   */
  public function index(WebformInterface $webform, string $webform_handler): Response {
    $handler = $this->getHandler($webform, $webform_handler);
    $items = $this->repository->loadByWebformAndHandler($webform, $handler);

    return new JsonResponse($items);
  }

  /**
   * Builds the response.
   */
  public function show(WebformInterface $webform, string $webform_handler, string $anvender_transaktions_id): Response {
    $handler = $this->getHandler($webform, $webform_handler);

    $item = $this->repository->loadByAnvenderTransaktionsId($anvender_transaktions_id);
    if (NULL === $item || $item->webformId !== $webform->id() || $item->webformHandlerId != $handler->getHandlerId()) {
      throw new NotFoundHttpException();
    }

    return new JsonResponse($item);
  }

}

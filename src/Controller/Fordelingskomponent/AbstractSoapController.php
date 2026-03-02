<?php

namespace Drupal\os2forms_fordelingskomponent\Controller\Fordelingskomponent;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelInterface;
use ItkDev\Serviceplatformen\SF2900\ClassMap;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;

/**
 * Abstract SOAP controller.
 */
abstract class AbstractSoapController extends ControllerBase {
  /**
   * The WSDL URL.
   */
  protected string $wsdl;

  public function __construct(
    #[Autowire(service: 'logger.channel.os2forms_fordelingskomponent')]
    protected readonly LoggerChannelInterface $logger,
  ) {}

  /**
   * Action!
   */
  public function __invoke(): Response {
    $server = new \SoapServer($this->wsdl, [
      'classmap' => ClassMap::get(),
      'features' => SOAP_SINGLE_ELEMENT_ARRAYS,
    ]);

    $server->setObject($this);

    $response = new Response();
    $response->headers->set('content-type', 'application/soap+xml');

    ob_start();
    try {
      $server->handle();
    }
    catch (\Throwable $e) {
      $this->logger->error('SOAP server error: @message', [
        '@message' => $e->getMessage(),
        'exception' => $e,
      ]);
      $server->fault($e->getCode(), $e->getMessage());
    }
    $response->setContent(ob_get_clean());

    // Returning the response will result in
    //
    //   nginx-1   | 2026/03/02 12:46:21 [error] 36#36: *13 upstream sent
    //   duplicate header line: "Content-Length: 302", previous value:
    //   "Content-Length: 302" while reading response header from upstream …
    // .
    $this->sendResponse($response);

    // Ensure nginx and proxy do not cache.
    $response->headers->set('x-accel-buffering', 'no');
    // Ensure browser do not cache.
    $response->headers->set('cache-control', 'no-cache, no-store, private');

    return $response;
  }

  /**
   * Send response.
   */
  private function sendResponse(Response $response): void {
    foreach ($response->headers->all() as $name => $value) {
      header($name . ': ' . reset($value));
    }
    echo $response->getContent();
    exit();
  }

}

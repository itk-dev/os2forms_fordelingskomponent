<?php

declare(strict_types=1);

namespace Drupal\os2forms_fordelingskomponent\Controller\Fordelingskomponent;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\os2forms_fordelingskomponent\Exception\SoapException;
use Drupal\os2forms_fordelingskomponent\Model\Fordelingskomponent\AnvenderKvittering;
use Drupal\os2forms_fordelingskomponent\Repository\AnvenderForsendelseRepository;
use Drupal\os2forms_fordelingskomponent\Repository\AnvenderKvitteringRepository;
use ItkDev\Serviceplatformen\SF2900\StructType\FordelingskvitteringModtagAnvenderRequestType;
use ItkDev\Serviceplatformen\SF2900\StructType\FordelingskvitteringModtagAnvenderResponseType;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Returns responses for Fordelingskomponent routes.
 */
final class FordelingskvitteringModtagController extends AbstractSoapController {
  /**
   * {@inheritdoc}
   */
  protected string $wsdl = 'file://' . __DIR__ . '/../../../resources/sf2900/SF2900_EP_MS1-2/DistributionServiceAnvenderV2.wsdl';

  public function __construct(
    private readonly AnvenderForsendelseRepository $forsendelseRepository,
    private readonly AnvenderKvitteringRepository $kvitteringRepository,
    private readonly TimeInterface $time,
    #[Autowire(service: 'logger.channel.os2forms_fordelingskomponent')]
    LoggerChannelInterface $logger,
  ) {
    parent::__construct($logger);
  }

  /**
   * FordelingskvitteringModtag handler.
   */
  // phpcs:ignore Drupal.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
  public function FordelingskvitteringModtag(
    FordelingskvitteringModtagAnvenderRequestType $request,
  ): FordelingskvitteringModtagAnvenderResponseType {
    $kvittering = $request->getForretningskvittering();
    // @todo Do something with the kvittering.
    $context = $request->getDistributionContext();

    $forsendelse = $this->forsendelseRepository->loadByAnvenderTransaktionsId(
      anvenderTransaktionsId: $context->getAnvenderTransaktionsID(),
      distributionTransaktionsId: $context->getDistributionTransktionsID(),
    );
    if (NULL === $forsendelse) {
      throw new SoapException(sprintf('Forsendelse %s not found.', $context->getAnvenderTransaktionsID()));
    }

    $forsendelse->deliveredAt = $this->time->getRequestTime();
    $this->forsendelseRepository->save($forsendelse);

    $response = new FordelingskvitteringModtagAnvenderResponseType();

    $kvittering = $this->kvitteringRepository->loadByAnvenderTransaktionsId(
      anvenderTransaktionsId: $context->getAnvenderTransaktionsID(),
      distributionTransaktionsId: $context->getDistributionTransktionsID(),
    );
    if (NULL === $kvittering) {
      $kvittering = new AnvenderKvittering(
        anvenderTransaktionsId: $context->getAnvenderTransaktionsID(),
        distributionTransaktionsId: $context->getDistributionTransktionsID(),
        request: $request,
        response: $response,
      );
    }

    $this->kvitteringRepository->save($kvittering);

    return $response;
  }

}

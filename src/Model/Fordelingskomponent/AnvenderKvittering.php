<?php

namespace Drupal\os2forms_fordelingskomponent\Model\Fordelingskomponent;

use ItkDev\Serviceplatformen\SF2900\StructType\FordelingskvitteringModtagAnvenderRequestType;
use ItkDev\Serviceplatformen\SF2900\StructType\FordelingskvitteringModtagAnvenderResponseType;

/**
 * Model for the os2forms_fordelingskomponent_anvender_kvittering table.
 */
final class AnvenderKvittering {

  /**
   * Constructor.
   */
  public function __construct(
    public readonly ?int $id,
    public readonly string $anvenderTransaktionsId,
    public readonly string $distributionTransaktionsId,
    public readonly FordelingskvitteringModtagAnvenderRequestType $request,
    public ?FordelingskvitteringModtagAnvenderResponseType $response = NULL,
    public ?int $createdAt = NULL,
    public ?int $updatedAt = NULL,
  ) {
  }

}

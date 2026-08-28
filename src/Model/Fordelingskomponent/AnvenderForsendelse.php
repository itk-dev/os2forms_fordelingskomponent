<?php

namespace Drupal\os2forms_fordelingskomponent\Model\Fordelingskomponent;

use ItkDev\Serviceplatformen\SF2900\StructType\FordelingsobjektAfsendRequestType;
use ItkDev\Serviceplatformen\SF2900\StructType\FordelingsobjektAfsendResponseType;

/**
 * Model for the os2forms_fordelingskomponent_anvender_forsendelse table.
 */
final class AnvenderForsendelse {

  /**
   * Constructor.
   */
  public function __construct(
    public readonly string $webformId,
    public readonly string $webformHandlerId,
    public readonly int $webformSubmissionId,
    public readonly string $anvenderTransaktionsId,
    public readonly FordelingsobjektAfsendRequestType $request,
    public ?string $distributionTransaktionsId = NULL,
    public ?FordelingsobjektAfsendResponseType $response = NULL,
    public ?int $createdAt = NULL,
    public ?int $updatedAt = NULL,
    public ?int $deliveredAt = NULL,
  ) {
  }

}

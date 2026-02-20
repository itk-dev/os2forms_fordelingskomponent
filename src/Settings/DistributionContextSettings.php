<?php

namespace Drupal\os2forms_fordelingskomponent\Settings;

/**
 * DistributionContext settings.
 *
 * @see https://rimi-itk.github.io/digitaliseringskataloget.dk/digitaliseringskataloget.dk/sf2900/2.4/SF2900%20-%20Fordelingskomponent%20V2.4.pdf#page=34
 */
final class DistributionContextSettings extends AbstractSettings {
  const string NAME = 'distribution_context';

  protected static array $nullableProperties = [
    self::HANDLING_FACET => TRUE,
  ];

  public const string KLE_EMNE_PATTERN = '^[0-9]{2}\.[0-9]{2}\.[0-9]{2}$';
  public const string KLE_EMNE = 'kle_emne';
  public ?string $kleEmne = NULL;

  public const string HANDLING_FACET_PATTERN = '^[A-Z,Æ,Ø,Å][0-9][0-9]$';
  public const string HANDLING_FACET = 'handling_facet';
  public ?string $handlingFacet = NULL;

  public const string BRUGERVENDT_NOEGLE = 'brugervendt_noegle';
  public ?string $brugervendtNoegle = NULL;

  // @todo Use this?
  public const ROUTING_MODTAGER_AKTOER = 'routing_modtager_aktoer';
  public ?string $routingModtagerAktoer = NULL;

  public const string TITEL = 'titel';
  public ?string $titel = NULL;

  public const string BESKRIVELSE = 'beskrivelse';
  public ?string $beskrivelse = NULL;

}

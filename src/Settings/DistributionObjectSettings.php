<?php

namespace Drupal\os2forms_fordelingskomponent\Settings;

use ItkDev\Serviceplatformen\SF2900\EnumType\ObjektTypeType;

/**
 * General DistributionContext settings, i.e. the ones that do not depend o a specific distribution object.
 *
 * @see https://rimi-itk.github.io/digitaliseringskataloget.dk/digitaliseringskataloget.dk/sf2900/2.4/SF2900%20-%20Fordelingskomponent%20V2.4.pdf#page=34
 */
final class DistributionObjectSettings extends AbstractSettings {
  const string NAME = 'distribution_object';

  public const string DISTRIBUTION_TYPE = 'distribution_type';
  public ?string $distributionType = NULL;

  public const string DISTRIBUTION_TYPE_JOURNALPOST = ObjektTypeType::VALUE_JOURNALPOST;
  public const string DISTRIBUTION_TYPE_DOKUMENT = ObjektTypeType::VALUE_DOKUMENT;
  public const string DISTRIBUTION_TYPE_FORMULAR = ObjektTypeType::VALUE_FORMULAR;

  public const string JOURNALPOST_MESSAGE = 'journalpost_message';
  public ?string $journalpostMessage = NULL;

  public const string ATTACHMENT_ELEMENT = 'attachment_element';
  public ?string $attachmentElement = NULL;

  public const string FORMULAR_TYPE = 'formular_type';
  public ?string $formularType = '';

  public const string XML_TEMPLATE = 'xml_template';
  public ?string $xmlTemplate = NULL;

  public const string XSD_URL = 'xsd_url';
  public ?string $xsdUrl = NULL;

}

<?php

namespace Drupal\os2forms_fordelingskomponent\Model;

/**
 * The Document class.
 */
final readonly class XmlRenderResult {

  /**
   * Constructor.
   */
  public function __construct(
    public string $rendered,
    public string $template,
    public array $context,
  ) {
  }

}

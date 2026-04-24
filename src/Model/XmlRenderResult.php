<?php

namespace Drupal\os2forms_fordelingskomponent\Model;

use Drupal\webform\WebformSubmissionInterface;

/**
 * The Document class.
 */
final readonly class XmlRenderResult {

  /**
   * Constructor.
   */
  public function __construct(
    public string $template,
    public array $context,
    public ?string $rendered,
    public ?\Exception $exception,
  ) {
  }

  /**
   * Convert objects in context to arrays.
   */
  public function withContextAsArray(): self {
    $context = $this->context;
    if (($context['submission'] ?? NULL) instanceof WebformSubmissionInterface) {
      $context['submission'] = $context['submission']->toArray(TRUE, TRUE);
    }
    // @todo Convert 'handler' and 'files'
    return new self(
      $this->template,
      $context,
      $this->rendered,
      $this->exception,
    );

  }

}

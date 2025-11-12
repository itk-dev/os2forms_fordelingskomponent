<?php

namespace Drupal\os2forms_fordelingskomponent\Model;

/**
 * The Document class.
 */
final class Attachment {
  public const MIME_TYPE_PDF = 'application/pdf';

  /**
   * Constructor.
   */
  public function __construct(
    readonly public string $contents,
    readonly public string $mimeType,
    readonly public string $filename,
  ) {
  }

  /**
   * Check if this document is a PDF.
   */
  public function isPdf(): bool {
    return static::MIME_TYPE_PDF === $this->mimeType;
  }

}

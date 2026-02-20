<?php

namespace Drupal\os2forms_fordelingskomponent\Model;

/**
 * The Document class.
 */
final readonly class Attachment {
  public const string FORMAT_NAME_PDF = 'PDF';
  public const string MIME_TYPE_PDF = 'application/pdf';

  /**
   * Constructor.
   */
  public function __construct(
    public string $contents,
    public string $mimeType,
    public string $filename,
  ) {
  }

  /**
   * Check if this document is a PDF.
   */
  public function isPdf(): bool {
    return static::MIME_TYPE_PDF === $this->mimeType;
  }

}

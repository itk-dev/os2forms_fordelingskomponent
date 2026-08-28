<?php

namespace Drupal\os2forms_fordelingskomponent\Model;

use ItkDev\Serviceplatformen\SF2900\StructType\DistributionFormularType;

/**
 * DistributionFormularType extended with file groups.
 *
 * @see https://phpstan.org/writing-php-code/phpdoc-types#local-type-aliases
 *
 * @phpstan-type FileGroups array<string, array<int, array{sftp_filename: string, file: \Drupal\file\FileInterface}>>
 */
final class DistributionFormular extends DistributionFormularType {
  /**
   * The files.
   */
  protected array $fileGroups;

  /**
   * Get file groups.
   *
   * @return FileGroups
   *   The file groups.
   */
  public function getFileGroups(): array {
    return $this->fileGroups;
  }

  /**
   * Set file groups.
   *
   * @param FileGroups $fileGroups
   *   The file groups.
   */
  public function setFileGroups(array $fileGroups): static {
    $this->fileGroups = $fileGroups;

    return $this;
  }

}

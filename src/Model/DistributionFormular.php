<?php

namespace Drupal\os2forms_fordelingskomponent\Model;

use ItkDev\Serviceplatformen\SF2900\StructType\DistributionFormularType;

/**
 * DistributionFormularType extended with files.
 */
final class DistributionFormular extends DistributionFormularType {
  /**
   * The files.
   */
  protected DistributionObjectFiles $files;

  /**
   * Get files.
   */
  public function getFiles(): DistributionObjectFiles {
    return $this->files;
  }

  /**
   * Set files.
   */
  public function setFiles(DistributionObjectFiles $files): static {
    $this->files = $files;

    return $this;
  }

}

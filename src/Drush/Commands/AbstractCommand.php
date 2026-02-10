<?php

declare(strict_types=1);

namespace Drupal\os2forms_fordelingskomponent\Drush\Commands;

use Drupal\os2forms_fordelingskomponent\Helper\FordelingskomponentHelper;
use Symfony\Component\Console\Command\Command;

/**
 * Abstract base command.
 */
abstract class AbstractCommand extends Command {

  public function __construct(
    protected readonly FordelingskomponentHelper $helper,
  ) {
    parent::__construct();
  }

}

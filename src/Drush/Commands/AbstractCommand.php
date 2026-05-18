<?php

declare(strict_types=1);

namespace Drupal\os2forms_fordelingskomponent\Drush\Commands;

use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\os2forms_fordelingskomponent\Helper\FordelingskomponentHelper;
use Drupal\os2forms_fordelingskomponent\Settings;
use Symfony\Component\Console\Command\Command;

/**
 * Abstract base command.
 */
abstract class AbstractCommand extends Command {
  use AutowireTrait;

  public function __construct(
    protected readonly FordelingskomponentHelper $helper,
    protected readonly Settings $settings,
  ) {
    parent::__construct();
  }

}

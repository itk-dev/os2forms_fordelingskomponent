<?php

namespace Drupal\os2forms_fordelingskomponent\Repository;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\LoggerChannelInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Abstract repository.
 */
abstract class AbstractRepository {

  public function __construct(
    protected readonly Connection $database,
    protected readonly TimeInterface $time,
    #[Autowire(service: 'logger.channel.os2forms_fordelingskomponent')]
    protected readonly LoggerChannelInterface $logger,
  ) {
  }

}

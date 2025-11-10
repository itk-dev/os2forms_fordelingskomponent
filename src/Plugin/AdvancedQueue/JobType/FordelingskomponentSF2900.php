<?php

namespace Drupal\os2forms_fordelingskomponent\Plugin\AdvancedQueue\JobType;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\advancedqueue\Job;
use Drupal\advancedqueue\JobResult;
use Drupal\advancedqueue\Plugin\AdvancedQueue\JobType\JobTypeBase;
use Drupal\os2forms_fordelingskomponent\Helper\WebformHelperSF2900;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Fordelingskomponent.
 *
 * @AdvancedQueueJobType(
 *   id = "Drupal\os2forms_fordelingskomponent\Plugin\AdvancedQueue\JobType\FordelingskomponentSF2900",
 *   label = @Translation("Fordelingskomponent (sf2900)"),
 *   max_retries = 5,
 *   retry_delay = 60,
 * )
 */
final class FordelingskomponentSF2900 extends JobTypeBase implements ContainerFactoryPluginInterface {
  /**
   * The webform helper.
   *
   * @var \Drupal\os2forms_fordelingskomponent\Helper\WebformHelperSF2900
   */
  private WebformHelperSF2900 $helper;

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $configuration
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get(WebformHelperSF2900::class)
    );
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $configuration
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    WebformHelperSF2900 $helper,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->helper = $helper;
  }

  /**
   * {@inheritdoc}
   */
  public function process(Job $job): JobResult {
    return $this->helper->processJob($job);
  }

}

<?php

namespace Drupal\domain_config_middleware_test;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Overrides the default services with the D9 compatibility layer.
 *
 * See https://www.drupal.org/docs/drupal-apis/services-and-dependency-injection/altering-existing-services-providing-dynamic-services.
 */
class DomainConfigMiddlewareServiceProvider {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // The connection service.
    $version = (int) explode('.', \Drupal::VERSION)[0];

    if ($version < 10 ) {
      $definition = $container->getDefinition('domain_config_test.middleware');
      $definition->setClass('Drupal\domain_config_middleware_test\MiddlewareD9');
    }
  }

}

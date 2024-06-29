<?php

namespace Drupal\domain_config_ui\Site;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Site\MaintenanceMode;

/**
 * Extends core maintenance mode service class.
 *
 * It mainly differs by checking the configuration setting instead of the state
 * value.
 */
class DomainMaintenanceMode extends MaintenanceMode {

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {

    if (!$this->config->get('domain_config_ui.domain_settings')->get('maintenance_mode')) {
      return FALSE;
    }

    if ($route = $route_match->getRouteObject()) {
      if ($route->getOption('_maintenance_access')) {
        return FALSE;
      }
    }

    return TRUE;
  }

}

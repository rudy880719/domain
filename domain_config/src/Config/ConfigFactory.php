<?php

namespace Drupal\domain_config\Config;

use Drupal\Core\Config\ConfigFactory as CoreConfigFactory;

/**
 * Extends core ConfigFactory class to save domain specific configuration.
 */
class ConfigFactory extends CoreConfigFactory {

  /**
   * Domain Cached configuration objects.
   *
   * @var \Drupal\Core\Config\Config[]
   */
  protected $domainCache = [];

  /**
   * {@inheritdoc}
   */
  protected function doLoadMultiple(array $names, $immutable = TRUE) {
    $list = [];

    if (\Drupal::service('domain.negotiator')->getActiveDomain()) {
      foreach ($names as $key => $name) {
        $cache_key = $this->getConfigCacheKey($name, $immutable);
        if (isset($this->domainCache[$cache_key])) {
          $list[$name] = $this->domainCache[$cache_key];
          unset($names[$key]);
        }
      }
    }
    else {
      return parent::doLoadMultiple($names, $immutable);
    }

    // Initialize override information.
    $module_overrides = [];
    if ($immutable) {
      // Only get module overrides if we have configuration to override.
      $module_overrides = $this->loadOverrides($names);
    }

    foreach ($names as $key => $name) {
      $cache_key = $this->getConfigCacheKey($name, $immutable);

      if (!isset($this->cache[$cache_key])) {
        continue;
      }
      $this->cache[$cache_key]->initWithData($this->cache[$cache_key]->getOriginal('', FALSE));

      if ($immutable) {
        if (isset($module_overrides[$name])) {
          $this->cache[$cache_key]->setModuleOverride($module_overrides[$name]);
        }
        if (isset($GLOBALS['config'][$name])) {
          $this->cache[$cache_key]->setSettingsOverride($GLOBALS['config'][$name]);
        }
      }

      $this->propagateConfigOverrideCacheability($cache_key, $name);

      $list[$name] = $this->cache[$cache_key];
      $this->domainCache[$cache_key] = $this->cache[$cache_key];
      unset($names[$key]);
    }

    return array_merge($list, parent::doLoadMultiple($names, $immutable));
  }
}

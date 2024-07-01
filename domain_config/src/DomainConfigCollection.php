<?php

namespace Drupal\domain_config;

use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\domain\DomainInterface;

/**
 * Service description.
 */
class DomainConfigCollection {

  /**
   * The domain entity storage.
   *
   * @var \Drupal\domain\DomainStorageInterface
   */
  protected $domainStorage;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The configuration entity storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $configStorage;

  /**
   * Constructs a DomainConfigCollection object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param StorageInterface $config_storage
   *   The configuration storage.
   */
  public function __construct(StorageInterface $config_storage, EntityTypeManagerInterface $entity_type_manager, LanguageManagerInterface $language_manager) {
    $this->domainStorage = $entity_type_manager->getStorage('domain');
    $this->languageManager = $language_manager;
    $this->configStorage = $config_storage;
  }

  /**
   * Return config overrides across all ( or ony active ) domains.
   *
   * @param array $names
   *   A list of configuration names that are being loaded.
   * @param bool $only_active
   *   Whether to load overrides only for active domains or all.
   *
   * @return array
   *   An array keyed by domain name, then configuration name of override data.
   *   Override data contains a nested array structure of overrides.
   */
  public function loadAllDomainOverrides( array $names, $only_active = FALSE): array {
    $result = [];

    if ($only_active) {
      $domains = $this->domainStorage->loadByProperties(['active' => 1]);
    }
    else {
      $domains = $this->domainStorage->loadMultiple();
    }
    $languages = $this->languageManager->getLanguages();

    foreach ($names as $name) {
      $result[$name] = [];
      /** @var DomainInterface $domain */
      foreach ($domains as $domain) {
        $configName = '';
        foreach ($languages as $language) {
          // Check domain + language overriden configuration.
          $configName = DomainConfigOverrider::getDomainConfigName($name,$domain,$language);
          if ($this->configStorage->exists($configName['langcode'])) {
            $result[$name][$domain->id()][$language->getId()] = $this->configStorage->read($configName['langcode']);
          }
        }
        // Check domain overriden configuration.
        if($this->configStorage->exists($configName['domain'])){
          $result[$name][$domain->id()]['default'] = $this->configStorage->read($configName['langcode']);
        }
      }
    }

    return $result;
  }

}

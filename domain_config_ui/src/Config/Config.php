<?php

namespace Drupal\domain_config_ui\Config;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Config\Config as CoreConfig;
use Drupal\domain_config_ui\DomainConfigUIManager;

/**
 * Extend core Config class to save domain specific configuration.
 */
class Config extends CoreConfig {

  /**
   * The Domain config UI manager.
   *
   * @var \Drupal\domain_config_ui\DomainConfigUIManager
   */
  protected $domainConfigUIManager;

  /**
   * The UUID generator.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuidGenerator;

  /**
   * Set the Domain config UI manager.
   *
   * @param \Drupal\domain_config_ui\DomainConfigUIManager $domain_config_ui_manager
   *   The Domain config UI manager.
   */
  public function setDomainConfigUiManager(DomainConfigUIManager $domain_config_ui_manager) {
    $this->domainConfigUIManager = $domain_config_ui_manager;
  }

  /**
   * Set the UUID generator manager.
   *
   * @param \Drupal\Component\Uuid\UuidInterface $uuid_generator
   *   The UUID generator.
   */
  public function setUuidGenerator(UuidInterface $uuid_generator) {
    $this->uuidGenerator = $uuid_generator;
  }

  /**
   * {@inheritdoc}
   */
  public function save($has_trusted_data = FALSE) {
    // Remember original config name and UUID.
    $originalName = $this->name;
    $originalUuid = $this->getOriginal('uuid', FALSE);

    try {
      // Get domain config name for saving.
      $domainConfigName = $this->getDomainConfigName();

      // If config is new and we are saving domain specific configuration,
      // save with original name so there is always a default configuration.
      if ($this->isNew && $domainConfigName !== $originalName) {
        parent::save($has_trusted_data);
      }

      // Update module override config.
      if ($domainConfigName !== $originalName) {
        // Override UUID.
        $this->set('uuid', $this->hasOverrides('uuid') ? $this->getOriginal('uuid') : $this->uuidGenerator->generate());
        // Update module override config.
        // Useful for config entities because save can be triggered
        // several times.
        $this->setModuleOverride($this->getRawData());
      }
      // Switch to use domain config name and save.
      $this->name = $domainConfigName;
      parent::save($has_trusted_data);
    }
    catch (\Exception $e) {
      // Reset back to original config name if save fails and re-throw.
      $this->name = $originalName;
      throw $e;
    }

    // Reset back to original config name and UUID after saving.
    $this->name = $originalName;
    $this->set('uuid', $originalUuid);

    return $this;
  }

  /**
   * Get the domain config name.
   */
  protected function getDomainConfigName() {
    // Return selected config name.
    return $this->domainConfigUIManager->getSelectedConfigName($this->name);
  }

}

<?php

namespace Drupal\domain_config;

use Drupal\Core\Asset\LibraryDiscoveryCollector;
use Drupal\domain\DomainNegotiatorInterface;

/**
 * Decorates the library.discovery.collector service.
 *
 * The decorated service adds the domain id to the cache id.
 *
 * @package Drupal\domain_config
 */
class DomainConfigLibraryDiscoveryCollector extends LibraryDiscoveryCollector {

  /**
   * The active domain.
   *
   * @var \Drupal\domain\DomainInterface
   */
  protected $domain;

  /**
   * Set a domain.
   *
   * @param \Drupal\domain\DomainNegotiatorInterface $domainNegotiator
   *   The domain negotiator.
   */
  public function setDomainNegotiator(DomainNegotiatorInterface $domainNegotiator) {
    $this->domain = $domainNegotiator->getActiveDomain();
  }

  /**
   * {@inheritdoc}
   */
  protected function getCid() {
    if (!isset($this->cid)) {
      $domain_id = 'null';
      if (!empty($this->domain)) {
        $domain_id = $this->domain->id();
      }
      $this->cid = 'library_info:' . $domain_id . ':' . $this->themeManager->getActiveTheme()->getName();
    }

    return $this->cid;
  }

}

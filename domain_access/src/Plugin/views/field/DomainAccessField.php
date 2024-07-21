<?php

namespace Drupal\domain_access\Plugin\views\field;

use Drupal\views\Plugin\views\field\EntityField;
use Drupal\views\ResultRow;

/**
 * Field handler to present the link an entity on a domain.
 *
 * @ViewsField("domain_access_field")
 */
class DomainAccessField extends EntityField {

  /**
   * {@inheritdoc}
   */
  public function getItems(ResultRow $values) {
    $items = parent::getItems($values);
    // Override the default link generator, which wants to send us to the entity
    // page, not the entity we are looking at.
    $is_link = $this->options['settings']['link'] ?? FALSE;
    if ($is_link) {
      foreach ($items as &$item) {
        $object = $item['raw'];
        $entity = $object->getEntity();
        // Mark the entity as external to force domain URL prefixing.
        $url = $entity->toUrl('canonical', ['external' => TRUE])->toString();
        $domain = $item['rendered']['#options']['entity'];
        $item['rendered']['#type'] = 'markup';
        $item['rendered']['#markup'] = '<a href="' . $domain->buildUrl($url) . '">' . $domain->label() . '</a>';
      }
      uasort($items, [$this, 'sort']);
    }

    return $items;
  }

  /**
   * Sort the domain list, if possible.
   */
  private function sort($a, $b) {
    $domainA = $a['rendered']['#options']['entity'] ?? 0;
    $domainB = $b['rendered']['#options']['entity'] ?? 0;
    if ($domainA !== 0) {
      return ($domainA->getWeight() > $domainB->getWeight()) ? 1 : 0;
    }
    // We don't have a domain object so sort as best we can.
    return strcmp($a['rendered']['#plain_text'], $b['rendered']['#plain_text']);
  }

}

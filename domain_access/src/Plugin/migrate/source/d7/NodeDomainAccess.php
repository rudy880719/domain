<?php

namespace Drupal\domain_access\Plugin\migrate\source\d7;

use Drupal\migrate\Row;
use Drupal\node\Plugin\migrate\source\d7\Node;

/**
 * Drupal 7 Domain access from database.
 *
 * @MigrateSource(
 *   id = "d7_node_domain_access",
 *   source_module = "node"
 * )
 */
class NodeDomainAccess extends Node {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = parent::fields();
    $fields['domain_access_node'] = $this->t("Node Domain Access");
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $nid = $row->getSourceProperty('nid');
    if ($this->getDomainSites($nid) != NULL) {
      $row->setSourceProperty('domain_all_affiliates', 1);
    }
    $row->setSourceProperty('domain_access_node', $this->getDomainTargetIds($nid));
    $row->setSourceProperty('domain_source', $this->getDomainSourceTargetId($nid));
    return parent::prepareRow($row);
  }

  /**
   * Helper method to get the domain site entries.
   *
   * @param int $nid
   *   Nid of the current row.
   *
   * @return mixed
   *   The domain_access entries with realm=domain_site
   */
  private function getDomainSites(int $nid) {
    return $this->select('domain_access', 'da')
      ->fields('da', ['realm'])
      ->condition('da.realm', 'domain_site')
      ->condition('da.nid', $nid)
      ->execute()
      ->fetchCol();
  }

  /**
   * Helper method to get the gids as target ids from d7 domain_access.
   *
   * @param int $nid
   *   Nid of the current row.
   *
   * @return array
   *   returns target ids of domains
   */
  private function getDomainTargetIds(int $nid) {
    $row_source_properties = [];

    $domains = $this->select('domain_access', 'da')
      ->fields('da', ['gid'])
      ->condition('da.realm', 'domain_id')
      ->condition('da.nid', $nid)
      ->execute()
      ->fetchCol();

    foreach ($domains as $domain) {
      $domain_target_ids = $this->select('domain', 'da')
        ->fields('da', ['machine_name'])
        ->condition('da.domain_id', $domain)
        ->execute()
        ->fetchCol();
      $row_source_properties[] = ['target_id' => $domain_target_ids[0]];
    }
    return $row_source_properties;
  }

  /**
   * Helper method to get the gids as target ids from d7 domain_source.
   *
   * @param int $nid
   *   Nid of the current row.
   *
   * @return array
   *   returns target ids of domains
   */
  private function getDomainSourceTargetId(int $nid) {
    $row_source_properties = [];

    $domains = $this->select('domain_source', 'ds')
      ->fields('ds', ['domain_id'])
      ->condition('ds.nid', $nid)
      ->execute()
      ->fetchCol();

    foreach ($domains as $domain) {
      $domain_target_ids = $this->select('domain', 'da')
        ->fields('da', ['machine_name'])
        ->condition('da.domain_id', $domain)
        ->execute()
        ->fetchCol();
      if (!$domain_target_ids) {
        continue;
      }
      $row_source_properties = $domain_target_ids[0];
    }
    return $row_source_properties;
  }

}

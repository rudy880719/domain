<?php

namespace Drupal\domain_access\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\BooleanOperator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Handles matching of current domain.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("domain_access_current_all_filter")
 */
class DomainAccessCurrentAllFilter extends BooleanOperator {

  /**
   * The Domain negotiator.
   *
   * @var \Drupal\domain\DomainNegotiatorInterface
   */
  protected $domainNegotiator;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->domainNegotiator = $container->get('domain.negotiator');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function operators() {
    $operators = parent::operators();
    unset($operators['!=']);
    return $operators;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    if (method_exists($this->query, 'addTable')) {
      $all_table = $this->query->addTable('node__field_domain_all_affiliates', $this->relationship);
      $all_field = $all_table . '.field_domain_all_affiliates_value';
      $real_field = $this->tableAlias . '.' . $this->realField;

      $current_domain = $this->domainNegotiator->getActiveDomain();
      $current_domain_id = $current_domain->id();

      if (is_null($this->value)) {
        $where = "(($real_field <> '$current_domain_id' OR $real_field IS NULL) AND ($all_field = 0 OR $all_field IS NULL))";
        if ($current_domain->isDefault()) {
          $where = "($real_field <> '$current_domain_id' AND ($all_field = 0 OR $all_field IS NULL))";
        }
      }
      else {
        $where = "($real_field = '$current_domain_id' OR $all_field = 1)";
        if ($current_domain->isDefault()) {
          $where = "(($real_field = '$current_domain_id' OR $real_field IS NULL) OR $all_field = 1)";
        }
      }

      if (method_exists($this->query, 'addWhereExpression')) {
        $this->query->addWhereExpression($this->options['group'], $where);
      }
      // This filter causes duplicates.
      $this->query->options['distinct'] = TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = parent::getCacheContexts();

    $contexts[] = 'url.site';

    return $contexts;
  }

}

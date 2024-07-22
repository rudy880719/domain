<?php

namespace Drupal\domain_content\Plugin\search_api\processor;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\node\NodeInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides configuration to include/exclude content by affiliate domain.
 *
 * @SearchApiProcessor(
 *   id = "affiliate_domain",
 *   label = @Translation("Affiliate Domain"),
 *   description = @Translation("Include content by its affiliated domain(s)."),
 *   stages = {
 *     "alter_items" = 0,
 *   },
 * )
 */
class AffiliateDomain extends ProcessorPluginBase implements PluginFormInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $processor = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $processor->setEntityTypeManager($container->get('entity_type.manager'));

    return $processor;
  }

  /**
   * Sets the entity type manager service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   *
   * @return $this
   */
  public function setEntityTypeManager(EntityTypeManagerInterface $entity_type_manager): AffiliateDomain {
    $this->entityTypeManager = $entity_type_manager;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $configuration = parent::defaultConfiguration();

    $configuration += [
      'domains' => [],
    ];

    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $domains = $this->entityTypeManager->getStorage('domain')->loadOptionsList();

    $form['domains'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Affiliate Domain(s)'),
      '#description' => $this->t("Select content's affiliate domain(s) to include in this index. If a content's <em>Send to all affiliates</em> options is selected; it will be included."),
      '#default_value' => $this->configuration['domains'],
      '#options' => $domains,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $value = $form_state->getValue('domains');
    $this->setConfiguration(['domains' => array_keys(array_filter($value))]);
  }

  /**
   * {@inheritdoc}
   */
  public static function supportsIndex(IndexInterface $index) {
    foreach ($index->getDatasources() as $datasource) {
      $entity_type_id = $datasource->getEntityTypeId();
      if (!$entity_type_id) {
        continue;
      }

      // Only supports the node entity type.
      if ($entity_type_id === 'node') {
        return TRUE;
      }

    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function alterIndexedItems(array &$items) {
    $domains = $this->configuration['domains'];

    /** @var \Drupal\search_api\Item\ItemInterface $item */
    foreach ($items as $item_id => $item) {
      $object = $item->getOriginalObject()->getValue();
      $exclude_item = FALSE;
      if ($object instanceof NodeInterface) {
        // If "Send to all affiliates", skip the item to include it in the
        // index.
        if ($object->hasField('field_domain_all_affiliates') && !$object->get('field_domain_all_affiliates')->isEmpty()) {
          if ($object->get('field_domain_all_affiliates')->getValue()[0]['value']) {
            continue;
          }
        }

        // If the item is not associated with the configured domains, exclude it
        // form the index.
        if ($object->hasField('field_domain_access') && !$object->get('field_domain_access')->isEmpty()) {
          $values = array_map(function ($value) {return $value['target_id'];}, $object->get('field_domain_access')->getValue());
          $exclude_item = empty(array_intersect($values, $domains));
        }
      }

      if ($exclude_item) {
        unset($items[$item_id]);
      }
    }
  }

}

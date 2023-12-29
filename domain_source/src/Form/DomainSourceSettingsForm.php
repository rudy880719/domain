<?php

namespace Drupal\domain_source\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a configuration form for managing Domain Source settings.
 *
 * @package Drupal\domain_source\Form
 */
class DomainSourceSettingsForm extends ConfigFormBase {


  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new DomainSourceSettingsForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'domain_source_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['domain_source.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $routes = $this->entityTypeManager->getDefinition('node')->getLinkTemplates();

    $options = [];
    foreach ($routes as $route => $path) {
      // Some parts of the system prepend drupal:, which the routing
      // system doesn't use. The routing system also uses underscores instead
      // of dashes. Because Drupal.
      $route = str_replace(['-', 'drupal:'], ['_', ''], $route);
      $options[$route] = $route;
    }
    $config = $this->config('domain_source.settings');
    $form['exclude_routes'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Disable link rewrites for the selected routes.'),
      '#default_value' => $config->get('exclude_routes') ?: [],
      '#options' => $options,
      '#description' => $this->t('Check the routes to disable. Any entity URL with a Domain Source field will be rewritten unless its corresponding route is disabled.'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('domain_source.settings')
      ->set('exclude_routes', $form_state->getValue('exclude_routes'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}

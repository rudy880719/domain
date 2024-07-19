<?php

namespace Drupal\domain_source\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Settings for the module.
 *
 * @package Drupal\domain_source\Form
 */
class DomainSourceSettingsForm extends ConfigFormBase {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');

    return $instance;
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
      '#default_value' => $config->get('exclude_routes') ?? [],
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

<?php

namespace Drupal\domain\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\domain\DomainToken;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Settings form for the Domain module.
 *
 * @package Drupal\domain\Form
 */
class DomainSettingsForm extends ConfigFormBase {

  /**
   * The domain token handler.
   *
   * @var \Drupal\domain\DomainToken
   */
  protected $domainTokens;

  /**
   * Constructs a DomainSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface|null $typedConfigManager
   *   The typed config manager.
   * @param \Drupal\domain\DomainToken|null $domain_tokens
   *   The domain token handler. See https://www.drupal.org/node/3404140.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    protected $typedConfigManager = NULL,
    DomainToken $domain_tokens = NULL,
  ) {
    parent::__construct($config_factory, $typedConfigManager);
    $this->domainTokens = $domain_tokens;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('domain.token')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'domain_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['domain.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('domain.settings');
    $form['allow_non_ascii'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow non-ASCII characters in domains and aliases'),
      '#default_value' => $config->get('allow_non_ascii'),
      '#description' => $this->t('Domains may be registered with international character sets. Note that not all DNS server respect non-ascii characters.'),
    ];
    $form['www_prefix'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Ignore www prefix when negotiating domains'),
      '#default_value' => $config->get('www_prefix'),
      '#description' => $this->t('Domain negotiation will ignore any www prefixes for all requests.'),
    ];
    // Get the usable tokens for this field.
    $patterns = [];
    foreach ($this->domainTokens->getCallbacks() as $key => $callback) {
      $patterns[] = "[domain:$key]";
    }
    $form['css_classes'] = [
      '#type' => 'textfield',
      '#size' => 80,
      '#title' => $this->t('Custom CSS classes'),
      '#default_value' => $config->get('css_classes'),
      '#description' => $this->t('Enter any CSS classes that should be added to the &lt;body&gt; tag. Available replacement patterns are: @patterns', [
        '@patterns' => implode(', ', $patterns),
      ]),
    ];
    $form['login_paths'] = [
      '#type' => 'textarea',
      '#rows' => 5,
      '#columns' => 40,
      '#title' => $this->t('Paths that should be accessible for inactive domains'),
      '#default_value' => $config->get('login_paths'),
      '#description' => $this->t('Inactive domains are only accessible to users with permission.
        Enter any paths that should be accessible, one per line. Normally, only the
        login path will be allowed.'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('domain.settings');
    foreach ($this->settingsKeys() as $key) {
      $config->set($key, $form_state->getValue($key));
    }
    $config->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * Returns an array of settings keys.
   */
  public function settingsKeys() {
    return [
      'allow_non_ascii',
      'www_prefix',
      'login_paths',
      'css_classes',
    ];
  }

}

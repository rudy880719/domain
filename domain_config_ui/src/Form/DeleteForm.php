<?php

namespace Drupal\domain_config_ui\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\domain_config_ui\Controller\DomainConfigUIController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class DeleteForm.
 */
class DeleteForm extends FormBase {

  /**
   * Logger channel factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Provides the Drupal messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Logger channel factory service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Constructs a DomainDeleteForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   Logger channel factory service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Provides the Drupal messenger service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   Logger channel factory service.
   */
  public function __construct(ConfigFactory $configFactory, MessengerInterface $messenger, LoggerChannelFactoryInterface $loggerFactory) {
    $this->configFactory = $configFactory;
    $this->messenger = $messenger;
    $this->loggerFactory = $loggerFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('messenger'),
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'domain_config_ui_delete';
  }

  /**
   * Build configuration form with metadata and values.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $config_name = NULL) {
    if (empty($config_name)) {
      $url = Url::fromRoute('domain_config_ui.list');
      return new RedirectResponse($url->toString());
    }

    $elements = DomainConfigUIController::deriveElements($config_name);
    $config = $this->configFactory->get($config_name)->getRawData();

    $form['help'] = [
      '#type' => 'item',
      '#title' => Html::escape($config_name),
      '#markup' => $this->t('Are you sure you want to delete the configuration
        override: %config_name?', ['%config_name' => $config_name]),
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];
    if ($elements['language'] === $this->t('all')->render()) {
      $language = $this->t('all languages');
    }
    else {
      $language = $this->t('the @language language.', ['@language' => $elements['language']]);
    }
    $form['more_help'] = [
      '#markup' => $this->t('This configuration is for the %domain domain and
        applies to %language.', [
          '%domain' => $elements['domain'],
          '%language' => $language,
        ]
      ),
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];
    $form['review'] = [
      '#type' => 'details',
      '#title' => $this->t('Review settings'),
      '#open' => FALSE,
    ];
    $form['review']['text'] = [
      '#markup' => DomainConfigUIController::printArray($config),
    ];
    $form['config_name'] = ['#type' => 'value', '#value' => $config_name];
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete configuration'),
      '#button_type' => 'primary',
    ];
    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => new Url('domain_config_ui.list'),
      '#attributes' => [
        'class' => [
          'button',
        ],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $name = $form_state->getValue('config_name');
    $message = $this->t('Domain configuration %label has been deleted.', ['%label' => $name]);
    $this->messenger->addMessage($message);
    $this->loggerFactory->get('domain_config')->notice($message);
    $this->configFactory()->getEditable($name)->delete();
    $form_state->setRedirectUrl(new Url('domain_config_ui.list'));
  }

}

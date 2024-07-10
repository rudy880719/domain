<?php

namespace Drupal\domain;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * {@inheritdoc}
 */
class DomainNegotiator implements DomainNegotiatorInterface {

  /**
   * The HTTP_HOST value of the request.
   *
   * @var string
   */
  protected $httpHost;

  /**
   * The domain record returned by the lookup request.
   *
   * @var \Drupal\domain\DomainInterface
   */
  protected $domain;

  /**
   * The domain storage class.
   *
   * @var \Drupal\domain\DomainStorageInterface|null
   */
  protected $domainStorage;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The request stack object.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;


  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a DomainNegotiator object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack object.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(RequestStack $requestStack, ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory) {
    $this->requestStack = $requestStack;
    $this->moduleHandler = $module_handler;
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function setRequestDomain($httpHost, $reset = FALSE) {
    // @todo Investigate caching methods.
    $this->setHttpHost($httpHost);
    // Try to load a direct match.
    $domain = $this->domainStorage()->loadByHostname($httpHost);
    if (!is_null($domain)) {
      // If the load worked, set an exact match flag for the hook.
      $domain->setMatchType(DomainNegotiatorInterface::DOMAIN_MATCHED_EXACT);
    }
    // If a straight load fails, create a base domain for checking. This data
    // is required for hook_domain_request_alter().
    else {
      $values = ['hostname' => $httpHost];
      /** @var \Drupal\domain\DomainInterface $domain */
      $domain = $this->domainStorage()->create($values);
      $domain->setMatchType(DomainNegotiatorInterface::DOMAIN_MATCHED_NONE);
    }

    // Now check with modules (like Domain Alias) that register alternate
    // lookup systems with the main module.
    $this->moduleHandler->alter('domain_request', $domain);

    // We must have registered a valid id, else the request made no match.
    if (!is_null($domain->id())) {
      $this->setActiveDomain($domain);
    }
    // Fallback to default domain if no match.
    else {
      $domain = $this->domainStorage()->loadDefaultDomain();
      if ($domain instanceof DomainInterface) {
        $this->moduleHandler->alter('domain_request', $domain);
        $domain->setMatchType(DomainNegotiatorInterface::DOMAIN_MATCHED_NONE);
        if (!is_null($domain->id())) {
          $this->setActiveDomain($domain);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setActiveDomain(DomainInterface $domain) {
    // @todo caching
    $this->domain = $domain;
  }

  /**
   * Determine the active domain.
   */
  protected function negotiateActiveDomain() {
    $httpHost = $this->negotiateActiveHostname();
    $this->setRequestDomain($httpHost);
    return $this->domain;
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveDomain($reset = FALSE) {
    if ($reset) {
      $this->negotiateActiveDomain();
    }
    return $this->domain;
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveId() {
    return $this->domain->id();
  }

  /**
   * {@inheritdoc}
   */
  public function negotiateActiveHostname() {
    $request = $this->requestStack->getCurrentRequest();
    if (!is_null($request)) {
      $httpHost = $request->getHttpHost();
    }
    else {
      $httpHost = $_SERVER['HTTP_HOST'] ?? NULL;
    }
    $hostname = $httpHost ?? 'localhost';

    return $this->domainStorage()->prepareHostname($hostname);
  }

  /**
   * {@inheritdoc}
   */
  public function setHttpHost($httpHost) {
    $this->httpHost = $httpHost;
  }

  /**
   * {@inheritdoc}
   */
  public function getHttpHost() {
    return $this->httpHost;
  }

  /**
   * {@inheritdoc}
   */
  public function isRegisteredDomain($hostname) {
    // Direct hostname match always passes.
    $domain = $this->domainStorage()->loadByHostname($hostname);
    if ($domain instanceof DomainInterface) {
      return TRUE;
    }
    // Check for registered alias matches.
    $values = ['hostname' => $hostname];
    /** @var \Drupal\domain\DomainInterface $domain */
    $domain = $this->domainStorage()->create($values);
    $domain->setMatchType(DomainNegotiatorInterface::DOMAIN_MATCHED_NONE);

    // Now check with modules (like Domain Alias) that register alternate
    // lookup systems with the main module.
    $this->moduleHandler->alter('domain_request', $domain);

    // We must have registered a valid id, else the request made no match.
    if (!is_null($domain->id())) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Retrieves the domain storage handler.
   *
   * @return \Drupal\domain\DomainStorageInterface
   *   The domain storage handler.
   */
  protected function domainStorage() {
    if (is_null($this->domainStorage)) {
      $this->domainStorage = $this->entityTypeManager->getStorage('domain');
    }
    return $this->domainStorage;
  }

}

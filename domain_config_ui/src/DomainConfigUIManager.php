<?php

namespace Drupal\domain_config_ui;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Domain Config UI manager.
 */
class DomainConfigUIManager implements DomainConfigUIManagerInterface {

  /**
   * A RequestStack instance.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * Constructs DomainConfigUIManager object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(RequestStack $request_stack) {
    // We want the currentRequest, but it is not always available.
    // https://www.drupal.org/project/domain/issues/3004243#comment-13700917
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public function getSelectedConfigName($name, $omit_language = FALSE) {
    $domain_id = $this->getSelectedDomainId();

    if (!is_null($domain_id)) {
      $prefix = "domain.config.{$domain_id}.";
      $langcode = $this->getSelectedLanguageId();
      if (!$omit_language && strlen($langcode) > 0) {
        $prefix .= "{$langcode}.";
      }
      return $prefix . $name;
    }

    return $name;
  }

  /**
   * {@inheritdoc}
   */
  public function getSelectedDomainId() {
    $id = NULL;

    $request = $this->getRequest();
    if (!is_null($request)) {
      $id = $this->currentRequest->get('domain_config_ui_domain') ?? NULL;
    }
    if (is_null($id) && isset($_SESSION['domain_config_ui_domain'])) {
      $id = $_SESSION['domain_config_ui_domain'];
    }

    return $id;
  }

  /**
   * {@inheritdoc}
   */
  public function getSelectedLanguageId() {
    $id = NULL;

    $request = $this->getRequest();
    if (!is_null($request)) {
      $id = $this->currentRequest->get('domain_config_ui_language') ?? NULL;
    }
    if (is_null($id) && isset($_SESSION['domain_config_ui_language'])) {
      $id = $_SESSION['domain_config_ui_language'];
    }

    return $id;
  }

  /**
   * Ensures that the currentRequest is loaded.
   *
   * @return \Symfony\Component\HttpFoundation\Request|null
   *   The current request object.
   */
  private function getRequest() {
    if (!isset($this->currentRequest)) {
      $this->currentRequest = $this->requestStack->getCurrentRequest();
    }

    return $this->currentRequest;
  }

}

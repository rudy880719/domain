<?php

namespace Drupal\domain\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Url;
use Drupal\domain\DomainInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller routines for AJAX callbacks for domain actions.
 */
class DomainController {

  use StringTranslationTrait;

  /**
   * Handles AJAX operations from the overview form.
   *
   * @param \Drupal\domain\DomainInterface $domain
   *   A domain record object.
   * @param string|NULL $op
   *   The operation being performed, either 'default' to make the domain record
   *   the default, 'enable' to enable the domain record, or 'disable' to
   *   disable the domain record.
   *
   *   Note: The delete action is handled by the entity form system.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response to redirect back to the domain record list.
   *   Supported by the UrlGeneratorTrait.
   *
   * @see \Drupal\domain\DomainListBuilder
   */
  public function ajaxOperation(DomainInterface $domain, $op = NULL) {
    $success = FALSE;
    switch ($op) {
      case 'default':
        $domain->saveDefault();
        $message = $this->t('Domain record set as default');
        if ($domain->isDefault()) {
          $success = TRUE;
        }
        break;

      case 'enable':
        $domain->enable();
        $message = $this->t('Domain record has been enabled.');
        if ($domain->status()) {
          $success = TRUE;
        }
        break;

      case 'disable':
        $domain->disable();
        $message = $this->t('Domain record has been disabled.');
        if (!$domain->status()) {
          $success = TRUE;
        }
        break;
    }

    // Set a message.
    if ($success) {
      drupal_set_message($message);
    }
    else {
      drupal_set_message($this->t('The operation failed.'));
    }

    // Return to the invoking page.
    $url = Url::fromRoute('domain.admin', array(), array('absolute' => TRUE));
    return new RedirectResponse($url->toString(), 302);
  }

  /**
   * Handles route permissions on the domain list page.
   *
   * This method probably doesn't belong here, but we don't have a simple means
   * for checking OR behavior on a route. Since this controller has no
   * dependencies, it is ideal for a static callback.
   *
   * @return \Drupal\Core\Access\AccessResult
   */
  public static function viewDomainList() {
    $account = \Drupal::currentUser();
    if ($account->hasPermission('administer domains') || $account->hasPermission('view domain list')) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

}

<?php

namespace Drupal\domain_content\Controller;

use Drupal\Core\Link;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Url;
use Drupal\domain\DomainInterface;
use Drupal\domain_access\Access\DomainAccessViewsAccess;
use Drupal\domain_access\DomainAccessManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller routines domain content pages.
 */
class DomainContentController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity query.
   *
   * @var \Drupal\Core\Entity\Query\QueryInterface
   */
  protected $entityQuery;

  /**
   * The domain access manager.
   *
   * @var \Drupal\domain_access\Access\DomainAccessViewsAccess
   */
  protected $domainAccessManager;

  /**
   * Constructs a new DomainContentController.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\Query\QueryInterface $entityQuery
   *   The entity query.
   * @param \Drupal\domain_access\Access\DomainAccessViewsAccess $domainAccessManager
   *   The domain access manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager,
  QueryInterface $entityQuery,
  DomainAccessViewsAccess $domainAccessManager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->entityQuery = $entityQuery;
    $this->domainAccessManager = $domainAccessManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity.query'),
      $container->get('domain_access.manager')
    );
  }

  /**
   * Builds the list of domains and relevant entities.
   *
   * @param array $options
   *   A list of variables required to build editor or content pages.
   *
   * @see contentlist()
   *
   * @return array
   *   A Drupal page build array.
   */
  public function buildList(array $options) {
    $account = $this->getUser();
    $build = [
      '#theme' => 'table',
      '#header' => [$this->t('Domain'), $options['column_header']],
    ];
    if ($account->hasPermission($options['all_permission'])) {
      $build['#rows'][] = [
        Link::fromTextAndUrl($this->t('All affiliates'), Url::fromUri('internal:/admin/content/' . $options['path'] . '/all_affiliates')),
        $this->getCount($options['type']),
      ];
    }
    // Loop through domains.
    $domains = $this->entityTypeManager->getStorage('domain')->loadMultipleSorted();
    $manager = $this->domainAccessManager;
    /** @var \Drupal\domain\DomainInterface $domain */
    foreach ($domains as $domain) {
      if ($account->hasPermission($options['all_permission']) || $manager->hasDomainPermissions($account, $domain, [$options['permission']])) {
        $row = [
          Link::fromTextAndUrl($domain->label(), Url::fromUri('internal:/admin/content/' . $options['path'] . '/' . $domain->id())),
          $this->getCount($options['type'], $domain),
        ];
        $build['#rows'][] = $row;
      }
    }
    return $build;
  }

  /**
   * Generates a list of content by domain.
   */
  public function contentList() {
    $options = [
      'type' => 'node',
      'column_header' => $this->t('Content count'),
      'permission' => 'publish to any assigned domain',
      'all_permission' => 'publish to any domain',
      'path' => 'domain-content',
    ];

    return $this->buildList($options);
  }

  /**
   * Generates a list of editors by domain.
   */
  public function editorsList() {
    $options = [
      'type' => 'user',
      'column_header' => $this->t('Editor count'),
      'permission' => 'assign domain editors',
      'all_permission' => 'assign editors to any domain',
      'path' => 'domain-editors',
    ];

    return $this->buildList($options);
  }

  /**
   * Counts the content for a domain.
   *
   * @param string $entity_type
   *   The entity type.
   * @param \Drupal\domain\DomainInterface $domain
   *   The domain to query. If passed NULL, checks status for all affiliates.
   *
   * @return int
   *   The content count for the given domain.
   */
  protected function getCount($entity_type = 'node', DomainInterface $domain = NULL) {
    if (is_null($domain)) {
      $field = DomainAccessManagerInterface::DOMAIN_ACCESS_ALL_FIELD;
      $value = 1;
    }
    else {
      $field = DomainAccessManagerInterface::DOMAIN_ACCESS_FIELD;
      $value = $domain->id();
    }
    // Note that we ignore node access so these queries work on any domain.
    $query = $this->entityQuery($entity_type)
      ->condition($field, $value)
      ->accessCheck(FALSE);

    return count($query->execute());
  }

  /**
   * Returns a fully loaded user object for the current request.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The current user object.
   */
  protected function getUser() {
    $account = $this->currentUser();
    // Advanced grants for edit/delete require permissions.
    return $this->entityTypeManager->getStorage('user')->load($account->id());
  }

}

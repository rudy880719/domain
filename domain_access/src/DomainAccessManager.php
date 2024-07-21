<?php

namespace Drupal\domain_access;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\domain\DomainInterface;
use Drupal\domain\DomainNegotiatorInterface;

/**
 * Checks the access status of entities based on domain settings.
 *
 * @todo It is possible that this class may become a subclass of the
 * DomainElementManager, however, the use-case is separate as far as I can tell.
 */
class DomainAccessManager implements DomainAccessManagerInterface {

  /**
   * The domain negotiator.
   *
   * @var \Drupal\domain\DomainNegotiatorInterface
   */
  protected $negotiator;

  /**
   * The Drupal module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The domain storage.
   *
   * @var \Drupal\domain\DomainStorageInterface
   */
  protected $domainStorage;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * Constructs a DomainAccessManager object.
   *
   * @param \Drupal\domain\DomainNegotiatorInterface $negotiator
   *   The domain negotiator.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The Drupal module handler.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(DomainNegotiatorInterface $negotiator, ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager) {
    $this->negotiator = $negotiator;
    $this->moduleHandler = $module_handler;
    $this->entityTypeManager = $entity_type_manager;
    $this->domainStorage = $entity_type_manager->getStorage('domain');
    $this->userStorage = $entity_type_manager->getStorage('user');
  }

  /**
   * {@inheritdoc}
   */
  public static function getAccessValues(FieldableEntityInterface $entity, $field_name = DomainAccessManagerInterface::DOMAIN_ACCESS_FIELD) {
    // @todo static cache.
    $list = [];
    // @todo In tests, $entity is returning NULL.
    if (is_null($entity)) {
      return $list;
    }
    // Get the values of an entity.
    $values = $entity->hasField($field_name) ? $entity->get($field_name) : [];
    // Must be at least one item.
    foreach ($values as $item) {
      $target = $item->getValue();
      if (isset($target['target_id'])) {
        $domain = \Drupal::entityTypeManager()->getStorage('domain')->load($target['target_id']);
        if ($domain instanceof DomainInterface) {
          $list[$domain->id()] = $domain->getDomainId();
        }
      }
    }

    return $list;
  }

  /**
   * {@inheritdoc}
   */
  public static function getAllValue(FieldableEntityInterface $entity) {
    return $entity->hasField(DomainAccessManagerInterface::DOMAIN_ACCESS_ALL_FIELD) ? (bool) $entity->get(DomainAccessManagerInterface::DOMAIN_ACCESS_ALL_FIELD)->value : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function checkEntityAccess(FieldableEntityInterface $entity, AccountInterface $account) {
    $entity_domains = self::getAccessValues($entity);
    $user = $this->userStorage->load($account->id());
    if (self::getAllValue($user) === TRUE && count($entity_domains) > 0) {
      return TRUE;
    }
    $user_domains = self::getAccessValues($user);
    return count(array_intersect($entity_domains, $user_domains)) > 0;
  }

  /**
   * {@inheritdoc}
   */
  public static function getDefaultValue(FieldableEntityInterface $entity, FieldDefinitionInterface $definition) {
    $item = [];
    if (!$entity->isNew()) {
      // If set, ensure we do not drop existing data.
      foreach (self::getAccessValues($entity) as $id) {
        $item[] = $id;
      }
    }
    // When creating a new entity, populate if required.
    elseif ($entity->getFieldDefinition(DomainAccessManagerInterface::DOMAIN_ACCESS_FIELD)->isRequired()) {
      $active = \Drupal::service('domain.negotiator')->getActiveDomain();
      if ($active instanceof DomainInterface) {
        $item[0]['target_uuid'] = $active->uuid();
      }
    }
    return $item;
  }

  /**
   * {@inheritdoc}
   */
  public function hasDomainPermissions(AccountInterface $account, DomainInterface $domain, array $permissions, $conjunction = 'AND') {
    // Assume no access.
    $access = FALSE;

    // In the case of multiple AND permissions, assume access and then deny if
    // any check fails.
    if ($conjunction === 'AND' && $permissions !== []) {
      $access = TRUE;
      foreach ($permissions as $permission) {
        if (!($permission_access = $account->hasPermission($permission))) {
          $access = FALSE;
          break;
        }
      }
    }
    // In the case of multiple OR permissions, assume deny and then allow if any
    // check passes.
    else {
      foreach ($permissions as $permission) {
        if ($permission_access = $account->hasPermission($permission)) {
          $access = TRUE;
          break;
        }
      }
    }
    // Validate that the user is assigned to the domain. If not, deny.
    $user = $this->userStorage->load($account->id());
    $allowed = self::getAccessValues($user);
    if (!isset($allowed[$domain->id()]) && self::getAllValue($user) !== TRUE) {
      $access = FALSE;
    }

    return $access;
  }

  /**
   * {@inheritdoc}
   */
  public function getContentUrls(FieldableEntityInterface $entity) {
    $list = [];
    $processed = FALSE;
    $domains = self::getAccessValues($entity);
    if ($this->moduleHandler->moduleExists('domain_source')) {
      $source = domain_source_get($entity);
      if (isset($domains[$source])) {
        unset($domains['source']);
      }
      if (!is_null($source)) {
        $list[] = $source;
      }
      $processed = TRUE;
    }
    $list = array_merge($list, array_keys($domains));
    $domains = $this->domainStorage->loadMultiple($list);
    $urls = [];
    foreach ($domains as $domain) {
      $options = ['domain_target_id' => $domain->id()];
      $url = $entity->toUrl('canonical', $options);
      if ($processed) {
        $urls[$domain->id()] = $url->toString();
      }
      else {
        // @phpstan-ignore-next-line
        $urls[$domain->id()] = $domain->buildUrl($url->toString());
      }
    }
    return $urls;
  }

}

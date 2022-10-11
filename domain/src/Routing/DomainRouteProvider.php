<?php

namespace Drupal\domain\Routing;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\Routing\RouteProvider;
use Drupal\Core\State\StateInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Custom router.route_provider service to make it domain context sensitive.
 *
 * The default behaviour is to cache routes by path and query parameters only,
 * for multiple domains this can make the home page of domain 1 be served from
 * cache as the home page of domain 2.
 *
 * Originally used by Domain Config, this behavior is tested in
 * domain_config/tests/src/Functional/DomainConfigHomepageTest.php.
 *
 * We have moved the behavior to the main module to better support extension
 * modules that do not require Domain Config, such as Domain Path.
 */
class DomainRouteProvider extends RouteProvider {

  /**
   * The database connection from which to read route information.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The name of the SQL table from which to read the routes.
   *
   * @var string
   */
  protected $tableName = 'router';

  /**
   * The state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The current path.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The cache tag invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagInvalidator;

  /**
   * A path processor manager for resolving the system path.
   *
   * @var \Drupal\Core\PathProcessor\InboundPathProcessorInterface
   */
  protected $pathProcessor;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager = NULL;

  /**
   * Constructs a new PathMatcher.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   A database connection object.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   The current path.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   * @param \Drupal\Core\PathProcessor\InboundPathProcessorInterface $path_processor
   *   The path processor.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tag_invalidator
   *   The cache tag invalidator.
   * @param string $table
   *   The table in the database to use for matching. Defaults to 'router'.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   (Optional) The language manager.
   */
  public function __construct(Connection $connection, StateInterface $state, CurrentPathStack $current_path, CacheBackendInterface $cache_backend, InboundPathProcessorInterface $path_processor, CacheTagsInvalidatorInterface $cache_tag_invalidator, $table = 'router', LanguageManagerInterface $language_manager = NULL) {
    $this->connection = $connection;
    $this->state = $state;
    $this->currentPath = $current_path;
    $this->cache = $cache_backend;
    $this->cacheTagInvalidator = $cache_tag_invalidator;
    $this->pathProcessor = $path_processor;
    $this->tableName = $table;
    $this->languageManager = $language_manager ?: \Drupal::languageManager();
  }

  /**
   * Returns the cache ID for the route collection cache.
   *
   * We are overriding the cache id by inserting the host to the cid.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @see \Drupal\Core\Routing\RouteProvider::getRouteCollectionCacheId()
   *
   * @return string
   *   The cache ID.
   */
  protected function getRouteCollectionCacheId(Request $request) {
    // Include the current language code in the cache identifier as
    // the language information can be elsewhere than in the path, for example
    // based on the domain.
    $language_part = $this->getCurrentLanguageCacheIdPart();
    return 'route:' . $request->getHost() . ':' . $language_part . ':' . $request->getPathInfo() . ':' . $request->getQueryString();
  }

}

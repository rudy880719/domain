<?php

namespace Drupal\domain_config_middleware_test;

use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Middleware for the domain_config_test module.
 */
class Middleware implements HttpKernelInterface {

  /**
   * The request type.
   *
   * @var int
   */
  public const MAIN_REQUEST = 1;

  /**
   * @deprecated since symfony/http-kernel 5.3, use MAIN_REQUEST instead.
   *             To ease the migration, this constant won't be removed until Symfony 7.0.
   */
  public const MASTER_REQUEST = self::MAIN_REQUEST;

  /**
   * The decorated kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a Middleware object.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
   *   The decorated kernel.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(HttpKernelInterface $http_kernel, ConfigFactoryInterface $config_factory) {
    $this->httpKernel = $http_kernel;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, int $type = self::MASTER_REQUEST, bool $catch = TRUE) {
    // This line should break hooks in our code.
    // @see https://www.drupal.org/node/2896434.
    $config = $this->configFactory->get('domain_config_middleware_test.settings');
    return $this->httpKernel->handle($request, $type, $catch);
  }

}

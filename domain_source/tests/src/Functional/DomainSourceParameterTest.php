<?php

namespace Drupal\Tests\domain_source\Functional;

use Drupal\Core\Url;
use Drupal\Tests\domain\Functional\DomainTestBase;

/**
 * Tests behavior for URLs that include query parameters.
 *
 * @group domain_source
 */
class DomainSourceParameterTest extends DomainTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'domain',
    'domain_source',
    'domain_source_test',
    'field',
    'node',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create 3 domains.
    DomainTestBase::domainCreateTestDomains(3);
  }

  /**
   * Tests the behavior of urls with query params.
   */
  public function testDomainSourceParams() {
    // Variables for our tests.
    $path = 'domain-format-test';
    $options = ['query' => ['_format' => 'json']];
    $domains = \Drupal::entityTypeManager()->getStorage('domain')->loadMultiple();
    foreach ($domains as $domain) {
      $this->drupalGet($domain->getPath() . $path, $options);
    }
    $uri_path = '/' . $path;
    $expected = base_path() . $path . '?_format=json';

    // Get the link using Url::fromUserInput()
    $url = Url::fromUserInput($uri_path, $options)->toString();
    $this->assertEquals($expected, $url, 'fromUserInput');
  }

}

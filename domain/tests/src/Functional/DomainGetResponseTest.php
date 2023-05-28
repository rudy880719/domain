<?php

namespace Drupal\Tests\domain\Functional;

/**
 * Tests domain record HTTP response.
 *
 * @group domain
 */
class DomainGetResponseTest extends DomainTestBase {

  /**
   * Tests that a domain response is proper.
   */
  public function testDomainResponse() {
    // No domains should exist.
    $this->domainTableIsEmpty();

    // Create a new domain programmatically.
    $this->domainCreateTestDomains();

    // Check the created domain based on its known id value.
    $key = 'example_com';
    /** @var \Drupal\domain\Entity\Domain $domain */
    $domain = \Drupal::entityTypeManager()->getStorage('domain')->load($key);

    // Our testing server should be able to access the test PNG file.
    $this->assertTrue($domain->getResponse() === 200, 'Server returned a 200 response.');
    // Guzzle will no longer test invalid domains.
  }

}

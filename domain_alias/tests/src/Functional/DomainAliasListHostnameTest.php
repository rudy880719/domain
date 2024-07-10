<?php

namespace Drupal\Tests\domain_alias\Functional;

/**
 * Tests behavior for environment loading on the overview page.
 *
 * @group domain_alias
 */
class DomainAliasListHostnameTest extends DomainAliasTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create 3 domains. These will be example.com, one.example.com,
    // two.example.com.
    $this->domainCreateTestDomains(3);
  }

  /**
   * Test for environment matching.
   */
  public function testDomainAliasEnvironments() {
    /** @var \Drupal\domain\DomainStorageInterface $domain_storage */
    $domain_storage = \Drupal::entityTypeManager()->getStorage('domain');
    /** @var \Drupal\domain_alias\DomainAliasStorageInterface $alias_storage */
    $alias_storage = \Drupal::entityTypeManager()->getStorage('domain_alias');
    /** @var \Drupal\domain\DomainInterface[] $domains */
    $domains = $domain_storage->loadMultiple();

    $base = $this->baseHostname;
    $hostnames = [$base, 'one.' . $base, 'two.' . $base];

    // Our patterns should map to example.com, one.example.com, two.example.com.
    $patterns = ['*.' . $base, 'four.' . $base, 'five.' . $base];
    $i = 0;
    foreach ($domains as $domain) {
      $this->assertTrue($domain->getHostname() === $hostnames[$i], 'Hostnames set correctly');
      $this->assertTrue($domain->getCanonical() === $hostnames[$i], 'Canonical domains set correctly');
      $values = [
        'domain_id' => $domain->id(),
        'pattern' => array_shift($patterns),
        'redirect' => 0,
        'environment' => 'local',
      ];
      $this->createDomainAlias($values);
      $i++;
    }
    // Test the environment loader.
    $local = $alias_storage->loadByEnvironment('local');
    $this->assertTrue(count($local) === 3, 'Three aliases set to local');
    // Test the environment matcher. $domain here is two.example.com.
    $test_domain = end($domains);
    $match = $alias_storage->loadByEnvironmentMatch($test_domain, 'local');
    $this->assertTrue(count($match) === 1, 'One environment match loaded');
    $alias = current($match);
    $this->assertTrue($alias->getPattern() === 'five.' . $base, 'Proper pattern match loaded.');

    $admin = $this->drupalCreateUser([
      'bypass node access',
      'administer content types',
      'administer node fields',
      'administer node display',
      'administer domains',
    ]);
    $this->drupalLogin($admin);

    // Load an aliased domain.
    $this->drupalGet($test_domain->getScheme() . 'five.' . $base . $GLOBALS['base_path'] . 'admin/config/domain');
    $this->assertSession()->statusCodeEquals(200);

    // Save the form.
    $this->pressButton('edit-submit');
    // Ensure the values haven't changed.
    $i = 0;
    /** @var \Drupal\domain\DomainInterface[] $domains */
    $domains = $domain_storage->loadMultiple();
    foreach ($domains as $domain) {
      $this->assertTrue($domain->getHostname() === $hostnames[$i], 'Hostnames set correctly');
      $this->assertTrue($domain->getCanonical() === $hostnames[$i], 'Canonical domains set correctly');
      $i++;
    }
  }

}

<?php

namespace Drupal\Tests\domain\Functional;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;

/**
 * Tests the domain navigation block.
 *
 * @group domain
 */
class DomainNavBlockTest extends DomainTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['domain', 'node', 'block'];

  /**
   * Test domain navigation block.
   */
  public function testDomainNav() {
    // Create four new domains programmatically.
    $this->domainCreateTestDomains(4);
    $domains = $this->getDomains();

    // Place the nav block.
    $block = $this->drupalPlaceBlock('domain_nav_block');

    // Let the anon user view the block.
    user_role_grant_permissions(AccountInterface::ANONYMOUS_ROLE, ['use domain nav block']);

    // Load the homepage. All links should appear.
    $this->drupalGet('<front>');
    // Confirm domain links.
    foreach ($domains as $id => $domain) {
      $this->findLink($domain->label());
    }

    // Disable one of the domains. One link should not appear.
    $disabled = $domains['one_example_com'];
    $disabled->disable();

    // Load the homepage.
    $this->drupalGet('<front>');
    // Confirm domain links.
    foreach ($domains as $id => $domain) {
      if ($id !== 'one_example_com') {
        $this->findLink($domain->label());
      }
      else {
        $this->assertSession()->responseNotContains($domain->label());
      }
    }
    // Let the anon user view disabled domains. All links should appear.
    user_role_grant_permissions(AccountInterface::ANONYMOUS_ROLE, ['access inactive domains']);

    // Load the homepage.
    $this->drupalGet('<front>');
    // Confirm domain links.
    foreach ($domains as $domain) {
      $this->findLink($domain->label());
    }

    // Now update the configuration and test again.
    $this->config('block.block.' . $block->id())
      ->set('settings.link_options', 'active')
      ->set('settings.link_label', 'hostname')
      ->save();

    // Load the login page.
    $this->drupalGet('user/login');
    // Confirm domain links.
    foreach ($domains as $domain) {
      $this->findLink($domain->getHostname());
      // @phpstan-ignore-next-line
      $this->assertSession()->responseContains($domain->buildUrl(base_path() . 'user/login'));
    }

    // Now update the configuration and test again.
    $this->config('block.block.' . $block->id())
      ->set('settings.link_options', 'home')
      ->set('settings.link_theme', 'menu')
      ->set('settings.link_label', 'url')
      ->save();

    // Load the login page.
    $this->drupalGet('user/login');
    // Confirm domain links.
    foreach ($domains as $domain) {
      $this->findLink($domain->getPath());
      $this->assertSession()->responseContains($domain->getPath());
    }
  }

  public function testActiveDomainLinkClass() {
    $this->domainCreateTestDomains(3);
    $domains = $this->getDomains();

    $block = $this->drupalPlaceBlock('domain_nav_block');
    user_role_grant_permissions(AccountInterface::ANONYMOUS_ROLE, ['use domain nav block']);

    foreach ($domains as $request_domain) {
      $this->drupalGet(Url::fromRoute('<front>'), ['base_url' => trim($request_domain->getPath(), '/')]);
      $page = $this->getSession()->getPage();

      $expected_classes = [$request_domain->id() => 'active'];

      $actual_classes = [];
      foreach ($domains as $id => $domain) {
        $link = $page->findById('block-' . $block->id())->findLink($domain->label());
        $actual_classes[$id] = $link->getAttribute('class');
      }

      $this->assertEquals($expected_classes, array_filter($actual_classes));
    }
  }
}

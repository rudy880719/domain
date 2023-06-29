<?php

namespace Drupal\Tests\domain_config_ui\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\domain\Traits\DomainTestTrait;
use Drupal\Tests\domain_config_ui\Traits\DomainConfigUITestTrait;

/**
 * Tests the domain config user interface for appearance configuration.
 *
 * @group domain_config_ui
 */
class DomainConfigUIDefaultThemeSettingsTest extends WebDriverTestBase {

  use DomainTestTrait;
  use DomainConfigUITestTrait;

  /**
   * The default theme.
   *
   * @var string
   */
  protected $defaultTheme = 'olivero';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'domain_config_ui',
  ];

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->createAdminUser();
    $this->domainCreateTestDomains(2, 'base.host_name');
  }

  /**
   * Tests set as default links.
   */
  public function testFormOptions() {
    $this->drupalLogin($this->adminUser);

    // Visit the domain config ui administration page.
    $this->drupalGet('/admin/appearance');
    $this->assertSession()->statusCodeEquals(200);

    // Select domain.
    $this->assertSession()->selectExists('Domain')->selectOption('Example');
    $this->assertWaitOnAjaxRequest();

    // Check if href contains correct domain_config_ui_domain param.
    $this->assertSession()->elementAttributeContains('xpath', "//*[@id=\"system-themes-page\"]//a[contains(text(),'Set as default')]", 'href', 'domain_config_ui_domain=base.host_name');
  }

}

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

    $this->setBaseHostname();
    $this->domainCreateTestDomains(5);

    $this->createLanguage();
  }

  /**
   * Tests set as default links.
   */
  public function testDomainAppearanceSettings() {
    $this->drupalGet('/admin/appearance');
    $page = $this->getSession()->getPage();

    // Test our form.
    $page->findField('domain');
    $page->findField('language');
    // Select domain.
    $page->selectFieldOption('domain', 'one_example_com');
    $this->assertWaitOnAjaxRequest();

    // Check if href contains correct domain_config_ui_domain param.
    $this->assertSession()->elementAttributeContains('xpath', "//*[@id=\"system-themes-page\"]//a[contains(text(),'Set as default')]", 'href', 'domain_config_ui_domain=base.host_name');
  }

}

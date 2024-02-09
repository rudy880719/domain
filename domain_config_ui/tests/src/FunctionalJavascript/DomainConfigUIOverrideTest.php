<?php

namespace Drupal\Tests\domain_config_ui\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\domain\Traits\DomainTestTrait;
use Drupal\Tests\domain_config_ui\Traits\DomainConfigUITestTrait;

/**
 * Tests the domain config user interface.
 *
 * @group domain_config_ui
 */
class DomainConfigUIOverrideTest extends WebDriverTestBase {

  use DomainTestTrait;
  use DomainConfigUITestTrait;

  /**
   * Disabled config schema checking.
   *
   * Domain Config actually duplicates schemas provided by other modules,
   * so it cannot define its own.
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE; // phpcs:ignore

  /**
   * The default theme.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'domain_config_ui',
    'domain_config_test',
    'language',
  ];

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->createAdminUser();
    $this->createEditorUser();

    $this->setBaseHostname();
    $this->domainCreateTestDomains(5);

    $this->createLanguage();
  }

  /**
   * Tests that we can save domain and language-specific settings.
   */
  public function testAjax(): void {
    // Test base configuration.
    $config_name = 'system.site';
    $config = \Drupal::configFactory()->get($config_name)->getRawData();

    $this->assertEquals('Drupal', $config['name']);
    $this->assertEquals('/user/login', $config['page']['front']);

    // Test stored configuration.
    $config_name = 'domain.config.one_example_com.en.system.site';
    $config = \Drupal::configFactory()->get($config_name)->getRawData();

    $this->assertEquals('One', $config['name']);
    $this->assertEquals('/node/1', $config['page']['front']);

    $this->drupalLogin($this->adminUser);
    $path = '/admin/config/system/site-information';

    // Visit the site information page.
    $this->drupalGet($path);
    $page = $this->getSession()->getPage();

    $assert_session = $this->assertSession();

    // Test our form.
    $page->findField('domain');
    $page->findField('language');
    $page->selectFieldOption('domain', 'one_example_com');
    $assert_session->waitForText('This configuration will be saved for the One domain and displayed in all languages.');
    $assert_session->pageTextContainsOnce('This configuration will be saved for the One domain and displayed in all languages.');
    $assert_session->addressEquals($path . '?domain_config_ui_domain=one_example_com&domain_config_ui_language=');
    $this->htmlOutput($page->getHtml());

    $page = $this->getSession()->getPage();
    $page->fillField('site_name', 'New name');
    $page->fillField('site_frontpage', '/user');
    $this->htmlOutput($page->getHtml());
    $page->pressButton('Save configuration');
    $assert_session->pageTextContainsOnce('The configuration options have been saved.');
    $this->htmlOutput($page->getHtml());

    // We did not save a language prefix, so none will be present.
    $config_name = 'domain.config.one_example_com.system.site';
    $config = \Drupal::configFactory()->get($config_name)->getRawData();

    $this->assertEquals('New name', $config['name']);
    $this->assertEquals('/user', $config['page']['front']);

    // Now let's save a language.
    // Visit the site information page.
    $this->drupalGet($path);
    $page = $this->getSession()->getPage();

    // Test our form.
    $page->selectFieldOption('domain', 'one_example_com');
    $assert_session->waitForText('This configuration will be saved for the Test One domain and displayed in Spanish');
    $assert_session->pageTextContainsOnce('This configuration will be saved for the Test One domain and displayed in Spanish');
    $assert_session->addressEquals($path . '?domain_config_ui_domain=one_example_com&domain_config_ui_language=');
    $this->htmlOutput($page->getHtml());

    $page = $this->getSession()->getPage();
    $page->selectFieldOption('language', 'es');
    $assert_session->waitForText('This configuration will be saved for the Test One domain and displayed in Spanish');
    $assert_session->pageTextContainsOnce('This configuration will be saved for the Test One domain and displayed in Spanish');
    $assert_session->addressEquals($path . '?domain_config_ui_domain=one_example_com&domain_config_ui_language=es');
    $this->htmlOutput($page->getHtml());

    $page = $this->getSession()->getPage();
    $page->fillField('site_name', 'Neuvo nombre');
    $page->fillField('site_frontpage', '/user');
    $assert_session->waitForText('This configuration will be saved for the Test One domain and displayed in Spanish');
    $assert_session->pageTextContainsOnce('This configuration will be saved for the Test One domain and displayed in Spanish');
    $assert_session->addressEquals($path . '?domain_config_ui_domain=one_example_com&domain_config_ui_language=es');

    $this->htmlOutput($page->getHtml());
    $page->pressButton('Save configuration');
    $assert_session->pageTextContainsOnce('The configuration options have been saved.');
    $this->htmlOutput($page->getHtml());

    // We did save a language prefix, so one will be present.
    $config_name = 'domain.config.one_example_com.es.system.site';
    $config = \Drupal::configFactory()->get($config_name)->getRawData();

    $this->assertEquals('Neuvo nombre', $config['name']);
    $this->assertEquals('/user', $config['page']['front']);

    // Make sure the base is untouched.
    $config_name = 'system.site';
    $config = \Drupal::configFactory()->get($config_name)->getRawData();

    $this->assertEquals('Drupal', $config['name']);
    $this->assertEquals('/user/login', $config['page']['front']);
  }

}

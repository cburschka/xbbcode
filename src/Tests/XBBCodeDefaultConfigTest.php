<?php

/**
 * @file
 * Contains \Drupal\xbbcode\Tests\XBBCodeDefaultConfigTest.
 */

namespace Drupal\xbbcode\Tests;

use Drupal\simpletest\KernelTestBase;
use Drupal\user\RoleInterface;

/**
 * Test the module's default configuration.
 *
 * @group xbbcode
 */
class XBBCodeDefaultConfigTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'user', 'filter', 'xbbcode'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('system', 'url_alias');
    $this->installEntitySchema('user');
    $this->installConfig(['user', 'xbbcode']);
  }

  /**
   * Test installation of the BBCode format.
   */
  public function testInstallation() {
    // Verify our global settings.
    $config = $this->config('xbbcode.settings');
    $this->assertEqual($config->get('tags'), []);

    // Verify that the format was installed correctly.
    $format = entity_load('filter_format', 'xbbcode');

    // Use part of the FilterDefaultConfigTest, but only those parts not
    // implicitly guaranteed by the core tests (such as the UUID and ID being
    // set correctly).
    $this->assertTrue((bool) $format);

    $this->assertEqual($format->label(), 'BBCode');
    $this->assertEqual($format->get('weight'), -5);

    // Verify that the defined roles in the configuration have been processed.
    $this->assertEqual(array_keys(filter_get_roles_by_format($format)), [
      RoleInterface::ANONYMOUS_ID,
      RoleInterface::AUTHENTICATED_ID,
    ]);

    $this->assertEqual($format->get('dependencies'), ['module' => ['xbbcode']]);

    // Verify the enabled filters.
    $filters = $format->get('filters');
    $this->assertEqual($filters['filter_html_escape']['status'], 1);
    $this->assertEqual($filters['filter_html_escape']['weight'], 0);
    $this->assertEqual($filters['filter_html_escape']['provider'], 'filter');
    $this->assertEqual($filters['filter_html_escape']['settings'], []);
    $this->assertEqual($filters['xbbcode']['status'], 1);
    $this->assertEqual($filters['xbbcode']['weight'], 1);
    $this->assertEqual($filters['xbbcode']['provider'], 'xbbcode');
    $this->assertEqual($filters['xbbcode']['settings'], [
      'override' => FALSE,
      'linebreaks' => TRUE,
      'tags' => [],
    ]);
    $this->assertEqual($filters['filter_url']['status'], 1);
    $this->assertEqual($filters['filter_url']['weight'], 2);
    $this->assertEqual($filters['filter_url']['provider'], 'filter');
    $this->assertEqual($filters['filter_url']['settings'], [
      'filter_url_length' => 72,
    ]);
    $this->assertEqual($filters['filter_htmlcorrector']['status'], 1);
    $this->assertEqual($filters['filter_htmlcorrector']['weight'], 3);
    $this->assertEqual($filters['filter_htmlcorrector']['provider'], 'filter');
    $this->assertEqual($filters['filter_htmlcorrector']['settings'], []);
  }

}

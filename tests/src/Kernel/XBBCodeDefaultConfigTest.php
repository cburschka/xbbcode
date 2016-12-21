<?php

/**
 * @file
 * Contains \Drupal\xbbcode\Tests\XBBCodeDefaultConfigTest.
 */

namespace Drupal\Tests\xbbcode\Kernel;

use Drupal\filter\Entity\FilterFormat;
use Drupal\KernelTests\KernelTestBase;
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

    $this->installConfig(['user', 'xbbcode']);
  }

  /**
   * Test installation of the BBCode format.
   */
  public function testInstallation() {
    // Verify our global settings.
    $config = $this->config('xbbcode.settings');
    self::assertEquals($config->get('tags'), []);

    // Verify that the format was installed correctly.
    $format = FilterFormat::load('xbbcode');

    // Use part of the FilterDefaultConfigTest, but only those parts not
    // implicitly guaranteed by the core tests (such as the UUID and ID being
    // set correctly).
    self::assertTrue((bool) $format);

    self::assertEquals($format->label(), 'BBCode');
    self::assertEquals($format->get('weight'), -5);

    // Verify that the defined roles in the configuration have been processed.
    self::assertEquals(array_keys(filter_get_roles_by_format($format)), [
      RoleInterface::ANONYMOUS_ID,
      RoleInterface::AUTHENTICATED_ID,
    ]);

    self::assertEquals($format->get('dependencies'), ['module' => ['xbbcode']]);

    // Verify the enabled filters.
    $filters = $format->get('filters');
    self::assertEquals($filters['filter_html_escape']['status'], 1);
    self::assertEquals($filters['filter_html_escape']['weight'], 0);
    self::assertEquals($filters['filter_html_escape']['provider'], 'filter');
    self::assertEquals($filters['filter_html_escape']['settings'], []);
    self::assertEquals($filters['xbbcode']['status'], 1);
    self::assertEquals($filters['xbbcode']['weight'], 1);
    self::assertEquals($filters['xbbcode']['provider'], 'xbbcode');
    self::assertEquals($filters['xbbcode']['settings'], [
      'override' => FALSE,
      'linebreaks' => TRUE,
      'tags' => [],
    ]);
    self::assertEquals($filters['filter_url']['status'], 1);
    self::assertEquals($filters['filter_url']['weight'], 2);
    self::assertEquals($filters['filter_url']['provider'], 'filter');
    self::assertEquals($filters['filter_url']['settings'], [
      'filter_url_length' => 72,
    ]);
    self::assertEquals($filters['filter_htmlcorrector']['status'], 1);
    self::assertEquals($filters['filter_htmlcorrector']['weight'], 3);
    self::assertEquals($filters['filter_htmlcorrector']['provider'], 'filter');
    self::assertEquals($filters['filter_htmlcorrector']['settings'], []);
  }

}

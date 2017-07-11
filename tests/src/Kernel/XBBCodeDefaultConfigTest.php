<?php

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
    // Verify that the format was installed correctly.
    /** @var \Drupal\filter\FilterFormatInterface $format */
    $format = FilterFormat::load('xbbcode');

    // Use part of the FilterDefaultConfigTest, but only those parts not
    // implicitly guaranteed by the core tests (such as the UUID and ID being
    // set correctly).
    self::assertTrue((bool) $format);

    self::assertEquals('BBCode', $format->label());
    self::assertEquals(-5, $format->get('weight'));

    // Verify that the defined roles in the configuration have been processed.
    self::assertEquals([
      RoleInterface::ANONYMOUS_ID,
      RoleInterface::AUTHENTICATED_ID,
    ], array_keys(filter_get_roles_by_format($format)));

    self::assertEquals([
      'module' => ['xbbcode'],
      'enforced' => ['module' => ['xbbcode']],
    ], $format->get('dependencies'));

    // Verify the enabled filters.
    $filters = $format->get('filters');
    self::assertEquals(1, $filters['filter_html_escape']['status']);
    self::assertEquals(0, $filters['filter_html_escape']['weight']);
    self::assertEquals('filter', $filters['filter_html_escape']['provider']);
    self::assertEquals([], $filters['filter_html_escape']['settings']);
    self::assertEquals(1, $filters['xbbcode']['status']);
    self::assertEquals(1, $filters['xbbcode']['weight']);
    self::assertEquals('xbbcode', $filters['xbbcode']['provider']);
    self::assertEquals([
      'linebreaks' => TRUE,
      'tags' => '',
    ], $filters['xbbcode']['settings']);
    self::assertEquals(1, $filters['filter_url']['status']);
    self::assertEquals(2, $filters['filter_url']['weight']);
    self::assertEquals('filter', $filters['filter_url']['provider']);
    self::assertEquals([
      'filter_url_length' => 72,
    ], $filters['filter_url']['settings']);
    self::assertEquals(1, $filters['filter_htmlcorrector']['status']);
    self::assertEquals(3, $filters['filter_htmlcorrector']['weight']);
    self::assertEquals('filter', $filters['filter_htmlcorrector']['provider']);
    self::assertEquals([], $filters['filter_htmlcorrector']['settings']);
  }

}

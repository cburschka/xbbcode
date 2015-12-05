<?php

/**
 * @file
 * Contains \Drupal\xbbcode\Tests\XBBCodeAdminTest.
 */

namespace Drupal\xbbcode\Tests;

use Drupal\Component\Utility\Unicode;
use Drupal\simpletest\WebTestBase;

/**
 * Test the administrative interface.
 *
 * @group xbbcode
 */
class XBBCodeAdminTest extends WebTestBase {
  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'block',
    'filter',
    'node',
    'xbbcode',
    'xbbcode_test_plugin',
  ];

  /**
   * Admin user.
   */
  protected $adminUser;

  /**
   * User who can create pages.
   */
  protected $webUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);

    // Set up a BBCode filter format.
    $xbbcode_format = entity_create('filter_format', [
      'format' => 'xbbcode_test',
      'name' => 'XBBCode Test',
      'filters' => [
        'xbbcode' => [
          'status' => 1,
          'settings' => [
            'tags' => [],
            'override' => FALSE,
            'linebreaks' => FALSE,
          ],
        ],
      ],
    ]);
    $xbbcode_format->save();

    $this->adminUser = $this->drupalCreateUser([
      'administer filters',
      $xbbcode_format->getPermissionName(),
      'administer custom BBCode tags',
      'access site reports',
    ]);

    $this->webUser = $this->drupalCreateUser(['create page content', 'edit own page content']);
    user_role_grant_permissions('authenticated', [$xbbcode_format->getPermissionName()]);
    user_role_grant_permissions('anonymous', [$xbbcode_format->getPermissionName()]);
    $this->drupalLogin($this->adminUser);
    $this->drupalPlaceBlock('local_actions_block');
  }

  /**
   * Test the custom tag page.
   */
  public function testCustomTags() {
    $this->drupalGet('admin/config/content/xbbcode/tags');

    $this->assertText('Test Tag Label');
    $this->assertText('Test Tag Description');
    $this->assertText('[test_tag]Content[/test_tag]');

    // Check that the tag can't be deleted.
    $this->assertNoLinkByHref('admin/config/content/xbbcode/tags/manage/test_tag_id');
    $this->assertNoLinkByHref('admin/config/content/xbbcode/tags/manage/test_tag_id/delete');

    $this->clickLink('Create custom tag');
    $edit = [
      'id' => Unicode::strtolower($this->randomMachineName()),
      'label' => $this->randomString(),
      'description' => $this->randomString(),
      'name' => Unicode::strtolower($this->randomMachineName()),
      'sample' => '[{{ name }}=' . $this->randomMachineName() . ']' . $this->randomString() . '[/{{ name }}]',
      'template_code' => '[' . $this->randomString() . '|{{ tag.option }}|{{ tag.content }}]',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // We should have been redirected to the tag list.
    // Our new custom tag is there.
    $this->assertText(format_string('@label', ['@label' => $edit['label']]));
    $this->assertText(format_string('@desc', ['@desc' => $edit['description']]));
    $this->assertText(format_string('@sample', [
      '@sample' => str_replace('{{ name }}', $edit['name'], $edit['sample']),
    ]));
    // And so is the old one.
    $this->assertText('[test_tag]Content[/test_tag]');

    $this->assertLinkByHref('admin/config/content/xbbcode/tags/manage/' . $edit['id']);
    $this->assertLinkByHref('admin/config/content/xbbcode/tags/manage/' . $edit['id'] . '/delete');

    $this->clickLink('Edit');

    // Check for the delete link on the editing form.
    $this->assertLinkByHref('admin/config/content/xbbcode/tags/manage/' . $edit['id'] . '/delete');

    // Edit the description and the name.
    $new_edit['description'] = $this->randomString();
    $new_edit['name'] = Unicode::strtolower($this->randomMachineName());
    $this->drupalPostForm(NULL, $new_edit, t('Save'));

    $this->assertNoText(format_string('@desc', ['@desc' => $edit['description']]));
    $this->assertText(format_string('@desc', ['@desc' => $new_edit['description']]));
    $this->assertText(format_string('@sample', [
      '@sample' => str_replace('{{ name }}', $new_edit['name'], $edit['sample']),
    ]));

    // Delete the tag.
    $this->clickLink('Delete');
    $this->drupalPostForm(NULL, [], t('Delete'));
    // It's gone.
    $this->assertNoLinkByHref('admin/config/content/xbbcode/tags/manage/' . $edit['id']);
    $this->assertNoText(format_string('@desc', ['@desc' => $new_edit['description']]));

    // And the ID is available for re-use.
    $this->clickLink('Create custom tag');
    $this->drupalPostForm(NULL, $edit, t('Save'));
    // And it's back.
    $this->assertText(format_string('@desc', ['@desc' => $edit['description']]));
    $this->assertLinkByHref('admin/config/content/xbbcode/tags/manage/' . $edit['id']);
  }

}

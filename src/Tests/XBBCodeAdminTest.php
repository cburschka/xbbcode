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

    $this->adminUser = $this->drupalCreateUser([
      'administer filters',
      'administer custom BBCode tags',
      'access site reports',
    ]);

    $this->webUser = $this->drupalCreateUser(['create page content', 'edit own page content']);
    $this->drupalLogin($this->adminUser);
    $this->drupalPlaceBlock('local_actions_block');

    $this->customTag = [
      'id' => Unicode::strtolower($this->randomMachineName()),
      'label' => $this->randomString(),
      'description' => $this->randomString(),
      'name' => Unicode::strtolower($this->randomMachineName()),
      'sample' => '[{{ name }}=' . $this->randomMachineName() . ']' . $this->randomMachineName() . '[/{{ name }}]',
      'template_code' => '[' . $this->randomMachineName() . '|{{ tag.option }}|{{ tag.content }}]',
    ];
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

    $edit = $this->customTag;
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // We should have been redirected to the tag list.
    // Our new custom tag is there.
    $this->assertRaw(format_string('The BBCode tag %tag has been created.', ['%tag' => $edit['label']]));
    $this->assertEscaped($edit['label']);
    $this->assertEscaped($edit['description']);
    $this->assertEscaped(str_replace('{{ name }}', $edit['name'], $edit['sample']));
    // And so is the old one.
    $this->assertText('[test_tag]Content[/test_tag]');

    $this->assertLinkByHref('admin/config/content/xbbcode/tags/manage/' . $edit['id']);
    $this->assertLinkByHref('admin/config/content/xbbcode/tags/manage/' . $edit['id'] . '/delete');

    $this->clickLink('Edit');

    // Check for the delete link on the editing form.
    $this->assertLinkByHref('admin/config/content/xbbcode/tags/manage/' . $edit['id'] . '/delete');

    // Edit the description and the name.
    $new_edit = [
      'label' => $this->randomString(),
      'description' => $this->randomString(),
      'name' => Unicode::strtolower($this->randomMachineName()),
    ];
    $this->drupalPostForm(NULL, $new_edit, t('Save'));

    $this->assertRaw(format_string('The BBCode tag %tag has been updated.', ['%tag' => $new_edit['label']]));
    $this->assertNoEscaped($edit['description']);
    $this->assertEscaped($new_edit['description']);
    $this->assertEscaped(str_replace('{{ name }}', $new_edit['name'], $edit['sample']));

    // Delete the tag.
    $this->clickLink('Delete');
    $this->drupalPostForm(NULL, [], t('Delete'));
    $this->assertRaw(format_string('The BBCode tag %tag has been deleted.', ['%tag' => $new_edit['label']]));
    // It's gone.
    $this->assertNoLinkByHref('admin/config/content/xbbcode/tags/manage/' . $edit['id']);
    $this->assertNoEscaped($new_edit['description']);

    // And the ID is available for re-use.
    $this->clickLink('Create custom tag');
    $this->drupalPostForm(NULL, $edit, t('Save'));
    // And it's back.
    $this->assertEscaped($edit['description']);
    $this->assertLinkByHref('admin/config/content/xbbcode/tags/manage/' . $edit['id']);
  }

  /**
   * Test the plugin selection page.
   */
  public function testPlugins() {
    $this->drupalPostForm('admin/config/content/xbbcode/tags/add', $this->customTag, t('Save'));

    $this->drupalGet('filter/tips');
    $this->assertText('BBCode is active, but no tags are available.');

    $this->drupalLogin($this->webUser);
    $this->drupalGet('node/add/page');
    // BBCode is the only format available:
    $this->assertText('BBCode is active, but no tags are available.');

    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/content/xbbcode/settings');
    $this->assertNoFieldChecked('edit-tags-test-plugin-id-status');
    $this->assertFieldByName('tags[test_plugin_id][name]', 'test_plugin');
    $this->assertNoFieldChecked('edit-tags-xbbcode-tagtest-tag-id-status');
    $this->assertFieldByName('tags[xbbcode_tag:test_tag_id][name]', 'test_tag');
    $id = $this->customTag['id'];
    $name = $this->customTag['name'];
    $this->assertNoFieldChecked('edit-tags-xbbcode-tag' . $id . '-status');
    $this->assertFieldByName('tags[xbbcode_tag:' . $id . '][name]', $name);

    $edit = [
      'tags[test_plugin_id][status]' => 1,
      'tags[xbbcode_tag:test_tag_id][status]' => 1,
      'tags[xbbcode_tag:' . $id . '][status]' => 1,
    ];
    $this->drupalPostForm(NULL, $edit, 'Save configuration');
    $this->assertText('The configuration options have been saved.');
    $this->assertFieldChecked('edit-tags-test-plugin-id-status');
    $this->assertFieldChecked('edit-tags-xbbcode-tagtest-tag-id-status');
    $this->assertFieldChecked('edit-tags-xbbcode-tag' . $id . '-status');

    $this->drupalLogin($this->webUser);
    $this->drupalGet('filter/tips');
    $this->assertNoText('BBCode is active, but no tags are available.');

    $this->assertRaw('<strong>[test_plugin]</strong>');
    $this->assertText('[test_plugin foo=bar bar=foo]Lorem Ipsum Dolor Sit Amet[/test_plugin]');
    $this->assertRaw('<span data-foo="bar" data-bar="foo">Lorem Ipsum Dolor Sit Amet</span>');

    $this->assertRaw('<strong>[test_tag]</strong>');
    $this->assertText('[test_tag]Content[/test_tag]');
    $this->assertRaw('<strong>Content</strong>');

    $this->assertRaw(format_string('<strong>[@name]</strong>', ['@name' => $name]));
    $sample = $this->customTag['sample'];
    $this->assertEscaped(str_replace('{{ name }}', $name, $sample));
    $template_string = preg_replace('/^\[(.*?)\|.*$/', '$1', $this->customTag['template_code']);
    $match = [];
    preg_match('/\[{{ name }}=(.*?)](.*?)\[\/{{ name }}\]/', $sample, $match);
    $this->assertText("[$template_string|{$match[1]}|{$match[2]}]");

    $this->drupalGet('node/add/page');
    // BBCode is the only format available:
    $this->assertNoText('BBCode is active, but no tags are available.');
    $this->assertRaw(format_string('<abbr title="@desc">[@name]</abbr>', [
      '@desc' => $this->customTag['description'],
      '@name' => $name,
    ]));
    $this->assertRaw('<abbr title="Test Tag Description">[test_tag]</abbr>');
    $this->assertRaw('<abbr title="Test Plugin Description">[test_plugin]</abbr>');
  }

}

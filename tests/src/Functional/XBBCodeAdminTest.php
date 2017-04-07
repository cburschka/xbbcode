<?php

namespace Drupal\Tests\xbbcode\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Unicode;
use Drupal\filter\Entity\FilterFormat;
use Drupal\Tests\BrowserTestBase;

/**
 * Test the administrative interface.
 *
 * @group xbbcode
 */
class XBBCodeAdminTest extends BrowserTestBase {

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
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * User who can create pages.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $webUser;

  /**
   * A custom tag definition.
   *
   * @var array
   */
  protected $customTag;

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
   * Generate a custom tag and return it.
   *
   * @return array
   *   Information about the created tag.
   */
  private function createCustomTag() {
    $tag = [
      'id' => Unicode::strtolower($this->randomMachineName()),
      'label' => $this->randomString(),
      'description' => $this->randomString(),
      'name' => Unicode::strtolower($this->randomMachineName()),
      'sample' => '[{{ name }}=' . $this->randomMachineName() . ']' . $this->randomMachineName() . '[/{{ name }}]',
      'template_code' => '[' . $this->randomMachineName() . '|{{ tag.option }}|{{ tag.content }}]',
    ];
    $this->drupalPostForm('admin/config/content/xbbcode/tags/add', $tag, t('Save'));
    $this->assertSession()->responseContains(new FormattableMarkup('The BBCode tag %tag has been created.', ['%tag' => $tag['label']]));
    return $tag;
  }

  /**
   * Test the custom tag page.
   */
  public function testCustomTags() {
    $this->drupalGet('admin/config/content/xbbcode/tags');

    $this->assertSession()->pageTextContains('Test Tag Label');
    $this->assertSession()->pageTextContains('Test Tag Description');
    $this->assertSession()->pageTextContains('[test_tag]Content[/test_tag]');

    // Check that the tag can't be edited or deleted.
    $this->assertSession()->linkByHrefNotExists('admin/config/content/xbbcode/tags/manage/test_tag_id/edit');
    $this->assertSession()->linkByHrefNotExists('admin/config/content/xbbcode/tags/manage/test_tag_id/delete');
    $this->drupalGet('admin/config/content/xbbcode/tags/manage/test_tag_id/edit');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('admin/config/content/xbbcode/tags/manage/test_tag_id/delete');
    $this->assertSession()->statusCodeEquals(403);

    // Check for the View operation.
    $this->drupalGet('admin/config/content/xbbcode/tags');
    $this->assertSession()->linkByHrefExists('admin/config/content/xbbcode/tags/manage/test_tag_external/view');
    $this->drupalGet('admin/config/content/xbbcode/tags/manage/test_tag_external/view');
    $template = <<<'EOD'
{#
/**
 * @file
 * Test template.
 */
#}
<em>{{ tag.content }}</em>

EOD;
    $this->assertSession()->fieldValueEquals('template_code', $template);
    $fields = $this->xpath($this->assertSession()->buildXPathQuery(
      '//input[@name=:name][@value=:value][@disabled=:disabled]', [
        ':name' => 'op',
        ':value' => 'Save',
        ':disabled' => 'disabled',
      ]
    ));
    $this->assertNotEmpty($fields);

    $this->drupalGet('admin/config/content/xbbcode/tags');
    $this->clickLink('Create custom tag');
    $edit = $this->createCustomTag();

    // We should have been redirected to the tag list.
    // Our new custom tag is there.
    $this->assertSession()->assertEscaped($edit['label']);
    $this->assertSession()->assertEscaped($edit['description']);
    $this->assertSession()->assertEscaped(str_replace('{{ name }}', $edit['name'], $edit['sample']));
    // And so is the old one.
    $this->assertSession()->pageTextContains('[test_tag]Content[/test_tag]');

    $this->assertSession()->linkByHrefExists('admin/config/content/xbbcode/tags/manage/' . $edit['id'] . '/edit');
    $this->assertSession()->linkByHrefExists('admin/config/content/xbbcode/tags/manage/' . $edit['id'] . '/delete');

    $this->clickLink('Edit');

    // Check for the delete link on the editing form.
    $this->assertSession()->linkByHrefExists('admin/config/content/xbbcode/tags/manage/' . $edit['id'] . '/delete');

    // Edit the description and the name.
    $new_edit = [
      'label' => $this->randomString(),
      'description' => $this->randomString(),
      'name' => Unicode::strtolower($this->randomMachineName()),
    ];
    $this->drupalPostForm(NULL, $new_edit, t('Save'));

    $this->assertSession()->responseContains(new FormattableMarkup('The BBCode tag %tag has been updated.', ['%tag' => $new_edit['label']]));
    $this->assertSession()->assertNoEscaped($edit['description']);
    $this->assertSession()->assertEscaped($new_edit['description']);
    $this->assertSession()->assertEscaped(str_replace('{{ name }}', $new_edit['name'], $edit['sample']));

    // Delete the tag.
    $this->clickLink('Delete');
    $this->drupalPostForm(NULL, [], t('Delete'));
    $this->assertSession()->responseContains(new FormattableMarkup('The BBCode tag %tag has been deleted.', ['%tag' => $new_edit['label']]));
    // It's gone.
    $this->assertSession()->linkByHrefNotExists('admin/config/content/xbbcode/tags/manage/' . $edit['id'] . '/edit');
    $this->assertSession()->assertNoEscaped($new_edit['description']);

    // And the ID is available for re-use.
    $this->clickLink('Create custom tag');
    $this->drupalPostForm(NULL, $edit, t('Save'));
    // And it's back.
    $this->assertSession()->assertEscaped($edit['description']);
    $this->assertSession()->linkByHrefExists('admin/config/content/xbbcode/tags/manage/' . $edit['id'] . '/edit');

    $invalid_edit['name'] = $this->randomMachineName() . 'A';
    $this->clickLink('Edit');
    $this->drupalPostForm(NULL, $invalid_edit, t('Save'));

    $this->assertSession()->responseContains(new FormattableMarkup('The name [%name] must consist of lower-case letters, numbers and underscores.', ['%name' => $invalid_edit['name']]));

    $invalid_edit['name'] = Unicode::strtolower($this->randomMachineName()) . '!';
    $this->drupalPostForm(NULL, $invalid_edit, t('Save'));
    $this->assertSession()->responseContains(new FormattableMarkup('The name [%name] must consist of lower-case letters, numbers and underscores.', ['%name' => $invalid_edit['name']]));
  }

  /**
   * Test the plugin selection page.
   */
  public function testPlugins() {
    $tag = $this->createCustomTag();
    $tag2 = $this->createCustomTag();

    $this->drupalGet('filter/tips');
    $this->assertSession()->pageTextContains('BBCode is active, but no tags are available.');

    $this->drupalLogin($this->webUser);
    $this->drupalGet('node/add/page');
    // BBCode is the only format available:
    $this->assertSession()->pageTextContains('BBCode is active, but no tags are available.');

    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/content/xbbcode/settings');
    $this->assertSession()->checkboxNotChecked('edit-tags-test-plugin-id-status');
    $this->assertSession()->fieldValueEquals('tags[test_plugin_id][name]', 'test_plugin');
    $this->assertSession()->checkboxNotChecked('edit-tags-xbbcode-tagtest-tag-id-status');
    $this->assertSession()->fieldValueEquals('tags[xbbcode_tag:test_tag_id][name]', 'test_tag');
    $id = $tag['id'];
    $name = $tag['name'];
    $this->assertSession()->checkboxNotChecked('edit-tags-xbbcode-tag' . $id . '-status');
    $this->assertSession()->fieldValueEquals('tags[xbbcode_tag:' . $id . '][name]', $name);

    $new_name = Unicode::strtolower($this->randomMachineName());

    $invalid_edit['tags[test_plugin_id][name]'] = Unicode::strtolower($this->randomMachineName()) . 'A';
    $this->drupalPostForm(NULL, $invalid_edit, t('Save configuration'));
    $this->assertSession()->responseContains(new FormattableMarkup('The name [%name] must consist of lower-case letters, numbers and underscores.', [
      '%name' => $invalid_edit['tags[test_plugin_id][name]'],
    ]));

    $invalid_edit['tags[test_plugin_id][name]'] = Unicode::strtolower($this->randomMachineName()) . '!';
    $this->drupalPostForm(NULL, $invalid_edit, t('Save configuration'));
    $this->assertSession()->responseContains(new FormattableMarkup('The name [%name] must consist of lower-case letters, numbers and underscores.', [
      '%name' => $invalid_edit['tags[test_plugin_id][name]'],
    ]));

    $invalid_edit = [
      'tags[test_plugin_id][status]' => 1,
      'tags[test_plugin_id][name]' => 'abc',
      'tags[xbbcode_tag:test_tag_id][status]' => 1,
      'tags[xbbcode_tag:test_tag_id][name]' => 'abc',
      "tags[xbbcode_tag:{$id}][status]" => 1,
      "tags[xbbcode_tag:{$id}][name]" => 'def',
      "tags[xbbcode_tag:{$tag2['id']}][name]" => 'def',
    ];
    $this->drupalPostForm(NULL, $invalid_edit, t('Save configuration'));
    // Only find a collision between two active tags.
    $this->assertSession()->responseContains(new FormattableMarkup('The name [%name] is used by multiple tags.', ['%name' => 'abc']));
    $this->assertSession()->responseNotContains(new FormattableMarkup('The name [%name] is used by multiple tags.', ['%name' => 'def']));

    $this->drupalGet('admin/config/content/xbbcode/settings');
    $edit = [
      'tags[test_plugin_id][status]' => 1,
      'tags[test_plugin_id][name]' => $new_name,
      'tags[xbbcode_tag:test_tag_id][status]' => 1,
      'tags[xbbcode_tag:' . $id . '][status]' => 1,
    ];
    $this->drupalPostForm(NULL, $edit, 'Save configuration');
    $this->assertSession()->pageTextContains('The configuration options have been saved.');
    $this->assertSession()->checkboxChecked('edit-tags-test-plugin-id-status');
    $this->assertSession()->checkboxChecked('edit-tags-xbbcode-tagtest-tag-id-status');
    $this->assertSession()->checkboxChecked('edit-tags-xbbcode-tag' . $id . '-status');

    $this->drupalLogin($this->webUser);
    $this->drupalGet('filter/tips');
    $this->assertSession()->pageTextNotContains('BBCode is active, but no tags are available.');

    $this->assertSession()->responseContains("<strong>[$new_name]</strong>");
    $this->assertSession()->pageTextContains("[$new_name foo=bar bar=foo]Lorem Ipsum Dolor Sit Amet[/$new_name]");
    $this->assertSession()->responseContains('<span data-foo="bar" data-bar="foo">Lorem Ipsum Dolor Sit Amet</span>');

    $this->assertSession()->responseContains('<strong>[test_tag]</strong>');
    $this->assertSession()->pageTextContains('[test_tag]Content[/test_tag]');
    $this->assertSession()->responseContains('<strong>Content</strong>');

    $this->assertSession()->responseContains(new FormattableMarkup('<strong>[@name]</strong>', ['@name' => $name]));
    $sample = $tag['sample'];
    $this->assertSession()->assertEscaped(str_replace('{{ name }}', $name, $sample));
    $template_string = preg_replace('/^\[(.*?)\|.*$/', '$1', $tag['template_code']);
    $match = [];
    preg_match('/\[{{ name }}=(.*?)](.*?)\[\/{{ name }}\]/', $sample, $match);
    $this->assertSession()->pageTextContains("[$template_string|{$match[1]}|{$match[2]}]");

    $this->drupalGet('node/add/page');
    // BBCode is the only format available:
    $this->assertSession()->pageTextNotContains('BBCode is active, but no tags are available.');
    $this->assertSession()->responseContains(new FormattableMarkup('<abbr title="@desc">[@name]</abbr>', [
      '@desc' => $tag['description'],
      '@name' => $name,
    ]));
    $this->assertSession()->responseContains('<abbr title="Test Tag Description">[test_tag]</abbr>');
    $this->assertSession()->responseContains('<abbr title="Test Plugin Description">[' . $new_name . ']</abbr>');
  }

  /**
   * Tests the format-specific settings.
   */
  public function testFormatSettings() {
    // Set up a BBCode filter format.
    /** @var \Drupal\filter\FilterFormatInterface $xbbcode_format */
    $xbbcode_format = FilterFormat::create([
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
    $xbbcode_format->getPermissionName();
    user_role_grant_permissions('authenticated', [$xbbcode_format->getPermissionName()]);
    user_role_grant_permissions('anonymous', [$xbbcode_format->getPermissionName()]);

    // Rename the tag globally.
    $name1 = Unicode::strtolower($this->randomMachineName());
    $edit = [
      'tags[xbbcode_tag:test_tag_id][status]' => 1,
      'tags[xbbcode_tag:test_tag_id][name]' => $name1,
    ];
    $this->drupalPostForm('admin/config/content/xbbcode/settings', $edit, t('Save configuration'));

    // The validator must be called in this form too:
    $name = $this->randomMachineName() . 'A';
    $invalid_edit = [
      'filters[xbbcode][settings][tags][xbbcode_tag:test_tag_id][name]' => $name,
    ];
    $this->drupalPostForm('admin/config/content/formats/manage/xbbcode_test', $invalid_edit, t('Save configuration'));
    $this->assertSession()->responseContains(new FormattableMarkup('The name [%name] must consist of lower-case letters, numbers and underscores.', [
      '%name' => $name,
    ]));

    // Rename the tag in the second format.
    $name2 = Unicode::strtolower($this->randomMachineName());
    $edit = [
      'filters[xbbcode][settings][override]' => TRUE,
      'filters[xbbcode][settings][tags][xbbcode_tag:test_tag_id][status]' => TRUE,
      'filters[xbbcode][settings][tags][xbbcode_tag:test_tag_id][name]' => $name2,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));

    $this->drupalGet('filter/tips');

    // Ensure that both names are shown in the filter tips.
    $this->assertSession()->responseContains("<strong>[$name1]</strong>");
    $this->assertSession()->responseContains("<strong>[$name2]</strong>");
  }

}

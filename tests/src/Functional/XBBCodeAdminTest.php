<?php

namespace Drupal\Tests\xbbcode\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Unicode;
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
      'administer BBCode tag sets',
      'access site reports',
    ]);

    $this->webUser = $this->drupalCreateUser(['create page content', 'edit own page content']);
    $this->drupalLogin($this->adminUser);
    $this->drupalPlaceBlock('local_actions_block');

    $this->customTag = $this->createCustomTag(FALSE);
  }

  /**
   * Generate a custom tag and return it.
   *
   * @param bool $save
   *   Set to false to skip the save operation.
   *
   * @return array
   *   Information about the created tag.
   */
  private function createCustomTag($save = TRUE) {
    $name = Unicode::strtolower($this->randomMachineName());
    $option = $this->randomString();
    $tag = [
      'id' => Unicode::strtolower($this->randomMachineName()),
      'label' => $this->randomString(),
      'description' => $this->randomString(),
      'name' => $name,
      'sample' => "[{$name}='{$option}']" . $this->randomMachineName() . "[/{$name}]",
      'template_code' => '[' . $this->randomMachineName() . '|{{ tag.option }}|{{ tag.content }}]',
    ];
    if ($save) {
      $this->drupalPostForm('admin/config/content/xbbcode/tags/add', $tag, t('Save'));
      $this->assertSession()->responseContains(new FormattableMarkup('The BBCode tag %tag has been created.', ['%tag' => $tag['label']]));
    }
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
    $this->assertSession()->fieldValueEquals('template_code', rtrim($template));
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
    $this->assertSession()->assertEscaped($edit['sample']);
    // And so is the old one.
    $this->assertSession()->pageTextContains('[test_tag]Content[/test_tag]');

    $this->assertSession()->linkByHrefExists('admin/config/content/xbbcode/tags/manage/' . $edit['id'] . '/edit');
    $this->assertSession()->linkByHrefExists('admin/config/content/xbbcode/tags/manage/' . $edit['id'] . '/delete');

    $this->clickLink('Edit');

    // Check for the delete link on the editing form.
    $this->assertSession()->linkByHrefExists('admin/config/content/xbbcode/tags/manage/' . $edit['id'] . '/delete');

    $name = Unicode::strtolower($this->randomMachineName());

    // Edit the description and the name.
    $new_edit = [
      'label' => $this->randomString(),
      'description' => $this->randomString(),
      'name' => $name,
      'sample' => str_replace($edit['name'], $name, $edit['sample']),
    ];
    $this->drupalPostForm(NULL, $new_edit, t('Save'));

    $this->assertSession()->responseContains(new FormattableMarkup('The BBCode tag %tag has been updated.', ['%tag' => $new_edit['label']]));
    $this->assertSession()->assertNoEscaped($edit['description']);
    $this->assertSession()->assertEscaped($new_edit['description']);
    $this->assertSession()->assertEscaped($new_edit['sample']);

    // Delete the tag.
    $this->clickLink('Delete');
    $this->drupalPostForm(NULL, [], t('Delete'));
    $this->assertSession()->responseContains(new FormattableMarkup('The custom tag %tag has been deleted.', ['%tag' => $new_edit['label']]));
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

    $this->assertSession()->responseContains(new FormattableMarkup('%name field is not in the right format.', ['%name' => 'Default name']));

    $invalid_edit['name'] = Unicode::strtolower($this->randomMachineName()) . '!';
    $this->drupalPostForm(NULL, $invalid_edit, t('Save'));
    $this->assertSession()->responseContains(new FormattableMarkup('%name field is not in the right format.', ['%name' => 'Default name']));
  }

  /**
   * Test the global default plugins.
   */
  public function testGlobalPlugins() {
    // By default, we have the tags from the test module.
    $this->drupalGet('filter/tips');
    $this->assertSession()
         ->pageTextContains('You may use the following BBCode tags:');
    $this->assertSession()->pageTextContains('[test_plugin]');
    $this->assertSession()->pageTextContains('[test_tag]');
    $this->assertSession()->pageTextContains('[test_template]');

    $tag = $this->createCustomTag();

    // Newly created tags are enabled by default.
    $this->drupalGet('filter/tips');
    $this->assertSession()->pageTextContains("{$tag['name']}");

    $this->drupalLogin($this->webUser);
    $this->drupalGet('node/add/page');
    // BBCode is the only format available:
    $this->assertSession()
         ->pageTextContains('You may use the following BBCode tags:');
    $this->assertSession()->pageTextContains('[test_plugin]');
  }

  /**
   * Create and edit a tag set.
   */
  public function testTagSet() {
    $tag = $this->createCustomTag();
    $tags = [
      'test_plugin_id'                => 'test_plugin',
      'xbbcode_tag:test_tag_id'       => 'test_tag',
      'xbbcode_tag:test_tag_external' => 'test_template',
      "xbbcode_tag:{$tag['id']}"      => $tag['name'],
    ];

    $this->drupalGet('admin/config/content/xbbcode/sets');
    $this->assertSession()->pageTextContains('There is no tag set yet.');

    $this->clickLink('Create tag set');
    // There is a checkbox for the format.
    $this->assertSession()->checkboxNotChecked('formats[xbbcode]');
    foreach ($tags as $id => $name) {
      $this->assertSession()->checkboxNotChecked("_tags[available:{$id}]");
      $this->assertSession()->fieldValueEquals("_settings[available:{$id}][name]", $name);
    }

    $tag_set = [
      'label'            => $this->randomString(),
      'id'               => Unicode::strtolower($this->randomMachineName()),
      'formats[xbbcode]' => 1,
    ];
    $this->drupalPostForm(NULL, $tag_set, t('Save'));
    $this->assertSession()->responseContains(new FormattableMarkup('The BBCode tag set %set has been created.', ['%set' => $tag_set['label']]));
    $this->assertSession()->pageTextContains('None');

    // The empty tag set is now selected in the format.
    $this->drupalGet('filter/tips');
    $this->assertSession()->pageTextContains('BBCode is active, but no tags are available.');

    $this->drupalGet('admin/config/content/xbbcode/sets');
    $this->clickLink('Edit');

    // The format is checked now.
    $this->assertSession()->checkboxChecked('formats[xbbcode]');

    $invalid_edit = [
      '_settings[available:test_plugin_id][name]' => Unicode::strtolower($this->randomMachineName()) . 'A',
    ];
    $this->drupalPostForm(NULL, $invalid_edit, t('Save'));
    $this->assertSession()->responseContains(new FormattableMarkup('%name field is not in the right format.', ['%name' => 'Tag name']));

    // Give the four available plugins two names, and enable the first three.
    $invalid_edit = [];
    foreach (array_keys($tags) as $i => $id) {
      $invalid_edit["_settings[available:{$id}][name]"] = $i >= 2 ? 'def' : 'abc';
      $invalid_edit["_tags[available:{$id}]"] = $i <= 2;
    }

    $this->drupalPostForm(NULL, $invalid_edit, t('Save'));
    // Only enabled plugins need unique names.
    $this->assertSession()->responseContains(new FormattableMarkup('The name [@name] is used by multiple tags.', ['@name' => 'abc']));
    $this->assertSession()->responseNotContains(new FormattableMarkup('The name [@name] is used by multiple tags.', ['@name' => 'def']));

    $this->drupalGet('admin/config/content/xbbcode/sets');
    $this->clickLink('Edit');

    // Enable only our custom tag.
    $edit = [
      "_tags[available:xbbcode_tag:{$tag['id']}]" => 1,
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertSession()->responseContains(new FormattableMarkup('The BBCode tag set %set has been updated.', ['%set' => $tag_set['label']]));
    $this->assertSession()->pageTextContains("[{$tag['name']}]");
    $this->assertSession()->pageTextNotContains('[test_tag]');
    $this->assertSession()->pageTextNotContains('[test_template]');
    $this->assertSession()->pageTextNotContains('[test_plugin]');

    // The filter tips are updated; only the custom tag is enabled.
    $this->drupalGet('filter/tips');
    $this->assertSession()->responseContains("<strong>[{$tag['name']}]</strong>");
    $this->assertSession()->pageTextContains($tag['sample']);
    $this->assertSession()->pageTextContains($tag['description']);
    $this->assertSession()->pageTextNotContains('[test_tag]');
    $this->assertSession()->pageTextNotContains('[test_template]');
    $this->assertSession()->pageTextNotContains('[test_plugin]');

    $this->drupalLogin($this->webUser);
    $this->drupalGet('node/add/page');
    // BBCode is the only format available:
    $this->assertSession()
         ->pageTextContains('You may use the following BBCode tags:');
    $this->assertSession()->responseContains(new FormattableMarkup('<abbr title="@desc">[@name]</abbr>', [
      '@desc' => $tag['description'],
      '@name' => $tag['name'],
    ]));

    // Delete the tag set.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/content/xbbcode/sets');
    $this->clickLink('Delete');
    $this->drupalPostForm(NULL, [], 'Delete');
    $this->assertSession()->responseContains(new FormattableMarkup('The tag set %name has been deleted.', ['%name' => $tag_set['label']]));

    // Without a tag set, all tags are enabled again.
    $this->drupalGet('filter/tips');
    $this->assertSession()->responseContains("<strong>[{$tag['name']}]</strong>");
    $this->assertSession()->pageTextContains($tag['sample']);
    $this->assertSession()->pageTextContains($tag['description']);
    $this->assertSession()->pageTextContains('[test_tag]');
    $this->assertSession()->pageTextContains('[test_template]');
    $this->assertSession()->pageTextContains('[test_plugin]');
  }

}

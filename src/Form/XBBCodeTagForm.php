<?php

/**
 * @file
 * Contains \Drupal\xbbcode\Form\XBBCodeTagForm.
 */

namespace Drupal\xbbcode\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * List custom tags and edit or delete them.
 */
class XBBCodeTagForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'xbbcode_tags';
  }

  /**
   * {@inheritdoc}
   *
   * @param $name
   *   If passed, load this tag for editing. Otherwise, list all tags and show a
   *   collapsed tag creation form.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $name = NULL) {
    module_load_include('inc', 'xbbcode', 'xbbcode.crud');
    // Determine whether the user has loaded an existing tag for editing (via edit link).
    $editing_tag = !empty($name);
    // If the form was submitted, then a new tag is being added.
    $adding_tag = $form_state->getValue('op') == t('Save');
    $access_php = module_exists('php') && user_access('use PHP for settings');
    $use_php = FALSE;

    // The upshot is that if a tag is being edited or added, the otherwise optional fields become required.

    // If editing a tag, load this tag and populate the form with its values.
    if ($editing_tag) {
      $tag = xbbcode_custom_tag_load($name);
      $use_php = $tag->options['php'];
      $form['edit'] = [
        '#type' => 'fieldset',
        '#title' => t('Editing Tag %name', ['%name' => $name]),
        '#collapsible' => FALSE,
      ];
    }
    else {
      $tags = array_keys(xbbcode_custom_tag_load());

      // If any tags already exist, build a list for deletion and editing.
      if (!empty($tags)) {
        foreach ($tags as $tag) {
          $options[$tag] = '[' . $tag . '] ' . l(t('Edit'), "admin/config/content/xbbcode/tags/$tag/edit");
        }
        $form['existing'] = [
          '#type' => 'checkboxes',
          '#title' => t('Existing tags'),
          '#description' => t('Check these tags and click "Delete" to delete them.'),
          '#options' => $options,
        ];
      }
      else {
        // If no tags exist, then a new tag must be added now.
        $adding_tag = TRUE;
      }

      $form['edit'] = [
        '#type' => 'fieldset',
        '#title' => t('Create new tag'),
        '#collapsible' => TRUE,
        '#collapsed' => count($tags),
      ];

      // Create an empty tag.
      $tag = (object)[
        'name' => '',
        'description' => '',
        'markup' => '',
        'sample' => '',
      ];
    }

    // Regardless of whether a new tag or an existing tag is being edited,
    // show the edit form now. The fields are required only if a new tag is being
    // saved (during the submission phase), or if an existing tag is being edited.

    $form['edit']['name'] = [
      '#type' => 'textfield',
      '#default_value' => $tag->name,
      '#field_prefix' => '[',
      '#field_suffix' => ']',
      '#required' => $editing_tag || $adding_tag,
      '#maxlength' => 32,
      '#size' => 16,
      '#description' => t('The name of this tag. The name will be used in the text as [name]...[/name]. Must be alphanumeric and will automatically be converted to lowercase.'),
    ];

    $form['edit']['description'] = [
      '#type' => 'textarea',
      '#title' => t('Description'),
      '#default_value' => $tag->description,
      '#required' => $editing_tag || $adding_tag,
      '#description' => t('This will be shown on help pages'),
    ];

    $form['edit']['sample'] = [
      '#type' => 'textfield',
      '#title' => t('Sample tag'),
      '#required' => $editing_tag || $adding_tag,
      '#description' => t('Enter an example of how this tag would be used. It will be shown on the help pages.'),
      '#default_value' => $tag->sample,
    ];

    $form['edit']['options'] = [
      '#type' => 'checkboxes',
      '#title' => t('Tag options'),
      '#options' => [
        'selfclosing' => t('Tag is self-closing (requires no closing tag, like <code>[img]</code>).'),
        'nocode' => t('Ignore further BBCode inside this tag.'),
        'plain' => t('Escape all HTML inside this tag.'),
      ],
      '#description' => t('The last two options should in most cases be used together. Note that HTML will not be escaped twice even if this tag is used in a format that allows no HTML in the first place.'),
    ];

    $form['edit']['php'] = [
      '#type' => 'checkbox',
      '#title' => t('Evaluate as PHP code.'),
      '#return_value' => TRUE,
      '#description' => t('This option requires the PHP module to be enabled, and the appropriate permission.'),
      '#default_value' => $use_php,
    ];

    foreach ($form['edit']['options']['#options'] as $key => $value) {
      if (!empty($tag->options[$key])) {
        $form['edit']['options']['#default_value'][] = $key;
      }
    }

    $form['edit']['markup'] = [
      '#type' => 'textarea',
      '#attributes' => ['style' => 'font-family:monospace'],
      '#title' => t('Rendering code'),
      '#default_value' => $tag->markup,
      '#required' => $editing_tag || $adding_tag,
      '#description' => t('The text that [tag]content[/tag] should be replaced with, or PHP code that prints/returns the text.', ['@url' => url('admin/help/xbbcode')]),
    ];

    if (!$access_php) {
      $form['edit']['php']['#disabled'] = TRUE;
      $form['edit']['php']['#value'] = $form['edit']['php']['#default_value'];
      // Imitate the behavior of filter.module on forbidden formats.
      if ($use_php) {
        $form['edit']['markup']['#disabled'] = TRUE;
        $form['edit']['markup']['#resizable'] = FALSE;
        $form['edit']['markup']['#value'] = $form['edit']['markup']['#default_value'];
        $form['edit']['markup']['#pre_render'] = ['filter_form_access_denied'];
      }
    }

    $form['edit']['help'] = [
      '#type' => 'markup',
      '#title' => t('Coding help'),
      '#markup' => t('<p>The above field should be filled either with HTML or PHP code depending on whether you enabled the PHP code option. PHP code must be placed in &lt;?php ?&gt; enclosures, or it will be
      printed literally.</p>
      <p>If your tag uses static HTML, then the tag\'s content and attributes will be inserted into your code by replacing placeholders. In PHP code, they will be available in the <code>$tag</code> object.</p>
      <dl>
        <dt><code>{content}</code> or <code>$tag->content</code></dt>
        <dd>The text between opening and closing tags, if the tag is not self-closing. Example: <code>[url=http://www.drupal.org]<strong>Drupal</strong>[/url]</code></dd>
        <dt><code>{option}</code> or <code>$tag->option</code></dt>
        <dd>The single tag attribute, if one is entered. Example: <code>[url=<strong>http://www.drupal.org</strong>]Drupal[/url]</code>.</dd>
        <dt>any other <code>{attribute}</code> or <code>$tag->attr(\'attribute\')</code></dt>
        <dd>The tag attribute of the same name, if it is entered. E.g: <strong>{by}</strong> or <strong><code>$tag->attr(\'by\')</code></strong> for <code>[quote&nbsp;by=<strong>Author</strong>&nbsp;date=2008]Text[/quote]</code>. If the attribute is not entered, the placeholder will be replaced with an empty string, and the <code>attr()</code> return value will be <code>NULL</code>.</dd>
      </dl>'),
    ];

    $form['edit']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Save'),
      '#submit' => ['::_submitFormSave'],
    ];

    if (!empty($name) || count($tags)) {
      $delete = [
        '#type' => 'submit',
        '#value' => t('Delete'),
        '#submit' => ['::_submitFormDelete'],
      ];
      if (!empty($name)) {
        $form['edit']['delete'] = $delete;
      }
      else {
        $form['delete'] = $delete;
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $name = $form_state->getValue('name');
    if (!preg_match('/^[a-z0-9]*$/i', $name)) {
      $form_state->setErrorByName('name', t('The name must be alphanumeric.'));
    }

    if ($form['edit']['name']['#default_value'] != $name) {
      if (xbbcode_custom_tag_exists($name)) {
        $form_state->setErrorByName('name', t('This name is already taken. Please delete or edit the old tag, or choose a different name.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

  /**
   * Delete selected custom tags.
   */
  public function _submitFormDelete(array &$form, FormStateInterface $form_state) {
    $delete = [];

    if (!empty($form_state->getValue('name'))) {
      $delete[] = $form_state->getValue('name');
    }
    elseif (is_array($form_state->getValue('existing'))) {
      foreach ($form_state->getValue('existing') as $tag => $checked) {
        if ($checked) {
          $delete[] = $tag;
        }
      }
    }

    xbbcode_custom_tag_delete($delete);

    $tags = '[' . implode('], [', $delete) . ']';

    drupal_set_message(format_plural(count($delete), 'The tag %tags has been deleted.', 'The tags %tags have been deleted.', ['%tags' => $tags]), 'status');
    drupal_static_reset('xbbcode_custom_tag_load');
    xbbcode_rebuild_handlers();
    xbbcode_rebuild_tags();
  }

  /**
   * Save (create or update) a custom tag.
   */
  public function _submitFormSave(array &$form, FormStateInterface $form_state) {
    $tag = (object) $form_state->getValues();
    $tag->name = strtolower($tag->name);
    foreach ($tag->options as $name => $value) {
      $tag->options[$name] = $value ? 1 : 0;
    }
    $tag->options['php'] = $tag->php;

    if (xbbcode_custom_tag_save($tag)) {
      if ($form['edit']['name']['#default_value']) {
        drupal_set_message(t('Tag [@name] has been changed.', ['@name' => $tag->name]));
      }
      else {
        drupal_set_message(t('Tag [@name] has been created.', ['@name' => $tag->name]));
      }
    }
    $form_state->setRedirect('xbbcode.admin_tags');
    drupal_static_reset('xbbcode_custom_tag_load');
    xbbcode_rebuild_handlers();
    xbbcode_rebuild_tags();
  }
}

/**
 * @file
 * Add dynamic behavior to the custom tag form.
 */

(function ($) {
  'use strict';
  Drupal.behaviors.xbbcode_tag = {
    attach: function (context) {
      const sampleField = $(context).find('[data-drupal-selector=edit-sample]');
      const nameField = $(context).find('[data-drupal-selector=edit-name]');

      const getTemplate = function () {
        return sampleField.val().replace(
          /(\[\/?)([a-z0-9_-]*)(?=[\]\s=])/g,
          function (match, prefix, name) {
            return name === nameValue ? prefix + '{{ name }}' : match;
          }
        );
      };

      var nameValue = nameField.val();
      var template = getTemplate();

      nameField.keyup(function () {
        // Only update with a valid name.
        if (nameField.val().match(/^[a-z0-9_-]+$/)) {
          nameValue = nameField.val();
          sampleField.val(template.replace(/{{ name }}/g, nameValue));
          // Reparse, in case the new name was already used.
          template = getTemplate();
        }
      });
      sampleField.change(function () {
        template = getTemplate();
      })
    }
  };
})(jQuery);

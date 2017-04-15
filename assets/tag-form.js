'use strict';

/**
 * @file
 * Add dynamic behavior to the custom tag form.
 */

(function ($) {
  Drupal.behaviors.xbbcode_tag = {
    attach: function attach(context) {
      var getTemplate = function getTemplate() {
        return sampleField.val().replace(/(\[\/?)([a-z0-9_-]*)(?=[\]\s=])/g, function (match, prefix, name) {
          return name === nameValue ? prefix + '{{ name }}' : match;
        });
      };

      var sampleField = $(context).find('[data-drupal-selector=edit-sample]');
      var nameField = $(context).find('[data-drupal-selector=edit-name]');
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
      });
    }
  };
})(jQuery);

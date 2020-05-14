/**
 * @file
 * Add dynamic behavior to the custom tag form.
 */

($ => {
  Drupal.behaviors.xbbcode_tag = {
    attach: context => {
      const getTemplate = () => sampleField.val().replace(
        /(\[\/?)([a-z0-9_-]*)(?=[\]\s=])/g,
        (match, prefix, name) => (name === nameValue ? prefix + '{{ name }}' : match)
      );

      const sampleField = $(context).find('[data-drupal-selector=edit-sample]');
      const nameField = $(context).find('[data-drupal-selector=edit-name]');
      let nameValue = nameField.val();
      let template = getTemplate();

      nameField.keyup(() => {
        // Only update with a valid name.
        if (nameField.val().match(/^[a-z0-9_-]+$/)) {
          nameValue = nameField.val();
          sampleField.val(template.replace(/{{ name }}/g, nameValue));
          // Reparse, in case the new name was already used.
          template = getTemplate();
        }
      });
      sampleField.change(() => {
        template = getTemplate();
      });
    }
  };
})(jQuery);

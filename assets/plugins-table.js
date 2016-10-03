/**
 * @file
 * Add dynamic behavior to the xbbcode settings pages.
 */

(function ($) {
  'use strict';
  Drupal.behaviors.xbbcode = {
    attach: function () {
      $('#xbbcode-plugins td.name-selector').each(function () {
        var fieldWrapper = $('div.form-type-textfield', this);
        var edit = $(this).find('span.edit');
        var reset = $(this).find('a[data-action=reset]');
        var field = $('input.form-text', fieldWrapper);
        var name = field.val();
        var defaultName = field.attr('default');
        $(fieldWrapper).toggle(name !== defaultName);
        $(reset).toggle(name !== defaultName);
        edit.toggle(name === defaultName);
        $('a[data-action=edit]', this).click(function (e) {
          $(fieldWrapper).show();
          $(reset).show();
          edit.hide();
          e.preventDefault();
        });
        reset.click(function (e) {
          $(fieldWrapper).hide();
          $(reset).hide();
          edit.show();
          field.val(defaultName);
          e.preventDefault();
        });
      });
    }
  };
})(jQuery);

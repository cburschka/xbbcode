/**
 * @file
 * Add dynamic behavior to the xbbcode settings pages.
 */

(function ($) {
  Drupal.behaviors.xbbcode = {
    attach: function() {
      $('#xbbcode-plugins td.name-selector').each(function() {
        var fieldWrapper = $('div.form-type-textfield', this);
        var edit = $(this).find('span.edit');
        var reset = $(this).find('a[action=reset]');
        var field = $('input.form-text', fieldWrapper);
        var name = field.val();
        var resetName = field.attr('data-reset');
        $(fieldWrapper).toggle(name !== resetName);
        $(reset).toggle(name !== resetName);
        edit.toggle(name === resetName);
        $('a[action=edit]', this).click(function(e) {
          $(fieldWrapper).show();
          $(reset).show();
          edit.hide();
          e.preventDefault();
        });
        reset.click(function(e) {
          $(fieldWrapper).hide();
          $(reset).hide();
          edit.show();
          field.val(resetName);
          e.preventDefault();
        });
      });
    }
  };
})(jQuery);

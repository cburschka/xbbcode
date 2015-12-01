/**
 * @file
 * Add dynamic behavior to the xbbcode settings pages.
 */

(function ($) {
  Drupal.behaviors.xbbcode = {
    attach: function() {
      $('#xbbcode-plugins input.form-checkbox').change(function() {
        var status = this.checked;
        $(this).parents('tr').find('input.form-text')
                .prop('required', status)
                .prop('disabled', !status)
                .parent().toggleClass('form-disabled', !status);
      });
      $('#xbbcode-plugins td.name-selector').each(function() {
        var fieldWrapper = $('div.form-type-textfield', this);
        var edit = $($(this).find('div.form-type-item')[0]);
        var reset = $($(this).find('div.form-type-item')[1]);
        console.log(edit, reset);
        var field = $('input.form-text', fieldWrapper);
        var name = field.val();
        var defaultName = field.attr('default');
        $(fieldWrapper).toggle(name !== defaultName);
        $(reset).toggle(name !== defaultName);
        edit.toggle(name === defaultName);
        $('a[action=edit]', this).click(function(e) {
          $(fieldWrapper).show();
          $(reset).show();
          edit.hide();
          e.preventDefault();
        });
        $('a[action=reset]', this).click(function(e) {
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


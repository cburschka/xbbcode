/**
 * @file
 * Add dynamic behavior to the xbbcode settings pages.
 */

(function ($) {
  Drupal.behaviors.xbbcode = {
    attach: function() {
      $('#xbbcode-handlers input.form-checkbox').change(function() {
        var status = this.checked;
        $(this).parents('tr').find('input.form-text')
                .prop('required', status)
                .prop('disabled', !status)
                .parent().toggleClass('form-disabled', !status);
      });
      $('#xbbcode-handlers a').click(function(e) {
        $(this).parents('td').siblings('input.form-text').val($(this).attr('default'));
        e.preventDefault();
      });
    }
  };
})(jQuery);


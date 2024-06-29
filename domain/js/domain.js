/**
 * @file
 * Attaches behaviors for the Domain module.
 */
(function ($) {
  /**
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.domainSettingsSummaries = {
    attach() {
      // The drupalSetSummary method required for this behavior is not available
      // on the Blocks administration page, so we need to make sure this
      // behavior is processed only if drupalSetSummary is defined.
      if (typeof jQuery.fn.drupalSetSummary === 'undefined') {
        return;
      }

      // There may be an easier way to do this. Right now, we just copy code
      // from block module.
      function checkboxesSummary(context) {
        const vals = [];
        const $checkboxes = $(context).find(
          'input[type="checkbox"]:checked + label',
        );
        const il = $checkboxes.length;
        for (let i = 0; i < il; i++) {
          vals.push($($checkboxes[i]).html());
        }
        if (!vals.length) {
          vals.push(Drupal.t('Not restricted'));
        }
        return vals.join(', ');
      }

      $('[data-drupal-selector="edit-visibility-domain"]').drupalSetSummary(
        checkboxesSummary,
      );
    },
  };
})(jQuery);

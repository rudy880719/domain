/**
 * @file
 * Attaches behaviors for the Domain module.
 */

(function ($) {
  /**
   * Provide the summary information for the block settings vertical tabs.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for the block settings summaries.
   */
  Drupal.behaviors.domainSettingsSummaries = {
    attach() {
      // The drupalSetSummary method required for this behavior is not available
      // on the Blocks administration page, so we need to make sure this
      // behavior is processed only if drupalSetSummary is defined.
      if (typeof $.fn.drupalSetSummary === 'undefined') {
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

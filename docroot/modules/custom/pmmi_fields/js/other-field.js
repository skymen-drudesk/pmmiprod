/**
 * @file
 * File for other checkbox field widget stuff.
 */

(function ($, window, Drupal) {
  'use strict';

  /**
   * Attaches states for "other" checkboxes value.
   */
  Drupal.behaviors.pmmiFieldsOtherChecckbox = {
    attach: function (context, settings) {
      var $states = $(context).find('[data-other-field]');
      var il = $states.length;
      for (var i = 0; i < il; i++) {
        var field = $states[i].getAttribute('data-other-field');
        var constraints = {};
        constraints[':input[name^="' + $($states[i]).attr('name') + '"]'] = {
          checked: true
        };
        new Drupal.states.Dependent({
          element: $('*[name*="' + field + '"]'),
          state: 'visible',
          constraints: constraints
        });
      }

      // Execute all postponed functions now.
      while (Drupal.states.postponed.length) {
        (Drupal.states.postponed.shift())();
      }
    }
  };

})(jQuery, window, Drupal);

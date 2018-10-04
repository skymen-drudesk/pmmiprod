/**
 * @file
 * JavaScript behaviors for Ajax.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Command to close a dialog.
   *
   * If no selector is given, it defaults to trying to close the modal.
   *
   * @param {Drupal.Ajax} [ajax]
   * @param {object} response
   * @param {string} response.selector
   * @param {bool} response.persist
   * @param {string} [status]
   */
  Drupal.AjaxCommands.prototype.pageReload = function (ajax, response, status) {
    if (status === 'success') {
      location.reload();
    }
  };


})(jQuery, Drupal);

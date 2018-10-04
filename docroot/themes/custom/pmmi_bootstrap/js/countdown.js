/**
 * @file
 * PMMI countdown js.
 */

(function ($, window, Drupal, moment) {
  'use strict';

  /**
   * Countdown behavior.
   */
  Drupal.behaviors.pmmiCountdown = {
    attach: function ($context) {
      if (jQuery.fn.countdown) {
        $('.block-countdown, .block-countdown-circle').once('countdown').each(function () {
          var $time = $('.countdown', $(this));
          var time = $time.find('.date').text();
          var timeZone = $time.find('.timezone').text();
          time = moment.tz(time, timeZone);
          $time.countdown(time.toDate(), {elapse: true}).on('update.countdown', function (event) {
            $(this).html(event.strftime(''
              + '<div class="part days">' +
                  '<span class="time">%D</span><span class="unit">day%!D</span>' +
                '</div>'
              + '<div class="part hrs">' +
                  '<span class="time">%H</span><span class="unit">hr%!H</span>' +
                '</div>'
              + '<div class="part min">' +
                  '<span class="time">%M</span><span class="unit">min</span>' +
                '</div>'
              + '<div class="part sec">' +
                  '<span class="time">%S</span><span class="unit">sec</span>' +
                '</div>'
            ));
          });
        });
      }
    }
  };



})(jQuery, window, Drupal, window.moment);

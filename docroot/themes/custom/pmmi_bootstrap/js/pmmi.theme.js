/**
 * @file
 * PMMI theme js.
 */

(function ($, window, Drupal) {
  'use strict';

  /**
   * Attaches the iFrame resize behaviour for video field.
   */
  Drupal.behaviors.pmmiFrameResize = {
    attach: function (context, settings) {
      var $videoFrame = $('.field-name-field-video, .field--name-body').find('iframe');
      $videoFrame.once('video-resize').each(function () {
        var $thisFrame = $(this);
        var defaultDimensions = {
          width: $thisFrame.prop('width'),
          height: $thisFrame.prop('height')
        };
        var aspectRatio = defaultDimensions.width / defaultDimensions.height;
        $(window).once().on('resize load update', function (e, data) {
          if (e.type === 'update' && data.$videoFrame) {
            $thisFrame = data.$videoFrame;
          }
          if ($(window).width() < 1200) {
            var parentWidth = $thisFrame.parent().width();
            $thisFrame.width(parentWidth).height(parentWidth / aspectRatio);
          }
          else {
            $thisFrame.width(defaultDimensions.width).height(defaultDimensions.height);
          }
        });
      });
    }
  };

  /**
   * Attaches the video update behaviour for videos view on video node.
   */
  Drupal.behaviors.pmmiVideoUpdateAjax = {
    attach: function (context, settings) {
      // Add ajax links to videos view on Video node.
      if ($(context).find('#video-node-info').length) {
        $('html, body').animate({
          scrollTop: $('#video-node-info').offset().top
        }, 100);
        $(window).trigger('update', {$videoFrame: $(context).find('iframe')});
      }
      $('#videos-view').once('video-view').each(function () {
        $(this).find('.default-mode-node').once('ajax').each(function () {
          var $item = $(this);
          var nodeID = $item.data('item-id');
          var title = $item.find('.field-name-node-title').text();
          $item.find('.field-name-node-link a, .field-name-field-video a').each(function () {
            $(this).prop('data-href', $(this).attr('href'))
              .prop('href', Drupal.url('pmmi-fields/replace-video/nojs/' + nodeID))
              .addClass('use-ajax');
          }).click(function () {
            if (window.history.pushState) {
              history.pushState({}, title, $(this).prop('data-href'));
              var titleParts = $(document).prop('title').split('|');
              titleParts[0] = title;
              $(document).prop('title', titleParts.join('|'));
            }
          });
        });
        Drupal.behaviors.AJAX.attach(context, settings);
      });
    }
  };

  /**
   * Common JS for elements theming.
   */
  Drupal.behaviors.pmmiCommonTheme = {
    attach: function (context, settings) {
      $('select').once('uniform').each(function () {
        $(this).uniform();
      });
      $('input[type="checkbox"]').once('uniform').each(function () {
        var $checkBox = $(this);
        var $checkBoxParent = $checkBox.closest('.form-type-checkbox');
        if ($checkBox.is(':checked')) {
          $checkBoxParent.addClass('checked');
        }
        $checkBox.uniform();
        $checkBox.on('change', function () {
          if ($checkBox.is(':checked')) {
            $checkBoxParent.addClass('checked');
          }
          else {
            $checkBoxParent.removeClass('checked');
          }
        });
      });
      $('input[type="radio"]').once('uniform').each(function () {
        var $radio = $(this);
        var $radios = $('input[name="' + $radio.prop('name') + '"]');
        if ($radio.is(':checked')) {
          $radio.closest('.control-label').addClass('checked');
        }
        $radio.uniform();
        var $radiosGroup = $radios.closest('.form-item').parent();
        $radiosGroup.once('radio-group').each(function () {
          $(this).find('input:radio').on('change', function () {
            $radiosGroup.find('.checked').removeClass('checked');
            var $radioParent = $(this).closest('.control-label');
            if ($(this).is(':checked')) {
              $radioParent.addClass('checked');
            }
            else {
              $radioParent.removeClass('checked');
            }
          });
        });
      });
      var $expandedList = $('.pmmi-company-search-block-form .js-form-wrapper, .industries-served-details.js-form-wrapper, .equipment-sold-details.js-form-wrapper');
      var $clicker = $('.panel-heading a', $expandedList);
      $clicker.once('pmmiCommonTheme').click(function () {
        $(this).closest('.js-form-wrapper').toggleClass('active');
      });
      // Equal heights for blocks inside containers.
      $('.containers .row').once('matchHeight').each(function () {
        var $row = $(this);
        var $socialBlock = $row.find('.social-block');
        var $containerBlocks = $socialBlock.add('.viewfield-wrapper, .match-height, .mode-container-mode', $row);
        if ($containerBlocks.length) {
          var $textBlock = $row.find('.block-text');
          $containerBlocks = $containerBlocks.add($textBlock);
        }
        $row.imagesLoaded().always(function () {
          if ($containerBlocks.length) {
            $('.col > .field > *', $row).matchHeight();
          }
          var $matchHeightBlock = $('.match-height, .match-height-parent > *', $row);
          if ($matchHeightBlock.length) {
            $matchHeightBlock.matchHeight();
          }
        });
        $.fn.matchHeight._beforeUpdate = function (event, groups) {
          $socialBlock.each(function () {
            $(this).removeAttr('style');
          });
        };
        $.fn.matchHeight._afterUpdate = function (event, groups) {
          $socialBlock.each(function () {
            var $block = $(this);
            var $parent = $block.parent();
            var $title = $parent.find('.block-title');
            var newHeight = $parent.height() - $title.outerHeight(true);
            $block.height(newHeight);
            var messagesHeight = 0;
            $('.message', $block).each(function () {
              messagesHeight += $(this).outerHeight();
            });
            if (messagesHeight > newHeight) {
              $block.addClass('scroll');
            }
            else {
              $block.removeClass('scroll');
            }
          });
        };
      });
      // Apple Safari 8 svg issue workaround.
      if (navigator.userAgent.match(/safari/i) && navigator.vendor.match(/apple/i)) {
        $('.logo img[src*=".svg"]').once('svg-issue').each(function () {
          $(this).hide().after('<object data="' + $(this).attr('src') + '" type="image/svg+xml"></object>');
        });
      }
      // Check if touch device.
      if ('ontouchstart' in window || navigator.maxTouchPoints) {
        $('html').addClass('touchevents');
      }

      // Responsive tables.
      $('table.cardtable').once('stack-columns').each(function (e) {
        $(this).cardtable();
      });
      $('table').once('responsive').each(function () {
        var $table = $(this);
        if ($table.width() > $table.parent().width()) {
          $table.addClass('scrolled');
        }
      });
      // Selectize tuning.
      $('.selectize-input .item a').on('click', function (e) {
        setTimeout(function () {
          $('.selectize-dropdown').hide();
        }, 50);
      });
    }
  };


  /**
   * Cards block js stuff.
   *
   * @todo Implement responsive update based on drupal breakpoints.
   */
  Drupal.behaviors.pmmiCardsBlcok = {
    attach: function (context, settings) {
      var _this = this;
      $('.block-card').closest('.row').once('equal-height').each(function () {
        var $row = $(this);
        $(window).on('breakpointActivated', function (e, breakpoint) {
          _this.alignCards($row, breakpoint);
        });
      });
    },
    alignCards: function ($row, breakpoint) {
      _.delay(function () {
        $row.find('.flipper').removeAttr('style');
        var maxCardHeidht = 0;
        $('.card-content', $row).each(function () {
          var $cardContent = $(this);
          var $cardParent = $cardContent.parent();
          var parentHeight = $cardParent.height();
          var cardHeight = $cardContent.height();
          var padding = $cardParent.outerHeight() - parentHeight;
          if (maxCardHeidht <= cardHeight + padding) {
            maxCardHeidht = cardHeight + padding;
          }
        });
        $('.card-content', $row).each(function () {
          var $cardContent = $(this);
          var $cardParent = $cardContent.parent();
          var parentHeight = $cardParent.height();
          var cardHeight = $cardContent.height();
          var padding = $cardParent.outerHeight() - parentHeight;
          if (cardHeight > parentHeight) {
            if (breakpoint === 'mobile') {
              $cardContent.closest('.flipper').height(cardHeight + padding);
            }
            else {
              $row.find('.flipper').height(cardHeight + padding);
            }
          }
          else if ((cardHeight + padding) < parentHeight) {
            $row.find('.flipper').height(maxCardHeidht);
          }
        });
      }, 50);
    }
  };

  /**
   * Main menu theming.
   */
  Drupal.behaviors.pmmiMainMenu = {
    searchBlock: function ($context) {
      var $menu = $context.find('.block-tb-megamenu');
      var $menuParent = $menu.parent();
      var handleResize = function () {
        var currentBp = _.invert(drupalSettings.responsive.activeBreakpoints, true)['true'];
        if (currentBp !== 'mobile') {
          var freeWidth = $menuParent.width() - $menu.width();
          if (freeWidth >= 310) {
            $menuParent.addClass('show-search');
          }
          else {
            $menuParent.removeClass('show-search');
          }
        }
        else {
          $menuParent.removeClass('show-search');
        }
      };
      $(window).on('load resize', _.throttle(handleResize, 300, {leading: false}));
    },
    attach: function () {
      var _this = this;
      $('.main-nav').once('main-nav').each(function () {
        var $navContext = $(this);
        var $dropdownToggle = $('a.dropdown-toggle');
        $dropdownToggle.each(function () {
          $(this).parent().find('.mega-nav').prepend($('<li>').addClass('only-mobile').append($(this).clone().toggleClass('dropdown-toggle')));
        });
        $(this).find('a').each(function () {
          var $link = $(this);
          if (!$link.attr('href').length) {
            $link.removeAttr('href').css('cursor', 'default')
              .on('click', function (e) {
                e.preventDefault();
              });
          }
        });
        // Search block.
        _this.searchBlock($navContext);

        // Mobile toggler.
        var applyToggler = function (breakpoint) {
          if (breakpoint === 'mobile') {
            $dropdownToggle.once('click-toggler').each(function () {
              var $dropdownLink = $(this);
              $dropdownLink.on('click.mobile-toggler', function (e) {
                e.preventDefault();
                $dropdownLink.toggleClass('opened').parent().toggleClass('opened')
                  .siblings().removeClass('opened')
                  .find('>a').removeClass('opened');
              });
            });
          }
          else {
            $dropdownToggle.off('click.mobile-toggler').removeOnce('click-toggler');
          }
        };
        $(window).on('breakpointActivated', function (e, breakpoint) {
          applyToggler(breakpoint);
        });

        // Fix menu for touch devices.
        if ('ontouchstart' in window || navigator.maxTouchPoints) {
          var $dropDownLinks = $('li.dropdown', $(this));
          $dropDownLinks.addClass('touch').on('click', function (e) {
            $dropDownLinks.not(this).removeClass('open');
            $(this).toggleClass('open');
            if ($(this).is('.open')) {
              e.preventDefault();
            }
          });
          $(document).on('click touchstart', function (e) {
            if (!$(e.target).is('.touch') && !$(e.target).closest('.touch').length) {
              $dropDownLinks.removeClass('open');
            }
          });
        }
      });
    }
  };

  /**
   * Persistent navigation behavior.
   */
  Drupal.behaviors.pmmiPersistentNav = {
    fixHeader: function ($header, scrollTop) {
      var headerH = $header.outerHeight(true);
      if (scrollTop > headerH) {
        $header
          .addClass('below-fold')
          .parent().css('padding-top', headerH);
      }
      else {
        $header
          .removeClass('below-fold')
          .parent().css('padding-top', 0);
      }
    },
    attach: function (context, settings) {
      var _this = this;
      $('.header-wrapper').once('persistent').each(function () {
        var $header = $(this);
        _this.fixHeader($header, $(window).scrollTop());
        $(window).on('scroll', function (e) {
          _this.fixHeader($header, $(this).scrollTop());
        });
      });
    }
  };

  /**
   * Copy button behavior.
   */
  Drupal.behaviors.pmmiCopyButton = {
    attach: function () {
      $('.copy-button input').once('copy-button').each(function () {
        var $input = $(this);
        var originalText = Drupal.t('Copy to clipboard');
        var copiedText = Drupal.t('Copied!');
        var $copyButton = $('<a>', {
          'class': 'copy-link fa fa-clipboard',
          'title': originalText,
          'data-toggle': 'tooltip',
          'href': '#',
          'click': function (e) {
            e.preventDefault();
            $input.select();
            document.execCommand('copy');
            $(this).attr('title', copiedText);
            $(this).attr('data-original-title', copiedText);
            $(this).tooltip('show');
          },
          'mouseleave': function () {
            $(this).attr('data-original-title', originalText);
            $(this).attr('title', originalText);
          }
        });
        $input.after($copyButton);
      });
    }
  };

  /**
   * Attaches the iFrame resize behaviour for video field.
   */
  Drupal.behaviors.pmmiPrivacyPolicyAgreement = {
    attach: function () {
      if ($('#node-company-form').length != 0) {
        $('#node-company-form').once("pmmiPrivacyPolicyAgreement").each(function(){
          var $form = $('#node-company-form');
          var $privacyPolicyField = $form.find("[name='field_privacy_policy_agreement']");
          if ($privacyPolicyField.length != 0) {
            $privacyPolicyField.each(function () {
              if($(this).attr('value') == '_none') {
                $(this).closest('.form-type-radio').remove();
              }
            });
            $privacyPolicyField.attr({type: 'checkbox', required: 'required'});
            var $fildestWrapper =  $privacyPolicyField.closest('.js-webform-type-radios');
            $fildestWrapper.attr('required', 'required');
            $fildestWrapper.find('.fieldset-legend').addClass('js-form-required form-required');
          }
        });

      }
    }
  };

})(jQuery, window, Drupal);

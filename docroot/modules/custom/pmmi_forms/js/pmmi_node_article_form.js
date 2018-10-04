(function ($, Drupal) {
    'use strict';

    /**
     * Emulate #states on node_article_form
     */
    Drupal.behaviors.pmmiFormsArcticleAlter = {
        attach: function () {

            // Fields that need to be processed.
            var fields = [
                "field_phone[0][value]",
                "field_email[0][value]",
                "field_company[0][value]",
                "field_author_title[0][value]",
                "field_author[0][value]",
                "field_link[0][title]",
                "field_link[0][uri]"
            ];
            var il = fields.length;

            // News Category
            var $elem = $(':input[name="field_news_category[]"]');

            // Process element on ready state.
            checkElemState($elem);

            // Bind change event.
            $elem.once().on('change', function () {
                checkElemState(this);
            });

            // Check if Women's Leadership Network is selected. If so make a few fields required.
            function checkElemState(elem) {
                try {
                    var $checkedElem = $(elem).find('option:checked');
                    var processed = false;
                    if ($checkedElem.length > 0) {
                        for (var i = 0; i < il; i++) {
                            if ($checkedElem[i].value == '191') {
                                changeFieldAttr(false);
                            }
                            else {
                                // Prevent do the same job several times.
                                if (!processed) {
                                    changeFieldAttr(true);
                                    processed = true;
                                }
                            }
                        }
                    }
                } catch (e) { }

            }

            // Helper function to change required attr.
            function changeFieldAttr(required) {
                for (var i = 0; i < il; i++) {
                    var $elem = $(':input[name="' + fields[i] + '"]');
                    $elem.attr("required", required);
                    if (required) {
                        $elem.closest('.js-form-item').find('label').addClass('js-form-required form-required');
                    } else {
                        $elem.closest('.js-form-item').find('label').removeClass('js-form-required form-required');
                    }
                }
            }
        }
    };
})(jQuery, Drupal);
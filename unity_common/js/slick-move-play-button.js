/**
 * @file
 * Attaches behaviors for the Slick module.
 */

(function ($, drupalSettings) {

  /**
   * Move slick autoplay button before the dots list.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.sliderAttach = {
    attach: function (context) {
      $(once('slick-move-play-button', '.slick-autoplay-toggle-button', context))
          .each(function () {
            const $button = $(this);
            const $dots = $button.siblings('.slick-dots').first();

            if ($dots.length) {
              $button.insertBefore($dots);
            }
          });
    }
  };

})(jQuery, drupalSettings);

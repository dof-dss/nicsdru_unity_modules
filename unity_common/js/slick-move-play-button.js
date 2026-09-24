/**
 * @file
 * Attaches behaviors for the Slick module.
 */

(function ($, drupalSettings) {

  /**
   * Move slick autoplay button to before slick dots.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.sliderAttach = {
    attach: function (context) {
      $(once('slick-move-play-button', '.slick-autoplay-toggle-button'))
        .prependTo('.slick-dots');
    }
  };
})(jQuery, drupalSettings);

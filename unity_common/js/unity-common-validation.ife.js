/**
 * @file
 * Attaches behaviors for the Clientside Validation jQuery module.
 */

(function ($, drupalSettings) {

  'use strict';

  // Disable clientside validation for webforms submitted using Ajax.
  // This prevents Computed elements with Ajax from breaking.
  // @see
  // \Drupal\clientside_validation_jquery\Form\ClientsideValidationjQuerySettingsForm
  drupalSettings.clientside_validation_jquery.validate_all_ajax_forms = 0;

  /**
   * Add .cv-validate-before-ajax to all webform submit buttons.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.errorMessageFixAjax = {
    attach: function (context) {
      $(once('error-message-fix-ajax', 'form.search-form--site .form-actions .js-form-submit'))
        .addClass('cv-validate-before-ajax');
    }
  };

  $(once('errormessage-fix', $(document))).on('cv-jquery-validate-options-update', function (event, options) {
    options.errorElement = 'p';
    options.showErrors = function (errorMap, errorList) {
      // Show errors using defaultShowErrors().
      this.defaultShowErrors();

      // Add '.form-item--error-message' class to all errors.
      $(this.currentForm).find('strong.error').addClass('form-item--error-message');

    };
  });

})(jQuery, drupalSettings);

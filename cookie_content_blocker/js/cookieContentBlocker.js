(function ($, Drupal, window, document) {
  'use strict';

  var $window = $(window);
  var $document = $(document);
  var consentGivenEventName = 'cookieContentBlockerConsentGiven';
  var consentChangedEventName = 'cookieContentBlockerChangeConsent';
  var cookieContentBlockerSettings = null;
  var specialEventSelectors = {
    window: $window,
    document: $document
  };

  /**
   * Handles events which trigger a consent change.
   *
   * @param {jQuery.Event} event
   *   The jQuery fired event.
   */
  var consentChangeCallback = function (event) {
    var eventChange = cookieContentBlockerSettings.consentAwareness.change.event;
    var $specialTarget = specialEventSelectors[eventChange.selector];

    event.preventDefault();

    $window.trigger(consentChangedEventName);

    if ($specialTarget === void (0)) {
      $(eventChange.selector).trigger(eventChange.name);
      return;
    }

    $specialTarget.trigger(eventChange.name);
  };

  /**
   * Check if a given cookie matches the given value based on the operator.
   *
   * @param {object} cookieMatch
   *   Object to use for the match containing:
   *   - name
   *   - value
   *   - operator
   *
   * @return {boolean}
   *   Whether or not we have a cookie value match.
   */
  var matchCookieValue = function (cookieMatch) {
    if (typeof cookieMatch.name !== 'string' || typeof cookieMatch.value !== 'string') {
      return false;
    }

    var currentValue = $.cookie(cookieMatch.name);

    // Handle special case when the cookie is not set.
    if (currentValue === null || currentValue === void (0)) {
      return cookieMatch.operator === '!e';
    }

    var matchMap = {
      'e': function () {
        return true;
      },
      '===': function (v) {
        return currentValue === v;
      },
      '>': function (v) {
        return currentValue > v;
      },
      '<': function (v) {
        return currentValue < v;
      },
      'c': function (v) {
        return currentValue.indexOf(v) >= 0;
      },
      '!c': function (v) {
        return currentValue.indexOf(v) === -1;
      }
    };

    return (matchMap[cookieMatch.operator] !== void (0) && matchMap[cookieMatch.operator](cookieMatch.value) || false);
  };

  /**
   * Cookie content blocker behavior for loading blocked content and scripts.
   */
  Drupal.behaviors.cookieContentBlocker = {
    initialized: false,
    consent: null,

    /**
     * Get cookie consent status.
     *
     * @return {boolean|null}
     *   Whether or not cookie consent has been given.
     */
    getConsent: function () {
      var cookieContentBlocker = Drupal.behaviors.cookieContentBlocker;

      if (cookieContentBlocker.consent !== null) {
        return cookieContentBlocker.consent;
      }

      if (matchCookieValue(cookieContentBlockerSettings.consentAwareness.accepted.cookie)) {
        cookieContentBlocker.consent = true;
        return true;
      }

      if (matchCookieValue(cookieContentBlockerSettings.consentAwareness.declined.cookie)) {
        cookieContentBlocker.consent = false;
      }

      return cookieContentBlocker.consent;
    },

    /**
     * Attach event listeners for consent status changes.
     *
     * We listen to our own event on window named:
     * 'cookieContentBlockerChangeConsent'. But also events defined by the user
     * via our admin interface to allow easy integration with other modules.
     *
     * @param {HTMLElement} context
     *   The attached context.
     */
    attachConsentEventListeners: function (context) {
      var cookieContentBlocker = Drupal.behaviors.cookieContentBlocker;
      var eventAccepted = cookieContentBlockerSettings.consentAwareness.accepted.event;
      var $specialTarget = specialEventSelectors[eventAccepted.selector];
      var isSpecialTarget = $specialTarget !== void (0);

      if (!isSpecialTarget) {
        $(eventAccepted.selector).on(eventAccepted.name, function () {
          cookieContentBlocker.handleCookieAccepted(context);
        });
      }

      // Only attach events to document and window once.
      if (cookieContentBlocker.initialized) {
        return;
      }

      $window.on(consentGivenEventName, function () {
        cookieContentBlocker.handleCookieAccepted();
      });

      if (!isSpecialTarget) {
        return;
      }

      $specialTarget.on(eventAccepted.name, function () {
        cookieContentBlocker.handleCookieAccepted();
      });
    },

    /**
     * Attach event triggers for consent status changes.
     *
     * We trigger our own event on window named:
     * 'cookieContentBlockerChangeConsent' when the change is requested via this
     * module's placeholders. But also events defined by the user
     * via our admin interface to allow easy integration with other modules.
     *
     * Note: we currently don't do anything when declining cookie consent.
     *
     * @param {HTMLElement} context
     *   The attached context.
     */
    attachConsentEventTriggers: function (context) {
      $('.js-cookie-content-blocker', context)
        .find('.js-cookie-content-blocker-consent-change-button, .js-cookie-content-blocker-click-consent-change')
        .once('cookie-content-blocker').each(function () {
        $(this).on('click', consentChangeCallback);
      });
    },

    /**
     * Initialize after the page is loaded and update after AJAX requests.
     *
     * @param {HTMLElement} context
     *   The attached context.
     * @param {object} settings
     *   The Drupal JS settings.
     */
    attach: function (context, settings) {
      var cookieContentBlocker = Drupal.behaviors.cookieContentBlocker;

      // @todo update cookieContentBlockerSettings after AJAX.
      if (cookieContentBlockerSettings === null) {
        cookieContentBlockerSettings = settings.cookieContentBlocker;
      }

      // Handle already accepted cookies.
      if (cookieContentBlocker.getConsent()) {
        cookieContentBlocker.handleCookieAccepted(context);
      }

      cookieContentBlocker.attachConsentEventListeners(context);
      cookieContentBlocker.attachConsentEventTriggers(context);
      cookieContentBlocker.initialized = true;
    },

    /**
     * Handle the fact that cookies are accepted by the user.
     *
     * @param {HTMLElement} context
     *   Optionally the attached context, defaults to 'document'.
     */
    handleCookieAccepted: function (context) {
      if (context === void (0)) {
        context = document;
      }

      Drupal.behaviors.cookieContentBlocker.consent = true;
      Drupal.behaviors.cookieContentBlocker.loadBlockedContent(context);
      Drupal.behaviors.cookieContentBlocker.loadBlockedAssets();
    },

    /**
     * Loads the blocked content in place.
     *
     * @param {HTMLElement} context
     *   The attached context.
     */
    loadBlockedContent: function (context) {
      $('.js-cookie-content-blocker-content', context).each(function () {
        var $originalContentWrapperScript = $(this);
        var $blocker = $originalContentWrapperScript.closest('.js-cookie-content-blocker');
        var originalContent = $originalContentWrapperScript.text();
        // Replace the <scriptfake> placeholder tags with real script tags.
        // See: _cookie_content_blocker_replace_scripts_with_fake() and
        // cookie-content-blocker-wrapper.tpl.php.
        originalContent = originalContent.replace(/(<[/]?script)fake/g, '$1');
        originalContent = originalContent.replace('<!--//--><![CDATA[// ><!--', '').replace('//--><!]]>', '');

        var replaceContent = function ($wrapper) {
          var parent = $wrapper.parent().get(0);
          $wrapper.replaceWith(originalContent);
          Drupal.attachBehaviors(parent);
        };

        replaceContent($blocker.length ? $blocker : $originalContentWrapperScript);
      });
    },

    /**
     * Loads the blocked assets.
     */
    loadBlockedAssets: function () {
      if (cookieContentBlockerSettings.blockedAssets === void (0)) {
        return;
      }

      var originalBehaviors = $.extend(true, {}, Drupal.behaviors);
      $('[data-cookie-content-blocker-asset-id]').each(function () {
        var $blockedAsset = $(this);
        var id = $blockedAsset.data('cookie-content-blocker-asset-id');

        if (!(id in cookieContentBlockerSettings.blockedAssets)) {
          return;
        }

        $blockedAsset.replaceWith(cookieContentBlockerSettings.blockedAssets[id]);
      });

      $.each(Drupal.behaviors, function (name, behavior) {
        if (!originalBehaviors[name] && $.isFunction(behavior.attach)) {
          behavior.attach(document, window.drupalSettings);
        }
      });
    }
  };

})(jQuery, Drupal, window, document);

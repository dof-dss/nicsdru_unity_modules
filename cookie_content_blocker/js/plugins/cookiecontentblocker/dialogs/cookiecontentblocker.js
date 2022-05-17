(function (CKEDITOR, Drupal, window) {
  'use strict';

  // Register our settings dialog.
  CKEDITOR.dialog.add('cookieContentBlockerDialog', function (editor) {
    return {
      icons: 'CookieContentBlocker',
      title: Drupal.t('Add Cookie content blocked content'),
      minWidth: 310,
      minHeight: 200,

      contents: [
        {
          id: 'cookieContentBlockerTab',
          label: Drupal.t('Add Cookie content blocked content'),

          elements: [
            {
              id: 'content',
              type: 'textarea',
              label: Drupal.t('Enter the content (HTML) that should be blocked until consent is given here.')
            },
            {
              id: 'blocked_message',
              type: 'textarea',
              label: Drupal.t('Enter the placeholder message text that should be shown instead of the content to inform the user about the blocked content.'),
              default: Drupal.t('You have not yet given permission to place the required cookies. Accept the required cookies to view this content.'),
              setup: function (settings) {
                setDialogElementObjectValue(this, settings);
              }
            },
            {
              id: 'show_placeholder',
              type: 'checkbox',
              label: Drupal.t('Show placeholder'),
              default: true,
              setup: function (settings) {
                setDialogElementObjectValue(this, settings);
              }
            },
            {
              id: 'show_button',
              type: 'checkbox',
              label: Drupal.t('Show button'),
              default: true,
              setup: function (settings) {
                setDialogElementObjectValue(this, settings);
              }
            },
            {
              id: 'enable_click',
              type: 'checkbox',
              label: Drupal.t('Enable changing consent by clicking on the whole blocked content placeholder'),
              default: true,
              setup: function (settings) {
                setDialogElementObjectValue(this, settings);
              }
            },
            {
              id: 'button_text',
              type: 'text',
              label: Drupal.t('Button text'),
              default: Drupal.t('Show content'),
              setup: function (settings) {
                setDialogElementObjectValue(this, settings);
              }
            }
          ]
        }
      ],

      onShow: function () {
        var selection = editor.getSelection();
        var selectedElement = selection.getStartElement();
        var blockerElement = selectedElement.getAscendant('cookiecontentblocker', true);
        if (!blockerElement) {
          this.setValueOf('cookieContentBlockerTab', 'content', selectedElement.getOuterHtml());
          return;
        }

        var dataSettings = window.atob(blockerElement.data('settings'));
        this.setupContent(JSON.parse(dataSettings));
        this.setValueOf('cookieContentBlockerTab', 'content', blockerElement.getHtml());
      },

      onOk: function () {
        var editor = this.getParentEditor();
        var content = this.getValueOf('cookieContentBlockerTab', 'content');

        if (!content.length > 0) {
          return;
        }

        var settings = {};
        var settingFields = ['button_text', 'show_button', 'show_placeholder', 'blocked_message', 'enable_click'];

        for (var i = 0; i < settingFields.length; i++) {
          var identifier = settingFields[i];
          settings[identifier] = this.getValueOf('cookieContentBlockerTab', identifier);
        }

        var insert = false;
        var selectedElement = editor.getSelection().getStartElement();
        var blockerElement = selectedElement.getAscendant('cookiecontentblocker', true);

        if (!blockerElement) {
          insert = true;
          blockerElement = CKEDITOR.dom.element.createFromHtml('<cookiecontentblocker></cookiecontentblocker>');
        }

        var dataSettings = window.btoa(JSON.stringify(settings));
        blockerElement.data('settings', dataSettings);
        blockerElement.addClass('cookie-content-blocker');
        blockerElement.setHtml(content);

        if (insert) {
          editor.insertElement(blockerElement);
        }
      }
    };

    // Set's the values of the dialog fields based on what is already in the
    // editor.
    function setDialogElementObjectValue(uiElementObject, settings) {
      var elementId = uiElementObject.id;
      if (!(elementId in settings)) {
        return;
      }

      uiElementObject.setValue(settings[elementId]);
    }

  });

})(CKEDITOR, Drupal, window);

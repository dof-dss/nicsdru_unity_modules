(function (CKEDITOR, Drupal) {
  'use strict';

  // Let CKEditor know about our own <cookiecontentblocker> element and let it
  // behave like a block level element.
  CKEDITOR.dtd['cookiecontentblocker'] = CKEDITOR.dtd['div'];
  CKEDITOR.dtd['body']['cookiecontentblocker'] = 1;
  CKEDITOR.dtd.$block['cookiecontentblocker'] = 1;

  // Let the magic line appear on every block level element, including our own.
  // We cannot add a single element because ckeditor.js defines a hard coded
  // list of supported elemenets.
  CKEDITOR.config.magicline_everywhere = true;

  // Define our CKEditor plugin.
  CKEDITOR.plugins.add('cookiecontentblocker', {
    icons: 'cookiecontentblocker',

    init: function (editor) {
      editor.addCommand('cookieContentBlocker', new CKEDITOR.dialogCommand('cookieContentBlockerDialog'));
      editor.ui.addButton('CookieContentBlocker', {
        label: Drupal.t('Add Cookie content blocked content'),
        command: 'cookieContentBlocker',
      });

      CKEDITOR.dialog.add('cookieContentBlockerDialog', this.path + 'dialogs/cookiecontentblocker.js');
    }
  });

  // Add a contextual menu item (right click menu) to modify settings.
  CKEDITOR.on('instanceReady', function (event) {
    var editor = event.editor;
    editor.addMenuGroup('cookie_content_blocker');
    editor.addMenuItems({
      cookieContentBlocker: {
        label: Drupal.t('Edit Cookie content blocker settings'),
        command: 'cookieContentBlocker',
        group: 'cookie_content_blocker'
      }
    });

    editor.contextMenu.addListener(function (element) {
      if (element.getAscendant('cookiecontentblocker', true)) {
        return {
          cookieContentBlocker: CKEDITOR.TRISTATE_ON
        };
      }
    });
  });


})(CKEDITOR, Drupal);

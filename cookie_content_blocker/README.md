# COOKIE CONTENT BLOCKER

_Thanks for taking the time to check this readme!_

## INTRODUCTION

This module will help you prevent the loading of specific parts and related
scripts of a page until consent for placing Cookies and related technologies is
given.

The best place to find introductory and background information is currently the
project page: https://www.drupal.org/project/cookie_content_blocker

## REQUIREMENTS

This module has no hard dependencies.

But, there are currently no known out-of-the-box integrations with cookie
consent UI's. So a developer has to make sure that:

* `Drupal.behaviors.cookieContentBlocker.consent` is set to `true` when the
  needed cookies are accepted,
* A `cookieContentBlockerConsentGiven` event is triggered on `window` when
  consent is given,
* Your cookie consent manager is triggered when the
  `cookieContentBlockerChangeConsent` is fired on `window`.

## INSTALLATION

Install this module as any other Drupal module, see the documenation on
[Drupal.org](https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules).

## CONFIGURATION

You will find site wide configuration after installation at
`/admin/config/user-interface/cookie-content-blocker`.
These settings can be overwritten per piece of blocked content by a developer.

### START BLOCKING CONTENT

**Input filtering / CKEditor**

This module defines an input filter in order to be able to block parts of your
content entered through formatted text fields.

The filter detects the usage of our own custom HTML tag/element named
`<cookiecontentblocker>`.

This way you can have a piece of content like:

```
<p>
  <strong>Awesome</awesome> demo content with a marketing pixel.
</p>

<cookiecontentblocker>
  <img src="http://www.mysecret.marketing/pixel.gif" />
</cookiecontentblocker>

<p>
  Some other text.
</p>
```

The marketing pixel will only be loaded after cookie consent has been given.
Other use cases are for example embedded video's.

This functionality works together with other input filters, so you can also use
the Media module to place video's in the blocking tag.

Go to `/admin/config/content/formats` and enable the
`Cookie content blocker filter` for selected formats.

Note: This filter should, in most cases, run as the last filter and any earlier HTML filters should allow
`<cookiecontentblocker>` elements and their `data-settings` attribute, otherwise
the HTML we provide in the filter may be removed. For example the `Convert line breaks into HTML` filter should run
**after** the filter provided by this module, as this filters out non 'block' level elements
defined by that filter.

You can add settings to each `<cookiecontentblocker>` tag via the
`data-settings` attribute. This way you can change what the site visitor sees
when the content is blocked. For example:

```
<cookiecontentblocker data-settings="{'blocked_message': 'This text shows when cookies are not accepted.'}">
  <img src="http://www.externalhosted.img/sensitive.png" alt="Loading this images exposes private info."/>
</cookiecontentblocker>
```

This module provides a CKEditor plugin to easily manage blocked content in
your editor. Go to your text format at `admin/config/content/formats` and
drag the cookie button in your active toolbar. Note: this CKEditor plugin
required that the `Cookie content blocker filter` text filter is enabled as
described above.

All options are given below.

**Render arrays, for developers**

Developers can easily block content and related scripts prepared via Drupal's
render arrays.

The easiest is blocking content of render arrays with a `#type` property, so
called render elements.
These elements are originally from the Form API but can be found anywhere now.
Add a non-empty property called `#cookie_content_blocker` and you are done.

On non render elements (you can recognize this by the lack of a `#type`
property) you will need to additionally add our pre render callback.
For example like this:

```php
'#pre_render' => ['cookie_content_blocker.element.processor:processElement'],
```

You can provide an options array for the `#cookie_content_blocker` properties to
vary behavior. See `cookie_content_blocker_element_info_alter()` and
`Drupal\cookie_content_blocker\ElementProcessor\DefaultProcessor::processElement`. More options will be added along
the way.

**We now support the following options:**

- blocked_message (the message to show visitors in the placeholder)
- show_button (whether or not to show a button to accept/change cookie consent)
- button_text (the text for the button mentioned above)
- enable_click (whether or not the whole placeholder works like the button)
- show_placeholder (whether or not to show the placeholder at all)
- preview (a preview of the blocked content)

## THANKS TO

* [Synetic](https://www.drupal.org/synetic) for providing time to work on this
  module,
* [Daniël Smidt](https://www.drupal.org/u/dmsmidt), for work on the module and
  putting it on DO,
* [Steven Buteneers](https://www.drupal.org/u/steven-buteneers), for a lot of
  initial work,
* [Marc Janson](https://www.drupal.org/u/scorpid), for providing some basic
  styling.

# COOKIE CONTENT BLOCKER - MEDIA

## INTRODUCTION

This submodule provides support for blocking media provided by external sources
(OEmbed) until consent for cookies and related technology has been
given. It integrates with OEmbed provided by Drupal core. It automatically
discovers all providers used and makes it possible to configure the behavior
of the Cookie Content Blocker for each of these providers.

Please read the documentation of Cookie content blocker first.

## REQUIREMENTS

This module requires the following Drupal modules:

- Cookie content blocker (parent module)
- Media

## INSTALLATION

Install this module as any other Drupal module, see the documenation on
[Drupal.org](https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules).

## CONFIGURATION

Navigate to `admin/config/system/cookie_content_blocker/media` to configure
blocking per available provider. It is possible to provide deviating messages
per provider of which media will be blocked once checked.

This module provides a field formatter for fields that expose OEmbed content.
If you want your content to be initially blocked before consent is given you
should use this field formatter.

Go to the display settings of (f.e. `admin/structure/media/manage/embedded_video/display`)
your media type. Select the `Cookie Content Blocker - oEmbed content"` format.
Save and you are ready to go!

Note: This field formatter will adhere to the settings configured at
`admin/config/system/cookie_content_blocker/media`. It wil determine the
provider based on the URL provided and check if it should be blocked.

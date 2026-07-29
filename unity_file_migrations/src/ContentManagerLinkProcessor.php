<?php

namespace Drupal\unity_file_migrations;


/**
 * Class ContentExtractors.
 *
 * @package Drupal\unity_file_migrations
 */
class ContentManagerLinkProcessor {

  /**
   * Extracts contentmanager links and link text from the body field.
   *
   * @param array $content
   *   Drupal content field array.
   *
   */
  public static function contentmanagerLinks(array $content) {
    // Obtain the nid of the publication for debugging.
    $nid = $content[0];
    // Check the body field has some content.
    if (!empty($content[1][0]['value'])) {
      // Obtain the D7 body value.
      $value = $content[1][0]['value'];
      // Regex to match a string <a href="contentmanager://record/DB=AI&Type=6&[item1]&URI=198287">Mail merge - Staf Guidance</a>.
      $link_regex = '/<a href="(contentmanager:\/\/.*?)">(.*?)<\/a>/';
      $matches = [];
      preg_match_all($link_regex, $value, $matches, PREG_SET_ORDER);

      $contentmanager_links = [];

      foreach ($matches as $contentmanager_link) {
        $contentmanager_links[] = [
          'uri' => $contentmanager_link[1],
          'title' => $contentmanager_link[2],
          'options' => [
            'attributes' => [],
          ],
        ];
      }

      return $contentmanager_links;
    }
  }
}

<?php

namespace Drupal\unity_file_migrations;

use Drupal\media\Entity\Media;

/**
 * Class ContentExtractors.
 *
 * @package Drupal\unity_file_migrations
 */
class PublicationLinkProcessor {

  /**
   * Extracts link elements from html after the string 'More useful links'.
   *
   * @param array $content
   *   Drupal content field array.
   *
   * @return \Drupal\media\MediaInterface[]
   *   An array of selected media items.
   */
  public static function publicationLinks(array $content) {
    // Obtain the nid of the publication for debugging.
    $nid = $content[0];
    // Check the body field has some content.
    if (!empty($content[1][0]['value'])) {
      // Obtain the D7 body value.
      $value = $content[1][0]['value'];
      // Regex to match a string <a href="https://www.niauditoffice.gov.uk/sites/niao/files/media-files/conflicts_of_interest_good_practice_guide.pdf">Conflicts Of Interest - A Good Practice Guide <span class="meta"> (PDF&nbsp;437 KB)</span></a>.
      $link_regex = '/<a href="(\S+?files\/\S+)".*?>\s*[^<]*(\([^<]*\))?<\/a>/';
      $matches = [];
      preg_match_all($link_regex, $value, $matches, PREG_SET_ORDER);

      $file_target_ids = [];
      foreach ($matches as $embedded_file) {
        // Obtain the basename of the embedded file.
        $embedded_file_name = basename($embedded_file[1]);

        if (!empty($embedded_file_name)) {
          // There may be a better way to do this. The filename taken from the body text relates to the uri of the file
          // some uri's are stored under public:// and some public://media-files. I couldn't find a way to add both or
          // do a strpos() in loadByProperties() but this works.
          $files = \Drupal::entityTypeManager()
            ->getStorage('file')
            ->loadByProperties(['filename' => urldecode($embedded_file_name)]);

          if (empty($files)) {
            $files = \Drupal::entityTypeManager()
              ->getStorage('file')
              ->loadByProperties(['uri' => 'public://media-files/' . urldecode($embedded_file_name)]);
          }
          if (empty($files)) {
            $files = \Drupal::entityTypeManager()
              ->getStorage('file')
              ->loadByProperties(['uri' => 'public://' . urldecode($embedded_file_name)]);
          }

          $file = reset($files) ?: NULL;
          // Ensure we have a matched file to work with and store the file ID in an array.
          if (isset($file)) {
            $file_target_id = $file->id();
            array_push($file_target_ids, $file_target_id);
          }
        }
      }
      // Return the file ID's and load them into the media field.
      return Media::loadMultiple($file_target_ids);
    }
    return [];
  }
  
}

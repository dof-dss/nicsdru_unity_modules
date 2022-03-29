<?php

namespace Drupal\unity_file_migrations\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate\MigrateSkipRowException;
use GuzzleHttp\Exception\RequestException;

/**
 * Provides a 'VideoUrl' migrate process plugin.
 *
 * @MigrateProcessPlugin(
 *  id = "video_url"
 * )
 */
class VideoUrl extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // This is a text field in D8 and we don't need the 'oembed://' prefix.
    $url = preg_replace('|^oembed:\/\/|', '', $value);
    // Remove HTML special chars to give a user readable string.
    $url = urldecode($url);
    // See if thumbnail is available.
    $thumbnail_url = "https://www.youtube.com/oembed?url=" . $url;
    try {
      $response = \Drupal::httpClient()->get($thumbnail_url);
    }
    catch (RequestException $e) {
      // No thumbnail available, so do not migrate this row.
      $msg = 'fid ' . $row->getSourceProperty('fid') . ' - ' . $e->getMessage();
      throw new MigrateSkipRowException($msg);
    }
    return $url;
  }

}

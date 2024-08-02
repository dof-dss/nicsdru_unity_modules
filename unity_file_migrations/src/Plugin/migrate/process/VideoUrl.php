<?php

namespace Drupal\unity_file_migrations\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
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

    return $url;
  }

}

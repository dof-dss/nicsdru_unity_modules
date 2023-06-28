<?php

namespace Drupal\unity_file_migrations\Plugin\migrate\process;

use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Provides an 'SiteFilesFilter' migrate process plugin.
 *
 * @MigrateProcessPlugin(
 *  id = "site_files_filter"
 * )
 */
class SiteFilesFilter extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $matches = [];
    // Look for all anchors in the body field.
    if (preg_match_all('|href\=[\'"]+([^ >"\']*)[\'"]+[^>]*>|', $value['value'], $matches)) {
      if (count($matches) > 1) {
        foreach ($matches[1] as $original_link) {
          // See if this url matches the pattern that we are looking for,
          // if so, replace it.
          if (!isset($this->configuration['from_ref'])) {
            throw new MigrateException('"from_ref" must be configured.');
          }
          if (!isset($this->configuration['to_ref'])) {
            throw new MigrateException('"to_ref" must be configured.');
          }
          $value['value'] = str_replace($this->configuration['from_ref'], $this->configuration['to_ref'], $value['value']);
        }
      }
    }
    return $value;
  }

}

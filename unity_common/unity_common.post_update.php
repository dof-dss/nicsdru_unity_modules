<?php

/**
 * @file
 * Post-update hooks for Unity Common.
 */

/**
 * Reconciles extension state before importing Drupal 11 configuration.
 */
function unity_common_post_update_retire_drupal_10_extensions(array &$sandbox): string {
  $extension_config = \Drupal::configFactory()->getEditable('core.extension');

  $available_modules = \Drupal::service('extension.list.module')->getList();
  foreach (['block_content_permissions', 'file_delete_ui'] as $module) {
    if ($extension_config->get("module.$module") !== NULL) {
      if (isset($available_modules[$module])) {
        \Drupal::service('module_installer')->uninstall([$module]);
        $extension_config = \Drupal::configFactory()->getEditable('core.extension');
      }
      else {
        // Missing code cannot run uninstall hooks; remove stale registration.
        $extension_config->clear("module.$module")->save(TRUE);
      }
    }
    \Drupal::keyValue('system.schema')->delete($module);
  }

  // Config import cannot uninstall a theme whose code is already absent.
  if ($extension_config->get('theme.seven') !== NULL && !isset(\Drupal::service('extension.list.theme')->getList()['seven'])) {
    $theme_config = \Drupal::config('system.theme');
    if (in_array('seven', [$theme_config->get('default'), $theme_config->get('admin')], TRUE)) {
      throw new \RuntimeException('Switch the default/admin theme from Seven before upgrading.');
    }
    $extension_config->clear('theme.seven')->save(TRUE);
  }

  return (string) t('Reconciled retired Drupal 10 extension state.');
}

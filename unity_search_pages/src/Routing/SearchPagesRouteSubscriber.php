<?php

namespace Drupal\unity_search_pages\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class SearchPagesRouteSubscriber.
 *
 * Listens to the dynamic route events.
 */
class SearchPagesRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach ($collection->getIterator() as $route_name => $route_info) {
      $pattern = '/view.\w*search.\w*search_page/';
      if ((preg_match($pattern, $route_name)) || ($route_name == 'search.view')) {
        if ($route = $collection->get($route_name)) {
          $route->setDefault('route', $route_name);
          $route->setDefault('_title_callback', '\Drupal\unity_search_pages\Controller\SearchPagesController::getTitle');
        }
      }
    }
  }

}

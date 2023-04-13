<?php

namespace Drupal\unity_search_pages\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Controller for setting browser tab title.
 */
class SearchPagesController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $routeMatch;

  /**
   * The current request stack.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Routing\CurrentRouteMatch $route_match
   *   The current route match.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   The current request.
   */
  public function __construct(CurrentRouteMatch $route_match, RequestStack $request) {
    $this->routeMatch = $route_match;
    $this->request = $request->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('request_stack')
    );
  }

  /**
   * Controller callback for the page title.
   *
   * Use this to examine route parameters/any other conditions
   * and vary the string that is returned.
   *
   * @return string
   *   The page title.
   */
  public function getTitle($route = NULL) {
    if ($route === NULL) {
      $route = $this->routeMatch->getRouteName();
    }
    $facet = $this->request->get('facets_query');

    $title = $this->getTitleFromRoute($route);
    $search = $this->request->query->all();

    if ($route === 'search.view') {
      if (!empty($search)) {
        return t('@title results', ['@title' => $title]);
      }
      else {
        return $title;
      }
    }
    // Applying the term being viewed to Judiciary Sentencing guidelines page title.
    elseif ($route === 'view.judicial_decisions_search.sentence_guide_search_page') {
      $current_path = \Drupal::service('path.current')->getPath();
      if (preg_match_all('/.*\/type\/\D+(\d+)/', $current_path, $matches)) {
        $tid = $matches[1][0];
        if (is_numeric($tid)) {
          $decision_type = Term::load($tid);
          $term_name = $decision_type->getName();
          $title = 'Sentencing guidelines - ' . $term_name;
        }
      }
      else {
        $title = 'Sentencing guidelines';
      }
      return $title;
    }
    else {
      if (!empty($facet) || !empty($search)) {
        return t('@title - search results', ['@title' => $title]);
      }
      else {
        return $title;
      }
    }
  }

  /**
   * Deduce page title from route.
   *
   * @return string
   *   The page title.
   */
  public function getTitleFromRoute($route = NULL) {
    // For example, route view.publications_search.publication_search_page
    // gives the title 'Publications'.
    $title = '';
    $route_parts = explode('.', $route);
    if ((count($route_parts) > 2) && !empty($route_parts[2])) {
      $title = $route_parts[2];
      $title = str_replace(['search_page', '_'], '', $title);
      if (strlen($title) == 0) {
        // This must be the site search page.
        $title = 'Search';
      }
      else {
        // Capitalise the first letter and pluralise.
        $title = ucfirst($title);
        if ((substr($title, -1) != 's') && ($title !== 'Evidence')) {
          $title .= 's';
        }
      }
      if ($title == 'Questions') {
        $title = 'Questions to the Chief Constable';
      }
      if ($title == 'Articles') {
        $title = 'Featured articles';
      }
      if ($title == 'Judicialdecisions') {
        $title = 'Judicial decisions and directions';
      }
    }
    return $title;
  }

}

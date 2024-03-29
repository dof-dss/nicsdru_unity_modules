<?php

namespace Drupal\unity_breadcrumbs;

/**
 * @file
 * Generates the breadcrumb trail for search page(s)
 *
 * In the format:
 * > Home
 * > current-page-title
 *
 * > <front>
 * > /current-page-title
 */
use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * {@inheritdoc}
 */
class ViewPageBreadcrumb implements BreadcrumbBuilderInterface {

  /**
   * RequestStack service object.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $request;

  /**
   * The title resolver.
   *
   * @var \Drupal\Core\Controller\TitleResolverInterface
   */
  protected $titleResolver;

  /**
   * Class constructor.
   */
  public function __construct(RequestStack $request, TitleResolverInterface $title_resolver) {
    $this->request = $request;
    $this->titleResolver = $title_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('title_resolver')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    $match = FALSE;
    $route_name = $route_match->getRouteName();
    $view_names = [
      'view.news_search.news_search_page',
      'view.publications_search.publication_search_page',
      'view.evidence_search.evidence_search_page',
      'view.consultations_search.consultations_search_page',
      'view.search.search_page',
      'view.documents_search.documents_search_page',
      'view.decision_search.decision_search_page',
      'view.featured_search.articles_search_page',
      'view.events_search.events_search_page',
      'view.judicial_decisions_search.sentence_guide_search_page',
      'view.managers_search.members_search_page',
    ];

    foreach ($view_names as $view_name) {
      if ($route_name == $view_name) {
        $match = TRUE;
      }
    }
    return $match;

  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();
    $title_resolver = $this->titleResolver->getTitle($this->request->getCurrentRequest(), $route_match->getRouteObject());
    $links[] = Link::createFromRoute(t('Home'), '<front>');
    $links[] = Link::createFromRoute($title_resolver, '<none>');
    $breadcrumb->setLinks($links);
    $breadcrumb->addCacheContexts(['url.path']);

    return $breadcrumb;
  }

}

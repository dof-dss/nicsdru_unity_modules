<?php

namespace Drupal\unity_breadcrumbs;

/**
 * @file
 * Generates the breadcrumb trail for content including:
 * - News
 *
 * In the format:
 * > Home
 * > Events
 * > current-page-title
 *
 * > <front>
 * > /events
 * > /current-page-title
 */
use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * {@inheritdoc}
 */
class EventsBreadcrumb implements BreadcrumbBuilderInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Node object, or null if on a non-node page.
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $node;

  /**
   * The title resolver.
   *
   * @var \Drupal\Core\Controller\TitleResolverInterface
   */
  protected $titleResolver;

  /**
   * RequestStack service object.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $request;

  /**
   * Class constructor.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, TitleResolverInterface $title_resolver, RequestStack $request) {
    $this->entityTypeManager = $entity_type_manager;
    $this->titleResolver = $title_resolver;
    $this->request = $request;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('title_resolver'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    $match = FALSE;
    $route_name = $route_match->getRouteName();
    if ($route_name == 'entity.node.canonical') {
      $this->node = $route_match->getParameter('node');
    }

    if ($route_name == 'entity.node.preview') {
      $this->node = $route_match->getParameter('node_preview');
    }

    if (!empty($this->node)) {
      if ($this->node instanceof NodeInterface == FALSE) {
        $this->node = $this->entityTypeManager->getStorage('node');
      }

      if ($this->node->bundle() == 'events') {
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
    $links[] = Link::fromTextandUrl(t('Events'), Url::fromRoute('view.events_search.events_search_page'));
    $links[] = Link::createFromRoute($title_resolver, '<none>');
    $breadcrumb->setLinks($links);
    $breadcrumb->addCacheContexts(['url.path']);
    return $breadcrumb;
  }

}

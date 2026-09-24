<?php

namespace Drupal\unity_breadcrumbs;

/**
 * @file
 * Generates the breadcrumb trail for content including:
 * - Evidence
 *
 * In the format:
 * > Home
 * > Evidence
 * > current-page-title
 *
 * > <front>
 * > /evidence
 * > /current-page-title
 */
use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * {@inheritdoc}
 */
class EvidenceBreadcrumb implements BreadcrumbBuilderInterface {

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
  public function __construct(TitleResolverInterface $title_resolver, RequestStack $request) {
    $this->titleResolver = $title_resolver;
    $this->request = $request;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
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

    if ($this->node instanceof NodeInterface) {
      if ($this->node->bundle() == 'evidence') {
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
    $links[] = Link::fromTextandUrl(t('Evidence'), Url::fromRoute('view.evidence_search.evidence_search_page'));
    $links[] = Link::createFromRoute($title_resolver, '<none>');
    $breadcrumb->setLinks($links);
    $breadcrumb->addCacheContexts([
      'url.path',
      'languages:language_url',
      'languages:language_interface',
      'theme',
      'user.permissions',
      'url.path.parent',
      'url.path.is_front',
      'route'
    ]);
    return $breadcrumb;
  }

}

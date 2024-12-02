<?php

namespace Drupal\asu_item_extras\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\MetadataBubblingUrlGenerator;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a 'Machine-readable links' Block.
 *
 * @Block(
 *   id = "machine_readable_links",
 *   admin_label = @Translation("Machine-readable links"),
 *   category = @Translation("Views"),
 * )
 */
class MachineReadableLinks extends BlockBase implements ContainerFactoryPluginInterface {


  /**
   * The routeMatch definition.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The requestStack definition.
   *
   * @var requestStack
   */
  protected $requestStack;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * URL Generator.
   *
   * @var \Drupal\Core\Render\MetadataBubblingUrlGenerator
   */
  protected $urlGenerator;

  /**
   * Constructor for About this Collection Block.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Render\MetadataBubblingUrlGenerator $url_generator
   *   For getting the site url.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match, RequestStack $request_stack, EntityTypeManagerInterface $entity_type_manager, MetadataBubblingUrlGenerator $url_generator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $route_match;
    $this->requestStack = $request_stack;
    $this->entityTypeManager = $entity_type_manager;
    $this->urlGenerator = $url_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('request_stack'),
      $container->get('entity_type.manager'),
      $container->get('url_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = $this->routeMatch->getParameter('node');
    $node = is_string($node) ? $this->entityTypeManager->getStorage('node')->load($node) : $node;
    if ($node) {
      $nid = $node->id();
    }
    else {
      $nid = 0;
    }
    if (!isset($node)) {
      return [];
    }
    // This site and domain bit was ripped from the twig template.
    // There has *got* to be a better way of doing this.
    $site_url = $this->urlGenerator->generateFromRoute('<front>', [], ['absolute' => TRUE]);
    $url_parts = explode('/', $site_url, -1);
    $domain = $url_parts[2];
    return [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#items' => [
        Link::fromTextAndUrl(
              $this->t('OAI Dublin Core'),
              Url::fromUri("{$site_url}oai/request?verb=GetRecord&metadataPrefix=oai_dc&identifier=oai:{$domain}:node-{$nid}")
        ),
        Link::fromTextAndUrl(
              $this->t('MODS XML'),
              Url::fromUri("{$site_url}items/{$nid}?_format=mods")
        ),
      ],
    ];
  }

}

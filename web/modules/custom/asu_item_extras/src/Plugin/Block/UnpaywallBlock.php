<?php

namespace Drupal\asu_item_extras\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Url;
use Drupal\Core\Link;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Drupal\Core\Entity\EntityTypeManagerInterface;



/**
 * Provides an Unpaywall Block.
 *
 * @Block(
 *   id = "unpaywall_block",
 *   admin_label = @Translation("Unpaywall"),
 *   category = @Translation("Custom"),
 * )
 */
class UnpaywallBlock extends BlockBase implements ContainerFactoryPluginInterface {
  /**
   * An http client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;
  /**
   * Drupal renderer.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;
  /**
   * The entityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;



  /**
   * Constructs a StringFormatter instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   The renderer class.
   * @param \GuzzleHttp\Client $httpClient
   *   Guzzle client.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entityTypeManager definition.
   */
  public function __construct(array $configuration,
    $plugin_id,
    $plugin_definition,
    Renderer $renderer,
    Client $httpClient,
    EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->renderer = $renderer;
    $this->httpClient = $httpClient;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('renderer'),
      $container->get('http_client'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $return_val = '';
    $block_config = BlockBase::getConfiguration();
    if (is_array($block_config) && array_key_exists('node', $block_config)) {
      $node = $block_config['node'];

      $doi_val = "";
      $typed_idents = $node->field_typed_identifier;
      foreach ($typed_idents as $typed_ident) {
        if (!$doi_val) {
          $typed_target_id = $typed_ident->get("target_id")->getCastedValue();
          $paragraph = $this->entityTypeManager->getStorage('paragraph')->load($typed_target_id);
          $typed_ident_target_id = $paragraph->field_identifier_type->target_id;
          if ($typed_ident_target_id) {
            $typed_ident_type = $this->entityTypeManager->getStorage('taxonomy_term')->load($typed_ident_target_id)->get('field_identifier_predicate')->value;
            if ($typed_ident_type == 'doi') {
              $doi_val = $paragraph->get('field_identifier_value')->value;
            }
          }
        }
      }
      if ($doi_val) {
        $unpaywall_url = $this->callUnpayApi($doi_val);
        if ($unpaywall_url) {
          $return_val = \Drupal::service('renderer')->render(Link::fromTextAndUrl($this->t('Open access version <i class="fas fa-external-link-alt"></i>'), Url::fromUri($unpaywall_url))->toRenderable());
        }
      }

    }
    return [
      '#markup' => $return_val
    ];
  }

  /**
   * Gets OA article link if one exists.
   * 
   * @param string $doi
   *  The doi.
   * 
   * @return string
   *  The url.
   */
  function callUnpayApi($doi) {
    $query = "https://api.unpaywall.org/v2/" . $doi . "?email=digitalrepository@asu.edu";

    $response = $this->httpClient->get($query);
    $response_body = $response->getBody();
    $status_code = $response->getStatusCode();
    if ($status_code == 200) {
      $resource = json_decode($response_body, TRUE);
      return $resource['best_oa_location']['url'];
    }
    else {
      return null;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $block_config = BlockBase::getConfiguration();
    if (is_array($block_config) && array_key_exists('node', $block_config)) {
      $nid = $block_config['node'];
    }
    if (isset($nid)) {
      if (!is_string($nid)) {
        $nid = $nid->id();
      }
      return Cache::mergeTags(parent::getCacheTags(), ['node:' . $nid]);
    }
    else {
      // Return default tags instead.
      return parent::getCacheTags();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['route']);
  }

}

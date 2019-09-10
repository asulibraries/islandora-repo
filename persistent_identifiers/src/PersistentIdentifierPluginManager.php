<?php
namespace Drupal\persistent_identifiers;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages persistent identifier plugins
 * @see plugin_api
 */
class PersistentIdentifierPluginManager extends DefaultPluginManager {
    /**
   * Constructs a PersistentIdentifierPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/PersistentIdentifier', $namespaces, $module_handler, 'Drupal\persistent_identifiers\PersistentIdentifierPluginInterface', 'Drupal\persistent_identifiers\Annotation\PersistentIdentifierPlugin');
    $this->setCacheBackend($cache_backend, 'persistent_identifiers_plugins');
    $this->alterInfo('persistent_identifiers_plugin_info');
  }


}

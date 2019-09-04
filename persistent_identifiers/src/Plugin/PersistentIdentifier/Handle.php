<?php
namespace \Drupal\persistent_identifiers\Plugin\PersistentIdentifier\Handle;
use Drupal\persistent_identifiers\PersistentIdentifierPluginBase;
use Drupal\persistent_identifiers\PersistentIdentifierPluginInterface;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\StorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * @PersistentIdentifierPlugin(
 *  id="pi_handle",
 *  label="Handle"
 * )
 */
class Handle extends PersistentIdentifierPluginBase implements PersistentIdentifierPluginInterface {

  /**
   * @param string node
   * @return string url
   */
  public function get_or_create_pi(string $node){

  }

  /**
   * @param string node
   * @return string url
   */
  public function tombstone_pi(string $node){

  }

}

<?php

namespace Drupal\persistent_identifiers;
use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for persistent identifier plugins.
 */
interface PersistentIdentifierPluginInterface extends PluginInspectionInterface{
    /**
     * @param string node
     * @return string url
     */
    public function get_or_create_pi(string $node);

    /**
     * @param string node
     * @return string url
     */
    public function tombstone_pi(string $node);

}

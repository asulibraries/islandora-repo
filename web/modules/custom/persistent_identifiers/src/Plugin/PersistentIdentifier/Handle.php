<?php

namespace Drupal\persistent_identifiers\Plugin\PersistentIdentifier;

use Drupal\persistent_identifiers\PersistentIdentifierPluginBase;
use Drupal\persistent_identifiers\PersistentIdentifierPluginInterface;
use Drupal\Core\Entity\EntityInterface;

// use Drupal\Core\Config\ConfigManagerInterface;
// use Drupal\Core\Config\StorageInterface;
// use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A plugin for handle.net.
 *
 * @PersistentIdentifierPlugin(
 *  id="pi_handle",
 *  label="Handle"
 * )
 */
class Handle extends PersistentIdentifierPluginBase implements PersistentIdentifierPluginInterface {

  /**
   * Get or create the identifier.
   *
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   The entity.
   *
   * @return string
   *   The url.
   */
  public function getOrCreatePi(EntityInterface $entity = NULL) {
    \Drupal::logger('persistent identifiers')->info('in the getOrCreatePI method');
    // Actually hit the REST API for handle.
    // get the URL for the object as $url
    // TODO $handle_prefix, $handle_type_qualifier, $admin_handle, $endpoint_url, $handle_admin_index  from config.
    $handle_prefix = '2286.9';
    // Prod just uses '2286'.
    $handle_type_qualifier = 'R.N';
    // Currently using I,C,A for item, collection, attachment.
    $handle = $handle_prefix . '/' . $handle_type_qualifier . '.' . $entity->id();
    \Drupal::logger('persistent identifiers')->info($handle);
    $url = $entity->toUrl()->toString();
    \Drupal::logger('persistent identifiers')->info($url);
    $admin_handle = "2286/ASU_ADMIN";
    $handle_admin_index = 300;
    // TODO HTTPS??
    $endpoint_url = 'http://handle-test.lib.asu.edu:8000/api/handles/';
    $handle_json = [
      [
        'index' => 1,
        'type' => "URL",
        'data' => [
          'format' => "string",
          'value' => $url,
        ],
      ],
      [
        'index' => 100,
        'type' => 'HS_ADMIN',
        'data' => [
          'format' => 'admin',
          'value' => [
            'handle' => $admin_handle,
            'index' => $handle_admin_index,
            'permissions' => "111111111111",
          ],
        ],
      ],
    ];

    $client = \Drupal::httpClient();
    // TODO media type null?
    // TODO add auth.
    // try {
    //   $request = $client->request('PUT', $endpoint_url . $handle, $handle_json);
    //   $request->addHeader('Content-Type', 'application/json');
    //   $request->addHeader('Accept', 'application/json');
    //   $response = json_decode($request->getBody());
    //   \Drupal::logger('persistent identifiers')->info(print_r($response, TRUE));

    // }
    // catch (ClientException $e) {
    //   \Drupal::logger('persistent identifiers')->error(print_r($e, TRUE));
    //   return FALSE;
    // }

    $entity->set('field_identifier', 'thisisthehandle');
    $entity->setNewRevision(FALSE);
    $entity->save();
    return "";
  }

  /**
   * Point the identifier to a tombstone page.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return string
   *   The url.
   */
  public function tombstonePi(EntityInterface $entity) {
    return "";
  }

}

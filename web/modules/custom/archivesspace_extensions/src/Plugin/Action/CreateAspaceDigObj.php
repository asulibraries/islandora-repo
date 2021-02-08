<?php

namespace Drupal\archivesspace_extensions\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Drupal\archivesspace\ArchivesSpaceSession;

/**
 * Create a handle for an object.
 *
 * @Action(
 *   id = "create_aspace_dig_obj",
 *   label = @Translation("Create/Update an Archivesspace Digital Object"),
 *   type = "node"
 * )
 */
class CreateAspaceDigObj extends ActionBase implements ContainerFactoryPluginInterface
{

    /**
     * Logger.
     *
     * @var Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * ArchivesSpaceSession that will allow us to issue API requests.
     *
     * @var ArchivesSpaceSession
     */
    protected $archivesspaceSession;

    /**
     * Constructor.
     *
     * @param array $configuration
     *   A configuration array containing information about the plugin instance.
     * @param string $plugin_id
     *   The plugin ID for the plugin instance.
     * @param mixed $plugin_definition
     *   The plugin implementation definition.
     * @param Psr\Log\LoggerInterface $logger
     *   Logger.
     */
    public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition,
        LoggerInterface $logger
    ) {
        parent::__construct($configuration, $plugin_id, $plugin_definition);
        $this->logger = $logger;
        $this->archivesspaceSession = new ArchivesSpaceSession();
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
    {
        return new static(
            $configuration,
            $plugin_id,
            $plugin_definition,
            $container->get('logger.channel.islandora')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function execute($entity = NULL)
    {
        if (!$entity) {
            return;
        }
        $entity_type = $entity->getEntityTypeId();

        if (!$entity_type == 'node') {
            return;
        }

        $this->logger->info("In the Archivesspace DO Action!");

        $archival_obj = $entity->get('field_source')->referencedEntities();
        if ($archival_obj) {
            $archival_obj = $archival_obj[0];
            $archival_obj_ref_id = $archival_obj->get('field_as_ref_id')->value;
            \Drupal::logger('aspace_digital_obj_action')->info("ref id is " . $archival_obj_ref_id);
            $params = [
                "ref_id" => strval($archival_obj_ref_id),
            ];
            $ao_results = $this->archivesspaceSession->request('GET','/repositories/2/find_by_id/archival_objects?ref_id=' . strval($archival_obj_ref_id));//, $params);
            \Drupal::logger('aspace_digital_obj_action')->info(print_r($ao_results, TRUE));

        }

    }

    /**
     * {@inheritdoc}
     */
    public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE)
    {
        $result = AccessResult::allowed();
        return $return_as_object ? $result : $result->isAllowed();
    }

}

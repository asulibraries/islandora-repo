<?php

namespace Drupal\asu_search\Plugin\search_api\processor;

use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\LoggerTrait;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api\SearchApiException;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds content access checks for nodes and comments.
 *
 * @SearchApiProcessor(
 *   id = "unpublished_content",
 *   label = @Translation("Unpublished content"),
 *   description = @Translation("Adds unpublished content checks"),
 *   stages = {
 *     "preprocess_query" = -20,
 *   },
 * )
 */
class UnpublishedContent extends ProcessorPluginBase {

  use LoggerTrait;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection|null
   */
  protected $database;

  /**
   * The current_user service used by this plugin.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface|null
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $processor */
    $processor = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $processor->setLogger($container->get('logger.channel.search_api'));
    $processor->setDatabase($container->get('database'));
    $processor->setCurrentUser($container->get('current_user'));

    return $processor;
  }

  /**
   * Retrieves the database connection.
   *
   * @return \Drupal\Core\Database\Connection
   *   The database connection.
   */
  public function getDatabase() {
    return $this->database ?: \Drupal::database();
  }

  /**
   * Sets the database connection.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The new database connection.
   *
   * @return $this
   */
  public function setDatabase(Connection $database) {
    $this->database = $database;
    return $this;
  }

  /**
   * Retrieves the current user.
   *
   * @return \Drupal\Core\Session\AccountProxyInterface
   *   The current user.
   */
  public function getCurrentUser() {
    return $this->currentUser ?: \Drupal::currentUser();
  }

  /**
   * Sets the current user.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   *
   * @return $this
   */
  public function setCurrentUser(AccountProxyInterface $current_user) {
    $this->currentUser = $current_user;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function supportsIndex(IndexInterface $index) {
    foreach ($index->getDatasources() as $datasource) {
      if (in_array($datasource->getEntityTypeId(), ['node', 'comment'])) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessSearchQuery(QueryInterface $query) {
    // If (!$query->getOption('search_api_bypass_access')) {.
    $account = $query->getOption('search_api_access_account', $this->getCurrentUser());
    if (is_numeric($account)) {
      $account = User::load($account);
    }
    if ($account instanceof AccountInterface) {
      try {
        $this->addNodeAccess($query, $account);
      }
      catch (SearchApiException $e) {
        $this->logException($e);
      }
    }
    else {
      $account = $query->getOption('search_api_access_account', $this->getCurrentUser());
      if ($account instanceof AccountInterface) {
        $account = $account->id();
      }
      if (!is_scalar($account)) {
        $account = var_export($account, TRUE);
      }
      $this->getLogger()->warning('An illegal user UID was given for node access: @uid.', ['@uid' => $account]);
    }
    // }
  }

  /**
   * Adds a node access filter to a search query, if applicable.
   *
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   The query to which a node access filter should be added, if applicable.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user for whom the search is executed.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if not all necessary fields are indexed on the index.
   */
  protected function addNodeAccess(QueryInterface $query, AccountInterface $account) {
    // Don't do anything if the user can access all content.
    if ($account->hasPermission('bypass node access')) {
      return;
    }

    if ($account->isAuthenticated() && (in_array("administrator", $account->getRoles()) || in_array("metadata_manager", $account->getRoles()))) {
      // Basically bypass all the permissions if they have one of these powerful roles.
      return;
    }

    $pub_access_conditions = $query->createConditionGroup();
    $status_field = $this->findField('entity:node', 'status', 'boolean');
    $sf = $status_field->getFieldIdentifier();
    if ($status_field) {
      $pub_access_conditions->addCondition($status_field->getFieldIdentifier(), TRUE);
    }
    $pub_access_conditions->addCondition("parent_published", TRUE);

    $group_conditions = $query->createConditionGroup('OR');
    $groups = [];
    $grp_membership_service = \Drupal::service('group.membership_loader');
    $grps = $grp_membership_service->loadByUser($account);
    foreach ($grps as $grp) {
      $groups[] = $grp->getGroup()->id();
      $grp_lbl = $grp->getGroup()->get('label')->getString();
      $re = '/Collection ([0-9]*) Group/m';
      preg_match_all($re, $grp_lbl, $matches, PREG_SET_ORDER, 0);
      if (count($matches) > 0) {
        $col_id = $matches[0][1];
        $group_conditions->addCondition('field_ancestors', $col_id);
      }
    }
    if (count($groups) > 0) {
      $access_conditions = $query->createConditionGroup('OR');
      $access_conditions->addConditionGroup($pub_access_conditions);
      $access_conditions->addConditionGroup($group_conditions);
    }
    else {
      $access_conditions = $query->createConditionGroup();
      $access_conditions->addConditionGroup($pub_access_conditions);
    }
    // $this_final_query = $access_conditions;
    $query->addConditionGroup($access_conditions);
  }

}

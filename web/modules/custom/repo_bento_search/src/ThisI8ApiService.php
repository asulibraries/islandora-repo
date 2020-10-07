<?php

namespace Drupal\repo_bento_search;

use Drupal\Core\Config\ConfigFactory;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use Psr\Log\LoggerInterface;

/**
 * Class ThisI8ApiService.
 */
class ThisI8ApiService implements BentoApiInterface {

  /**
   * GuzzleHttp\ClientInterface definition.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;
  /**
   * Configuration Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;
  /**
   * Logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a new ThisI8ApiService object.
   */
  public function __construct(ClientInterface $http_client, ConfigFactory $configFactory, LoggerInterface $logger) {
    $this->httpClient = $http_client;
    $this->configFactory = $configFactory;
    $this->logger = $logger;
  }

  /**
   * Gets search results from the API.
   *
   * @param string $term
   *   The query string.
   * @param int $limit
   *   The number of results to limit to.
   */
  public function getSearchResults(string $term, int $limit = 10) {
    try {
      $config = $this->configFactory->get('repo_bento_search.bentosettings');
      $base_url = $config->get('this_i8_api_url');
      if (!trim($base_url)) {
        $this->logger->warning("No URL set for Legacy Repository: see /admin/config/bento_search/settings");
        return;
      } else {
        $request_url = $base_url . '?search_api_fulltext=' . $term . '&q=' . $term .
          '&format=json' . (($limit > 10) ? "items_per_page=" . $limit : "");
        $request = $this->httpClient->request('GET', $request_url);
        if ($request->getStatusCode() == 200) {
          $body = $request->getBody()->getContents();
          $this->logger->info(print_r($body, TRUE));
          // dsm(print_r($body, TRUE));
          return $body;
        }
        else {
          $this->logger->warning("Unable to reach the site with response: " . print_r($request, TRUE));
        }
      }
    }
    catch (ClientException $e) {
      $this->logger->warning("Unable to reach the site with response: " . $e);
    }
  }

  /**
   * Gets the recent items from the API.
   *
   * @param string $type
   *   The content type.
   * @param int $limit
   *   The number of results to limit to.
   */
  public function getRecentItems(string $type = 'asu_repository_item', int $limit = 12) {
    try {
      $config = $this->configFactory->get('repo_bento_search.bentosettings');
      $base_url = $config->get('recent_items_api');
      if (!trim($base_url)) {
        $this->logger->warning("No recent items api url set: see /admin/config/bento_search/settings");
        return;
      }
      else {
        $request_url = $base_url . '?_format=json&type=' . $type . '&items_per_page=' . $limit;
        $request = $this->httpClient->request('GET', $request_url);
        if ($request->getStatusCode() == 200) {
          $body = $request->getBody()->getContents();
          $this->logger->info(print_r($body, TRUE));
          // dsm(print_r($body, TRUE));
          return $body;
        }
        else {
          $this->logger->warning("Unable to reach the site with response: " . print_r($request, TRUE));
        }
      }
    }
    catch (ClientException $e) {
      $this->logger->warning("Unable to reach the site with response: " . $e);
    }
  }

}

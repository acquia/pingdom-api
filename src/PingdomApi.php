<?php

namespace Acquia\Pingdom;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Message\Response;

class PingdomApi {


  const ENDPOINT = 'https://api.pingdom.com/api/{version}/';

  const VERSION = '2.0';

  /**
   * The team account email address used for multi-user authentication.
   *
   * @var string
   */
  private $account_email;

  /**
   * Indicates whether requests should use gzip compression.
   *
   * @var bool
   */
  private $gzip;

  /**
   * Guzzle Client
   *
   * @var Client
   */
  private $client;

  /**
   * Last response from API call.
   *
   * @var Response
   */
  private $last_response;

  /**
   * Constructor.
   *
   * @param string $username
   *   The basic authentication username.
   * @param string $password
   *   The basic authentication password.
   * @param string $api_key
   *   The Pingdom API key.
   * @param bool $gzip
   *   TRUE if responses from Pingdom should use gzip compression, otherwise
   *   FALSE.
   * @param bool $debug
   *   TRUE if Guzzle should use debug mode
   *   FALSE.
   *
   * @throws MissingCredentialsException
   */
  public function __construct($username, $password, $api_key, $gzip = FALSE, $debug = FALSE) {
    if (empty($username) || empty($password) || empty($api_key)) {
      throw new MissingCredentialsException('Missing Pingdom credentials. Please supply the username, password, and api_key parameters.');
    }

    $this->client = new Client([
      'base_url' => [self::ENDPOINT, ['version' => self::VERSION]],
      'defaults' => [
        'headers' => [
          'Content-Type' => 'application/json; charset=utf-8',
          'App-Key' => $api_key,
          'User-Agent' => 'PingdomApi/1.0',
        ],
        'auth'    => [$username, $password],
        'decode_content' => 'gzip',
        'debug' => $debug,
      ]
    ]);
  }

  /**
   * Sets the Pingdom Team account email address.
   *
   * @param string $account_email
   *   A specific multi-account email address.
   */
  public function setAccount($account_email) {
    $this->account_email = $account_email;
  }

  /**
   * Fetches the list of domains being monitored in Pingdom.
   *
   * @return array
   *   An array of domains, indexed by check ID.
   */
  public function getDomains() {
    $domains = array();
    $checks = $this->getChecks();
    foreach ($checks as $check) {
      $domains[$check->id] = $check->hostname;
    }
    return $domains;
  }

  /**
   * Retrieves a list of checks.
   *
   * @param int $limit
   *   Limits the number of returned checks to the specified quantity (max value
   *   is 25000).
   * @param int $offset
   *   Offset for listing (requires limit).
   *
   * @return array
   *   An indexed array of checks.
   */
  public function getChecks($limit = NULL, $offset = NULL) {
    $parameters = array();
    if (!empty($limit)) {
      $parameters['limit'] = $limit;
      if (!empty($offset)) {
        $parameters['offset'] = $offset;
      }
    }
    $parameters['tags'] = 'pfizer';
    $parameters['include_tags'] = true;
    $data = $this->request('GET', 'checks', $parameters);
    return $data->checks;
  }

  /**
   * Retrieves detailed information about a specified check.
   *
   * @param int $check_id
   *   The ID of the check to retrieve.
   *
   * @return array
   *   An array of information about the check.
   */
  public function getCheck($check_id) {
    $this->ensureParameters(array('check_id' => $check_id), __METHOD__);
    $data = $this->request('GET', "checks/${check_id}");
    return $data->check;
  }

  /**
   * Adds a new check.
   *
   * @param array $check
   *   An array representing the check to create. The only required properties
   *   are "name" and "host", default values for the other properties will be
   *   assumed if not explicitly provided.
   * @param array $defaults
   *   An array of default settings for the check.
   *
   * @return string
   *   A success message.
   */
  public function addCheck($check, $defaults = array()) {
    $this->ensureParameters(array(
      'name' => $check['name'],
      'host' => $check['host'],
      'type' => $check['type'],
    ), __METHOD__);
    $check += $defaults;
    $data = $this->request('POST', 'checks', $check);
    return sprintf('Created check %s for %s at http://%s%s', $data->check->id, $check['name'], $check['host'], $check['type']);
  }

  /**
   * Pauses a check.
   *
   * @param int $check_id
   *   The ID of the check to pause.
   *
   * @return string
   *   The returned response message.
   */
  public function pauseCheck($check_id) {
    $this->ensureParameters(array('check_id' => $check_id), __METHOD__);
    $check = array(
      'paused' => TRUE,
    );
    return $this->modifyCheck($check_id, $check);
  }

  /**
   * Unpauses a check.
   *
   * @param array $check_id
   *   The ID of the check to pause.
   *
   * @return string
   *   The returned response message.
   */
  public function unpauseCheck($check_id) {
    $this->ensureParameters(array('check_id' => $check_id), __METHOD__);
    $check = array(
      'paused' => FALSE,
    );
    return $this->modifyCheck($check_id, $check);
  }

  /**
   * Pauses multiple checks.
   *
   * @param array $check_ids
   *   An array of check IDs to pause.
   *
   * @return string
   *   The returned response message.
   */
  public function pauseChecks($check_ids) {
    $this->ensureParameters(array('check_ids' => $check_ids), __METHOD__);
    $parameters = array(
      'paused' => TRUE,
    );
    return $this->modifyChecks($check_ids, $parameters);
  }

  /**
   * Unpauses multiple checks.
   *
   * @param array $check_ids
   *   An array of check IDs to unpause.
   *
   * @return string
   *   The returned response message.
   */
  public function unpauseChecks($check_ids) {
    $this->ensureParameters(array('check_ids' => $check_ids), __METHOD__);
    $parameters = array(
      'paused' => FALSE,
    );
    return $this->modifyChecks($check_ids, $parameters);
  }

  /**
   * Modifies a check.
   *
   * @param int $check_id
   *   The ID of the check to modify.
   * @param array $parameters
   *   An array of settings by which to modify the check.
   *
   * @return string
   *   The returned response message.
   */
  public function modifyCheck($check_id, $parameters) {
    $this->ensureParameters(array(
      'check_id' => $check_id,
      'parameters' => $parameters,
    ), __METHOD__);
    $data = $this->request('PUT', "checks/${check_id}", $parameters);
    return $data->message;
  }

  /**
   * Modifies multiple checks.
   *
   * Pingdom allows all checks to be modified at once when the "checkids"
   * parameter is not supplied but since that is a very destructive operation we
   * require the check IDs to be explicitly specified. See modifyAllChecks() if
   * you need to modify all checks at once.
   *
   * @param array $check_ids
   *   An array of check IDs to modify.
   * @param array $parameters
   *   An array of parameters by which to modify the given checks:
   *   - paused: TRUE for paused; FALSE for unpaused.
   *   - resolution: An integer specifying the check frequency.
   *
   * @return string
   *   The returned response message.
   */
  public function modifyChecks($check_ids, $parameters) {
    $this->ensureParameters(array(
      'check_ids' => $check_ids,
      'parameters' => $parameters,
    ), __METHOD__);
    $parameters['checkids'] = implode(',', $check_ids);
    $data = $this->request('PUT', 'checks', $parameters);
    return $data->message;
  }

  /**
   * Modifies all checks.
   *
   * This method can be used to modify all checks at once. Check modification by
   * this method is limited to adjusting the paused status and check frequency.
   * This is a relatively destructive operation so please be careful that you
   * intend to modify all checks before calling this method.
   *
   * @param array $parameters
   *   An array of parameters by which to modify the given checks:
   *   - paused: TRUE for paused; FALSE for unpaused.
   *   - resolution: An integer specifying the check frequency.
   *
   * @return string
   *   The returned response message.
   */
  public function modifyAllChecks($parameters) {
    $this->ensureParameters(array('parameters' => $parameters), __METHOD__);
    $data = $this->request('PUT', 'checks', $parameters);
    return $data->message;
  }

  /**
   * Removes a check.
   *
   * @param int $check_id
   *   The ID of the check to remove.
   *
   * @return string
   *   The returned response message.
   */
  public function removeCheck($check_id) {
    $this->ensureParameters(array('check_id' => $check_id), __METHOD__);
    $data = $this->request('DELETE', "checks/${check_id}");
    return $data->message;
  }

  /**
   * Removes multiple checks.
   *
   * @param int $check_ids
   *   An array of check IDs to remove.
   *
   * @return string
   *   The returned response message.
   */
  public function removeChecks($check_ids) {
    $this->ensureParameters(array('check_ids' => $check_ids), __METHOD__);
    $check_ids = implode(',', $check_ids);
    $parameters = array(
      'delcheckids' => $check_ids,
    );
    $data = $this->request('DELETE', 'checks', $parameters);
    return $data->message;
  }

  /**
   * Gets the list of contacts stored in Pingdom.
   *
   * @param int $limit
   *   Limits the number of returned contacts to the specified quantity.
   * @param int $offset
   *   The offset for the listing (requires limit).
   *
   * @return string
   *   The returned response message.
   */
  public function getContacts($limit = NULL, $offset = NULL) {
    $parameters = array();
    if (!empty($limit)) {
      $parameters['limit'] = $limit;
      if (!empty($offset)) {
        $parameters['offset'] = $offset;
      }
    }
    $data = $this->request('GET', 'contacts', $parameters);
    return $data->contacts;
  }

  /**
   * Fetches a report about remaining account credits.
   *
   * @return string
   *   The returned response message.
   */
  public function getCredits() {
    $data = $this->request('GET', 'credits');
    return $data->credits;
  }

  /**
   * Fetches a list of actions (alerts) that have been generated.
   *
   * @return string
   *   The returned response message.
   */
  public function getActions() {
    $data = $this->request('GET', 'actions');
    return $data->actions;
  }

  /**
   * Fetches the latest root cause analysis results for a specified check.
   *
   * @param int $check_id
   *   The ID of the check.
   * @param array $parameters
   *   An array of parameters for the request.
   *
   * @return string
   *   The returned response message.
   */
  public function getAnalysis($check_id, $parameters = array()) {
    $this->ensureParameters(array('check_id' => $check_id), __METHOD__);
    $data = $this->request('GET', "analysis/${check_id}", $parameters);
    return $data->analysis;
  }

  /**
   * Fetches the raw root cause analysis for a specified check.
   *
   * @param int $check_id
   *   The ID of the check.
   * @param int $analysis_id
   *   The analysis ID.
   * @param array $parameters
   *   An array of parameters for the request.
   *
   * @return string
   *   The returned response message.
   */
  public function getRawAnalysis($check_id, $analysis_id, $parameters = array()) {
    $this->ensureParameters(array(
      'check_id' => $check_id,
      'analysis_id' => $analysis_id,
    ), __METHOD__);
    $data = $this->request('GET', "analysis/{$check_id}/{$analysis_id}", $parameters);
    return $data;
  }

  /**
   * Checks that required parameters were provided.
   *
   * PHP only triggers a warning for missing parameters and continues with
   * program execution. To avoid calling the Pingdom API with known malformed
   * data, we throw an exception if we find that something required is missing.
   *
   * @param array $parameters
   *   An array of parameters to check, keyed by parameter name with the
   *   parameter itself as the value.
   * @param string $method
   *   The calling method's name.
   *
   * @throws MissingParameterException
   */
  public function ensureParameters($parameters = array(), $method) {
    if (empty($parameters) || empty($method)) {
      throw new MissingParameterException(sprintf('%s called without required parameters.', __METHOD__));
    }
    foreach ($parameters as $parameter => $value) {
      if (!isset($value)) {
        throw new MissingParameterException(sprintf('Missing required %s parameter in %s', $parameter, $method));
      }
    }
  }

  /**
   * Makes a request to the Pingdom REST API.
   *
   * @param string $method
   *   The HTTP request method e.g. GET, POST, and PUT.
   * @param string $resource
   *   The resource location e.g. checks/{checkid}.
   * @param array $parameters
   *   The request parameters, if any are required. This is used to build the
   *   URL query string.
   * @param array $headers
   *   Additional request headers, if any are required.
   *
   * @return object
   *   An object containing the response data.
   */
  public function request($method, $resource, $parameters = array(), $headers = array()) {
    if (!empty($this->account_email)) {
      $headers[] = 'Account-Email: '.$this->account_email;
    }

    // The Pingdom API requires boolean values to be transmitted as "true" and
    // "false" string representations. To preserve the convenience of using the
    // boolean types we will convert them here.
    $parameters = array_map(function ($a) {
      return !is_bool($a) ? $a : ($a ? 'true' : 'false');
    }, $parameters);

    try {
      $method = strtolower($method);
      if (method_exists($this->client, $method)) {
        $response = $this->client->$method($resource, [
          'headers' => $headers,
          'query' => $parameters,
        ]);
      }
      else {
        throw new \Exception($method . ' is not supported.');
      }
    }
    catch (ClientException $e) {
      $status = $e->getResponse()->getStatusCode();
      $message =  $this->getError($e->getResponse()->json(['object' => true]), $status);
      throw new ClientErrorException(sprintf('Client error: %s', $message), $status);
    }
    catch (ServerException $e) {
      $status = $e->getResponse()->getStatusCode();
      $message =  $this->getError($e->getResponse()->json(['object' => true]), $status);
      throw new ServerErrorException(sprintf('Server error: %s', $message), $status);
    }

    $this->setLastResponse($response);
    $data = $response->json(['object' => true]);
    return $data;
  }

  /**
   * Gets the human-readable error message for a failed request.
   *
   * @param object $response_data
   *   The object containing the response data.
   * @param int $status
   *   The HTTP status code.
   *
   * @return string
   *   The error message.
   */
  protected function getError($response_data, $status) {
    if (!empty($response_data->error)) {
      $error = $response_data->error;
      $message = sprintf('%s %s: %s',
        $error->statuscode,
        $error->statusdesc,
        $error->errormessage);
    }
    else {
      $message = sprintf('Error code: %s. No reason was given by Pingdom for the error.', $status);
    }
    return $message;
  }

  /**
   * @return Client
   */
  public function getClient()
  {
    return $this->client;
  }

  /**
   * @return Response
   */
  public function getLastResponse()
  {
    return $this->last_response;
  }

  /**
   * @param Response $last_response
   */
  public function setLastResponse($last_response)
  {
    $this->last_response = $last_response;
  }

}


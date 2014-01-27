<?php

namespace Acquia\Pingdom;
use Guzzle\Http\Client;
use Acquia\Pingdom\MissingCredentialsException;
use Acquia\Pingdom\MissingParameterException;

class PingdomApi {

  const ENDPOINT = 'https://api.pingdom.com/api/2.0/';

  /**
   * The username to access the service.
   *
   * @var string
   */
  private $username;

  /**
   * The password to access the service.
   *
   * @var string
   */
  private $password;

  /**
   * The Pingdom API key.
   *
   * @var string
   */
  private $api_key;

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
   * @param string $account_email
   *   A specific multi-account email address, if using a Pingdom Team account.
   *
   * @throws MissingCredentialsException
   */
  public function __construct($username, $password, $api_key, $gzip = FALSE, $account_email = NULL) {
    if (empty($username) || empty($password) || empty($api_key)) {
      throw new MissingCredentialsException('Missing Pingdom credentials. Please supply the username, password, and api_key parameters.');
    }
    $this->username = $username;
    $this->password = $password;
    $this->api_key = $api_key;
    $this->account_email = $account_email;
    $this->gzip = $gzip;
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
      'url' => $check['url'],
    ), __METHOD__);
    $check += $defaults;
    $data = $this->request('POST', 'checks', $check);
    return sprintf('Created check %s for %s at http://%s%s', $data->check->id, $check['name'], $check['host'], $check['url']);
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
        throw new MissingParameterException(sprintf('Missing required %s parameter in %s', $parameter,  $method));
      }
    }
  }

  /**
   * Formats query parameters to match the required types in the Pingdom API.
   *
   * Boolean values in PHP are converted to integer string representations in
   * rawurlencode but the Pingdom API requires these to be "true" and "false".
   * To preserve the convenience of using boolean types we do the conversion
   * here.
   *
   * @param array $parameters
   *   The query parameters of the request.
   *
   * @return array
   *   The query parameters with their values formatted how the Pingdom API
   *   requires them to be.
   */
  public function formatParameters($parameters) {
    foreach ($parameters as $property => $value) {
      if ($value === FALSE) {
        $parameters[$property] = 'false';
      }
      if ($value === TRUE) {
        $parameters[$property] = 'true';
      }
    }
    return $parameters;
  }

  /**
   * Makes a request to the Pingdom REST API.
   *
   * @param string $method
   *   The HTTP request method.
   * @param string $resource
   *   The resource location.
   * @param array $parameters
   *   The request parameters, if any are required.
   * @param array $headers
   *   Additional request headers, if any are required.
   *
   * @return object
   *   An object containing the response data.
   */
  public function request($method = 'GET', $resource, $parameters = array(), $headers = array()) {
    $client = new Client(self::ENDPOINT);
    $headers['App-Key'] = $this->api_key;
    if (!empty($this->account_email)) {
      $headers['Account-Email'] = $this->account_email;
    }
    if ($this->gzip) {
      $headers['Accept-Encoding'] = 'gzip';
    }
    $options = array(
      'query' => $this->formatParameters($parameters),
    );
    $request = $client->createRequest($method, $resource, $headers, NULL, $options);
    $request->setAuth($this->username, $this->password);
    $response = $request->send();
    return json_decode($response->getBody(TRUE));
  }

}


<?php

use Acquia\Pingdom\PingdomApi;
use Acquia\Pingdom\MissingParameterException;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Subscriber\Mock;

class UnitTest extends PHPUnit_Framework_TestCase {

  /**
   * @var PingdomApi
   */
  protected $pingdom;

  protected $default_check = array(
    'name' => 'name',
    'host' => 'host',
    'type' => 'type',
  );

  public function setUp() {
    $this->pingdom = new PingdomApi('username', 'password', 'api_key');
  }

  public function tearDown() {
    $this->default_check = null;
    $this->pingdom = null;
  }

  /**
   * Test the Pingdom username is required.
   *
   * @expectedException Acquia\Pingdom\MissingCredentialsException
   */
  public function testMissingCredentialsUsername() {
    new PingdomApi(null, 'password', 'api_key');
  }

  /**
   * Test the Pingdom password is required.
   *
   * @expectedException Acquia\Pingdom\MissingCredentialsException
   */
  public function testMissingCredentialsPassword() {
    new PingdomApi('username', null, 'api_key');
  }

  /**
   * Test the Pingdom API key is required.
   *
   * @expectedException Acquia\Pingdom\MissingCredentialsException
   */
  public function testMissingCredentialsApiKey() {
    new PingdomApi('username', 'password', null);
  }

  /**
   * Test getCheck() requires the $check_id parameter.
   *
   * @expectedException Acquia\Pingdom\MissingParameterException
   */
  public function testMissingParameterGetCheck() {
    $this->pingdom->getCheck(null);
  }

  /**
   * Test addCheck() requires the $check parameter.
   *
   * @expectedException Acquia\Pingdom\MissingParameterException
   */
  public function testMissingParameterAddCheckNull() {
    $this->pingdom->addCheck(null);
  }

  /**
   * Test addCheck() requires the name index of the $check parameter.
   *
   * @expectedException Acquia\Pingdom\MissingParameterException
   */
  public function testMissingParameterAddCheckName() {
    $check = $this->default_check;
    $check['name'] = null;
    $this->pingdom->addCheck($check);
  }

  /**
   * Test addCheck() requires the host index of the $check parameter.
   *
   * @expectedException Acquia\Pingdom\MissingParameterException
   */
  public function testMissingParameterAddCheckHost() {
    $check = $this->default_check;
    $check['host'] = null;
    $this->pingdom->addCheck($check);
  }

  /**
   * Test pauseCheck() requires the $check_id parameter.
   *
   * @expectedException Acquia\Pingdom\MissingParameterException
   */
  public function testMissingParameterPauseCheck() {
    $this->pingdom->pauseCheck(null);
  }

  /**
   * Test unpauseCheck() requires the $check_id parameter.
   *
   * @expectedException Acquia\Pingdom\MissingParameterException
   */
  public function testMissingParametersUnpauseCheck() {
    $this->pingdom->unpauseCheck(null);
  }

  /**
   * Test pauseChecks() requires the $check_ids parameter.
   *
   * @expectedException Acquia\Pingdom\MissingParameterException
   */
  public function testMissingParametersPauseChecks() {
    $this->pingdom->pauseChecks(null);
  }

  /**
   * Test unpauseChecks() require the $check_ids parameter.
   *
   * @expectedException Acquia\Pingdom\MissingParameterException
   */
  public function testMissingParametersUnpauseChecks() {
    $this->pingdom->unpauseChecks(null);
  }

  /**
   * Test modifyCheck() requires the $check_id parameter.
   *
   * @expectedException Acquia\Pingdom\MissingParameterException
   */
  public function testMissingParametersModifyCheckCheckId() {
    $this->pingdom->modifyCheck(null, array('parameter' => 'value'));
  }

  /**
   * Test modifyCheck() requires the $parameters parameter.
   *
   * @expectedException Acquia\Pingdom\MissingParameterException
   */
  public function testMissingParametersModifyCheckParameters() {
    $this->pingdom->modifyCheck(12345678, null);
  }

  /**
   * Test modifyChecks() requires the $check_id parameter.
   *
   * @expectedException Acquia\Pingdom\MissingParameterException
   */
  public function testMissingParametersModifyChecksCheckId() {
    $this->pingdom->modifyChecks(null, array('parameter' => 'value'));
  }

  /**
   * Test modifyChecks() requires the $parameters parameter.
   *
   * @expectedException Acquia\Pingdom\MissingParameterException
   */
  public function testMissingParametersModifyChecksParameters() {
    $this->pingdom->modifyChecks(12345678, null);
  }

  /**
   * Test modifyAllChecks() requires the $parameters parameter.
   *
   * @expectedException Acquia\Pingdom\MissingParameterException
   */
  public function testMissingParametersModifyAllChecks() {
    $this->pingdom->modifyAllChecks(null);
  }

  /**
   * Test removeCheck() requires the $check_id parameter.
   *
   * @expectedException Acquia\Pingdom\MissingParameterException
   */
  public function testMissingParametersRemoveCheck() {
    $this->pingdom->removeCheck(null);
  }

  /**
   * Test removeChecks() requires the $check_ids parameter.
   *
   * @expectedException Acquia\Pingdom\MissingParameterException
   */
  public function testMissingParametersRemoveChecks() {
    $this->pingdom->removeChecks(null);
  }

  /**
   * Test getAnalysis() requires the $check_id parameter.
   *
   * @expectedException Acquia\Pingdom\MissingParameterException
   */
  public function testMissingParametersGetAnalysis() {
    $this->pingdom->getAnalysis(null);
  }

  /**
   * Test getRawAnalysis() requires the $check_id parameter.
   *
   * @expectedException Acquia\Pingdom\MissingParameterException
   */
  public function testMissingParametersGetRawAnalysisCheckId() {
    $this->pingdom->getRawAnalysis(null, 12345678);
  }

  /**
   * Test getRawAnalysis() requires the $analysis_id parameter.
   *
   * @expectedException Acquia\Pingdom\MissingParameterException
   */
  public function testMissingParametersGetRawAnalysisAnalysisId() {
    $this->pingdom->getRawAnalysis(12345678, null);
  }

  /**
   * Test ensureParameters() requires the $parameters parameter.
   *
   * @expectedException Acquia\Pingdom\MissingParameterException
   */
  public function testMissingParametersEnsureParameters() {
    $this->pingdom->ensureParameters(null, __method__);
  }

  /**
   * Test ensureParameters() requires the $method parameter.
   *
   * @expectedException Acquia\Pingdom\MissingParameterException
   */
  public function testMissingParametersEnsureMethod() {
    $this->pingdom->ensureParameters(array(), null);
  }

  /**
   * Test ensureParameters() handles empty parameter values correctly.
   */
  public function testMissingParametersEnsureParametersEmptyValues() {
    $parameters = array(
      'bool' => FALSE,
      'int' => 0,
      'float' => 0.0,
      'string' => '',
      'array' => array(),
    );
    try {
      $error = FALSE;
      $this->pingdom->ensureParameters($parameters, __method__);
    }
    catch (MissingParameterException $e) {
      $error = TRUE;
    }
    $this->assertFalse($error);
  }

  /**
   * Test buildRequestUrl() handles query parameters correctly.
   */
  public function testBuildRequestUrl()
  {
    $api = new PingdomApi('user', 'password', 'api_key');
    // Create a mock subscriber and queue a response.
    $mockResponse = new Response(200);
    $mockResponseBody = \GuzzleHttp\Stream\Stream::factory('{}');
    $mockResponse->setBody($mockResponseBody);
    $mock = new Mock([
      $mockResponse,
    ]);
    $api->getClient()->getEmitter()->attach($mock);

    $parameters = array(
      'bool_true' => true,
      'bool_false' => false,
      'int_true' => 1,
      'int_false' => 0,
    );
    $api->request('GET', 'resource', $parameters);
    $expected = 'https://api.pingdom.com/api/2.0/resource?bool_true=true&bool_false=false&int_true=1&int_false=0';
    $this->assertSame($api->getLastResponse()->getEffectiveUrl(), $expected);
  }

  /**
   * Test request() handles 400 response
   *
   * @expectedException Acquia\Pingdom\ClientErrorException
   * @expectedExceptionMessage Client error: 403 Forbidden: Something went wrong! This string describes what happened.
   */
  public function testRequest400() {
    $api = new PingdomApi('user', 'password', 'api_key');

    // Create a mock subscriber and queue a response.

    $mockResponse = new Response(403);
    $mockResponseBody = \GuzzleHttp\Stream\Stream::factory('{
      "error":{
      "statuscode":403,
      "statusdesc":"Forbidden",
      "errormessage":"Something went wrong! This string describes what happened."
    }
    }');
    $mockResponse->setBody($mockResponseBody);
    $mock = new Mock([
      $mockResponse,
    ]);
    $api->getClient()->getEmitter()->attach($mock);
    $api->getChecks();
  }

  /**
   * Test request() handles 500 response
   *
   * @expectedException Acquia\Pingdom\ServerErrorException
   * @expectedExceptionMessage Server error: 503 Service unavailable: Try again later.
   */
  public function testRequest500() {
    $api = new PingdomApi('user', 'password', 'api_key');

    // Create a mock subscriber and queue a response.
    $mockResponse = new Response(503);
    $mockResponseBody = \GuzzleHttp\Stream\Stream::factory('{
      "error":{
      "statuscode":503,
      "statusdesc":"Service unavailable",
      "errormessage":"Try again later."
    }
    }');
    $mockResponse->setBody($mockResponseBody);
    $mock = new Mock([
      $mockResponse,
    ]);
    $api->getClient()->getEmitter()->attach($mock);
    $api->getChecks();
  }

  /**
   * @expectedException \Exception
   * @expectedExceptionMessage foo is not supported.
   */
  public function testUndefinedMethod() {
    $api = new PingdomApi('user', 'password', 'api_key');
    $api->request('foo', '/');
  }

}


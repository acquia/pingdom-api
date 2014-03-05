<?php

use Acquia\Pingdom\PingdomApi;
use Acquia\Pingdom\MissingCredentialsException;
use Acquia\Pingdom\MissingParameterException;

class UnitTest extends PHPUnit_Framework_TestCase {

  protected $pingdom;

  protected $default_check = array(
    'name' => 'name',
    'host' => 'host',
    'url' => 'url',
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
    $api = new PingdomApi(null, 'password', 'api_key');
  }

  /**
   * Test the Pingdom password is required.
   *
   * @expectedException Acquia\Pingdom\MissingCredentialsException
   */
  public function testMissingCredentialsPassword() {
    $api = new PingdomApi('username', null, 'api_key');
  }

  /**
   * Test the Pingdom API key is required.
   *
   * @expectedException Acquia\Pingdom\MissingCredentialsException
   */
  public function testMissingCredentialsApiKey() {
    $api = new PingdomApi('username', 'password', null);
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
   * Test addCheck() requires the url index of the $check parameter.
   *
   * @expectedException Acquia\Pingdom\MissingParameterException
   */
  public function testMissingParameterAddCheckUrl() {
    $check = $this->default_check;
    $check['url'] = null;
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
  public function testBuildRequestUrl() {
    ini_set('arg_separator.output', '&amp;');
    $parameters = array(
      'bool_true' => TRUE,
      'bool_false' => FALSE,
      'int_true' => 1,
      'int_false' => 0,
    );
    $coerced = $this->pingdom->buildRequestUrl('resource', $parameters);
    $expected = 'https://api.pingdom.com/api/2.0/resource?bool_true=true&bool_false=false&int_true=1&int_false=0';
    $this->assertSame($coerced, $expected);
  }

}


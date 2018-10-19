<?php

use Drupal\DrupalExtension\Context\RawDrupalContext;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;

use Behat\Gherkin\Node\StepNode,
  Behat\Gherkin\Node\ScenarioNode,
  Behat\Gherkin\Node\FeatureNode;
use Behat\Mink\Driver\Selenium2Driver;

use Drupal\Component\Utility\SafeMarkup;
use Behat\Mink\Exception\UnsupportedDriverActionException;
use Behat\Behat\Tester\Exception\PendingException;

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Testwork\Hook\Scope\BeforeSuiteScope;
use Behat\Testwork\Hook\Scope\AfterSuiteScope;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_price\Price;
use Drupal\file\Entity\File;


/**
 * Defines application features from the specific context.
 */
class FeatureContext extends RawDrupalContext implements Context {

  protected $screenshot_dir = '/tmp';

  protected $m_config, $h_config, $saved_mailsystem_defaults, $saved_honeypot_time_limit;


  /**
   * Initializes context.
   *
   * Every scenario gets its own context instance.
   * You can also pass arbitrary arguments to the
   * context constructor through behat.yml.
   */
  public function __construct($parameters) {
    $this->parameters = $parameters;
    if (isset($parameters['screenshot_dir'])) {
      $this->screenshot_dir = $parameters['screenshot_dir'];
    }
  }

  /**
   * Take screenshot when step fails. Works only with Selenium2Driver.
   * Screenshot is saved at [Date]/[Feature]/[Scenario]/[Step].jpg
   *
   * @AfterStep
   */
  /*
  public function after(Behat\Behat\Hook\Scope\AfterStepScope $scope) {
    if ($scope->getTestResult()->getResultCode() === 99) {
      $driver = $this->getSession()->getDriver();
      if ($driver instanceof Behat\Mink\Driver\Selenium2Driver) {

        $featureFolder = preg_replace('/\W/', '', $scope->getFeature()->getTitle());
        $scenario = current($scope->getFeature()->getScenarios());
        $scenarioName = $scenario->getTitle();

        $fileName = preg_replace('/\W/', '', $scenarioName) . '.png';

        //create screenshots directory if it doesn't exist
        print "DIR: {$this->screenshot_dir}/{$featureFolder}";
        if (!file_exists($this->screenshot_dir . '/' . $featureFolder)) {
          mkdir($this->screenshot_dir . '/' . $featureFolder);
        }

        $image_data = $this->getSession()->getDriver()->getScreenshot();
        $filePath = $this->screenshot_dir . '/' . $featureFolder . '/' . $fileName;
        file_put_contents($filePath, $image_data);

        print 'Screenshot at: '. $filePath;
      }
    }
  }
  */

  /**
   * Checks, that form element with specified label is visible on page.
   *
   * @Then /^(?:|I )should see an? "(?P<label>[^"]*)" form element$/
   */
  public function assertFormElementOnPage($label) {
    $element = $this->getSession()->getPage();
    $nodes = $element->findAll('css', '.form-item label');
    foreach ($nodes as $node) {
      if ($node->getText() === $label) {
        if ($node->isVisible()) {
          return;
        }
        else {
          throw new \Exception("Form item with label \"$label\" not visible.");
        }
      }
    }
    throw new \Behat\Mink\Exception\ElementNotFoundException($this->getSession(), 'form item', 'label', $label);
  }

  /**
   * Checks, that form element with specified label and type is visible on page.
   *
   * @Then /^(?:|I )should see an? "(?P<label>[^"]*)" (?P<type>[^"]*) form element$/
   */
  public function assertTypedFormElementOnPage($label, $type) {
    $container = $this->getSession()->getPage();
    $pattern = '/(^| )form-type-' . preg_quote($type) . '($| )/';
    $label_nodes = $container->findAll('css', '.form-item label');
    foreach ($label_nodes as $label_node) {
      // Note: getText() will return an empty string when using Selenium2D. This
      // is ok since it will cause a failed step.
      if ($label_node->getText() === $label
        && preg_match($pattern, $label_node->getParent()->getAttribute('class'))
        && $label_node->isVisible()) {
        return;
      }
    }
    throw new \Behat\Mink\Exception\ElementNotFoundException($this->getSession(), $type . ' form item', 'label', $label);
  }

  /**
   * Checks, that element with specified CSS is not visible on page.
   *
   * @Then /^(?:|I )should not see an? "(?P<label>[^"]*)" form element$/
   */
  public function assertFormElementNotOnPage($label) {
    $element = $this->getSession()->getPage();
    $nodes = $element->findAll('css', '.form-item label');
    foreach ($nodes as $node) {
      // Note: getText() will return an empty string when using Selenium2D. This
      // is ok since it will cause a failed step.
      if ($node->getText() === $label && $node->isVisible()) {
        throw new \Exception();
      }
    }
  }

  /**
   * Checks, that form element with specified label and type is not visible on page.
   *
   * @Then /^(?:|I )should not see an? "(?P<label>[^"]*)" (?P<type>[^"]*) form element$/
   */
  public function assertTypedFormElementNotOnPage($label, $type) {
    $container = $this->getSession()->getPage();
    $pattern = '/(^| )form-type-' . preg_quote($type) . '($| )/';
    $label_nodes = $container->findAll('css', '.form-item label');
    foreach ($label_nodes as $label_node) {
      // Note: getText() will return an empty string when using Selenium2D. This
      // is ok since it will cause a failed step.
      if ($label_node->getText() === $label
        && preg_match($pattern, $label_node->getParent()->getAttribute('class'))
        && $label_node->isVisible()) {
        throw new \Behat\Mink\Exception\ElementNotFoundException($this->getSession(), $type . ' form item', 'label', $label);
      }
    }
  }


  /**
   * @Then /^I should see "([^"]*)" in the code$/
   */
  public function inspectCode($code) {
    if (!$this->assertSession()->statusCodeEquals($code)) {
      throw new Exception("There is no such value in code.");
    }
    return $this->assertSession()->statusCodeEquals($code);
  }

  /**
   * Filling the field with parameter using jQuery . Some forms can't be filled using other functions.
   *
   * @When /^(?:|I )fill the field "(?P<field>(?:[^"]|\\")*)" with value "(?P<value>(?:[^"]|\\")*)" using jQuery$/
   */
  public function checkFieldValue($id, $value) {
    $response = $this->getSession()->getDriver()->evaluateScript(
      "return jQuery('#" . $id . "').val();"
    );
    if ($response != $value) {
      throw new Exception("Value doesn't match");
    }
  }

  /**
   * @Then /^I execute jQuery click on selector "([^"]*)"$/
   */
  public function executeJQueryForSelector($arg) {
    $arg = str_replace("'", "\\'", $arg);
    $jQ = "return jQuery('" . $arg . "').click();";
    #$this->getSession()->getDriver()->evaluateScript($jQ);

    try {
      $this->getSession()->getDriver()->evaluateScript($jQ);
    } catch (Exception $e) {
      throw new \Exception("Selector isn't valid");
    }

  }

  /**
   * Setting custom size of the screen using width and height parameters
   *
   * @Given /^the custom size is "([^"]*)" by "([^"]*)"$/
   */
  public function theCustomSizeIs($width, $height) {
    $this->getSession()->resizeWindow($width, $height, 'current');
  }

  /**
   * Setting screen size to 1400x900 (desktop)
   *
   * @Given /^the size is desktop/
   */
  public function theSizeIsDesktop() {
    $this->getSession()->resizeWindow(1400, 900, 'current');
  }

  /**
   * Setting screen size to 1024x900 (tablet landscape)
   *
   * @Given /^the size is tablet landscape/
   */
  public function theSizeIsTabletLandscape() {
    $this->getSession()->resizeWindow(1024, 900, 'current');
  }

  /**
   * Setting screen size to 768x900 (tablet portrait)
   *
   * @Given /^the size is tablet portrait/
   */
  public function theSizeIsTabletPortrait() {
    $this->getSession()->resizeWindow(768, 900, 'current');
  }

  /**
   * Setting screen size to 640x900 (mobile landscape)
   *
   * @Given /^the size is mobile landscape/
   */
  public function theSizeIsMobileLandscape() {
    $this->getSession()->resizeWindow(640, 900, 'current');
  }

  /**
   * Setting screen size to 320x900 (mobile portrait)
   *
   * @Given /^the size is mobile portrait/
   */
  public function theSizeIsMobilePortrait() {
    $this->getSession()->resizeWindow(320, 900, 'current');
  }

  /**
   * Check if the port is 443(https) or 80(http) / secure or not.
   *
   * @Then /^the page is secure$/
   */
  public function thePageIsSecure() {
    $current_url = $this->getSession()->getCurrentUrl();
    if (strpos($current_url, 'https') === FALSE) {
      throw new Exception('Page is not using SSL and is not Secure');
    }
  }

  /**
   * This will cause a 3 second delay
   *
   * @Given /^I wait$/
   */
  public function iWait() {
    sleep(3);
  }

  /**
   * Hover over an item using id|name|class
   *
   * @Given /^I hover over the item "([^"]*)"$/
   */
  public function iHoverOverTheItem($arg1) {
    if ($this->getSession()->getPage()->find('css', $arg1)) {
      $this->getSession()->getPage()->find('css', $arg1)->mouseOver();
    }
    else {
      throw new Exception('Element not found');
    }
  }

  /**
   * See if Element has style eg p.padL8 has style font-size= 12px
   *
   * @Then /^the element "([^"]*)" should have style "([^"]*)"$/
   */
  public function theElementShouldHaveStyle($arg1, $arg2) {
    $element = $this->getSession()->getPage()->find('css', $arg1);
    if ($element) {
      if (strpos($element->getAttribute('style'), $arg2) === FALSE) {
        throw new Exception('Style not found');
      }
    }
    else {
      throw new Exception('Element not found');
    }
  }

  /**
   * Look for a cookie
   *
   * @Then /^I should see cookie "([^"]*)"$/
   */
  public function iShouldSeeCookie($cookie_name) {
    if ($this->getSession()->getCookie('welcome_info_name') == $cookie_name) {
      return TRUE;
    }
    else {
      throw new Exception('Cookie not found');
    }
  }

  /**
   * Setting the cookie with particular value
   *
   * @Then /^I set cookie "([^"]*)" with value "([^"]*)"$/
   */
  public function iSetCookieWithValue($cookie_name, $value) {
    $this->getSession()->setCookie($cookie_name, $value);
  }

  /**
   * Check if the cookie isn't presented
   *
   * @Then /^I should not see cookie "([^"]*)"$/
   */
  public function iShouldNotSeeCookie($cookie_name) {
    if ($this->getSession()->getCookie('welcome_info_name') == $cookie_name) {
      throw new Exception('Cookie not found');
    }
  }

  /**
   * Destroy cookies. Resetting the session
   *
   * @Then /^I reset the session$/
   */
  public function iDestroyMyCookies() {
    $this->getSession()->reset();
  }

  /**
   * See if element is visible
   *
   * @Then /^element "([^"]*)" is visible$/
   */
  public function elementIsVisible($arg) {
    $el = $this->getSession()->getPage()->find('css', $arg);
    if ($el) {
      if (!$el->isVisible()) {
        throw new Exception('Element is not visible');
      }
    }
    else {
      throw new Exception('Element not found');
    }
  }

  /**
   * See if element is not visible
   *
   * @Then /^element "([^"]*)" is not visible$/
   */
  public function elementIsNotVisible($arg) {
    $el = $this->getSession()->getPage()->find('css', $arg);
    if ($el) {
      if ($el->isVisible()) {
        throw new Exception('Element is visible');
      }
    }
    else {
      throw new Exception('Element not found');
    }
  }

  /**
   * Set a waiting time in seconds
   *
   * @Given /^I wait for "([^"]*)" seconds$/
   */
  public function iWaitForSeconds($arg1) {
    sleep($arg1);
  }

  /**
   * Switching to iFrame with Name(don't use id, title etc. ONLY NAME)
   *
   * @Given /I switch to iFrame named "([^"]*)"$/
   */
  public function iSwitchToIframeNamed($arg1) {
    $this->getSession()->switchToIFrame($arg1);
  }

  /**
   * Switching to Window with Name(don't use id, title etc. ONLY NAME)
   *
   * @Given /^I switch to window named "([^"]*)"$/
   */
  public function iSwitchPreviousToWindow($arg1) {
    $this->getSession()->switchToWindow($arg1);
  }

  /**
   * Switching to second window
   *
   * @Given /^I switch to the second window$/
   */
  public function iSwitchToSecondWindow() {
    $windowNames = $this->getSession()->getWindowNames();
    if (count($windowNames) > 1) {
      $this->getSession()->switchToWindow($windowNames[1]);
    }
  }

  /**
   * Click an element with an onclick handler
   *
   * @Given /^I click on element which has onclick handler located at "([^"]*)"$/
   */
  public function iClickOnElementWhichHasOnclickHandlerLocatedAt($item) {
    $node = $this->getSession()->getPage()->find('css', $item);
    if ($node) {
      $this->getSession()->wait(3000,
        "jQuery('{$item}').trigger('click')"
      );
    }
    else {
      throw new Exception('Element not found');
    }
  }

  /**
   * Y would be the way to up and down the page. A good default for X is 0
   *
   * @Given /^I scroll to x "([^"]*)" y "([^"]*)" coordinates of page$/
   */
  public function iScrollToXYCoordinatesOfPage($arg1, $arg2) {
    $function = "(function(){
              window.scrollTo($arg1, $arg2);
            })()";
    try {
      $this->getSession()->executeScript($function);
    } catch (Exception $e) {
      throw new \Exception("ScrollIntoView failed");
    }
  }

  /**
   * Check existence of JavaScript variable on loaded page.
   *
   * @Then /^I should see "([^"]*)" Js variable$/
   */
  public function iShouldSeeJsVariable($variable_name) {

    $javascript = <<<EOT
return (typeof $variable_name === "undefined") ? 0 : 1;
EOT;

    // Execute javascript and return variable value or undefined
    // if javascript variable not exists or equals to undefined.
    $variable_value_exist = $this->getSession()->evaluateScript($javascript);

    if (empty($variable_value_exist)) {
      throw new Exception('JavaScript variable doesn\'t exists or undefined.');
    }
  }

  /**
   * Check NON existence of JavaScript variable on loaded page.
   *
   * @Then /^I should not see "([^"]*)" Js variable$/
   */
  public function iShouldNotSeeJsVariable($variable_name) {

    $javascript = <<<EOT
return (typeof $variable_name != $variable_value_exist) ? 0 : 1;
EOT;

    // Execute javascript and return variable value or undefined
    // if javascript variable not exists or equals to undefined.
    $variable_value_exist = $this->getSession()->evaluateScript($javascript);

    if (empty($variable_value_exist)) {
      throw new Exception('JavaScript variable match.');
    }
  }

  /**
   * @Then /^I should see "([^"]*)" in the "([^"]*)" Js variable$/
   */
  public function iShouldSeeInTheJsVariable($variable_value, $variable_name) {

    $javascript = <<<EOT
return (typeof $variable_name === "undefined") ? "" : $variable_name;
EOT;

    // Execute javascript and return variable value or undefined
    // if javascript variable not exists or equals to undefined.
    $variable_value_exist = $this->getSession()->evaluateScript($javascript);

    if ($variable_value_exist === "undefined") {
      throw new Exception('JavaScript variable doesn\'t exists or undefined.');
    }

    if ($variable_value != $variable_value_exist) {
      throw new Exception('JavaScript variable value doesn\'t match.');
    }
  }

  /**
   * Scrolling to the particular element(arg1 - Nav menu selector, arg2 - element's selector to scroll to)
   *
   * @Given /^I scroll to element "([^"]*)" "([^"]*)"$/
   */
  public function iScrollToElement($arg1, $arg2) {
    $function = <<<JS
     var headerHeight = jQuery('$arg2').outerHeight(true),
          scrollBlock = jQuery('$arg1').offset().top;
 jQuery('body, html').scrollTo(scrollBlock - headerHeight);

JS;
    try {
      $this->getSession()->executeScript($function);
    } catch (Exception $e) {
      throw new \Exception("ScrollIntoElement failed");
    }
  }

  /**
   * Clicking the element using selector (works only if element is visible)
   *
   * @When /^I click the element with selector "([^"]*)"$/
   */
  public function iClickTheElement($arg) {
    $node = $this->getSession()->getPage()->find('css', $arg);
    if ($node) {
      $this->getSession()->getPage()->find('css', $arg)->click();
    }
    else {
      throw new Exception('Element not found');
    }
  }

  /**
   * Verifying that element has particular class
   *
   * @When /^element "(?P<field>(?:[^"]|\\")*)" should have class "(?P<value>(?:[^"]|\\")*)"$/
   */
  public function checkElementClass($arg, $class) {
    $response = $this->getSession()->getDriver()->evaluateScript(
      "           
            return (function () {
            var element = jQuery('" . $arg . "');
            if (element.length > 0) {
              if (element.hasClass('" . $class . "')){
                return 'Ok';
              }
              
              else {
                return 'Class doesn\'t match';
              }
            }
            else {
              return 'Selector wasn\'t found';
            }
            })();
            "
    );
    if ($response != 'Ok') {
      throw new Exception($response);
    }
  }

  /**
   * @Then user with email :email should exist
   */
  public function userWithEmailShouldExist($email) {
    $user = user_load_by_mail($email);

    if (!$user) {
      throw new Exception("No user with email " . $email . " created according to the database ");
    }
  }

  /**
   * Wait for AJAX to finish.
   *
   * @see \Drupal\FunctionalJavascriptTests\JSWebAssert::assertWaitOnAjaxRequest()
   *
   * @Given I wait for long AJAX to finish
   */
  public function iWaitForLongAjaxToFinish() {
    $condition = <<<JS
    (function() {
      function isAjaxing(instance) {
        return instance && instance.ajaxing === true;
      }
      var d7_not_ajaxing = true;
      if (typeof Drupal !== 'undefined' && typeof Drupal.ajax !== 'undefined' && typeof Drupal.ajax.instances === 'undefined') {
        for(var i in Drupal.ajax) { if (isAjaxing(Drupal.ajax[i])) { d7_not_ajaxing = false; } }
      }
      var d8_not_ajaxing = (typeof Drupal === 'undefined' || typeof Drupal.ajax === 'undefined' || typeof Drupal.ajax.instances === 'undefined' || !Drupal.ajax.instances.some(isAjaxing))
      return (
        // Assert no AJAX request is running (via jQuery or Drupal) and no
        // animation is running.
        (typeof jQuery === 'undefined' || (jQuery.active === 0 && jQuery(':animated').length === 0)) &&
        d7_not_ajaxing && d8_not_ajaxing
      );
    }());
JS;
    $result = $this->getSession()->wait(50000, $condition);
    if (!$result) {
      throw new \RuntimeException('Unable to complete AJAX request.');
    }
  }

  /**
   * Delete user with email if exists
   *
   * @Given a user with email :email doesn't exist
   */
  public function aUserWithEmailDoesntExist($email) {
    $user = user_load_by_mail($email);
    if ($user) {
      $user->delete();
    }
  }

  /**
   * @BeforeScenario @email
   */
  public function beforeEmailScenario(BeforeScenarioScope $scope) {
    $this->m_config = \Drupal::configFactory()
      ->getEditable('mailsystem.settings');
    $this->saved_mailsystem_defaults = $this->m_config->get('defaults');
    $this->m_config
      ->set('defaults.sender', 'test_mail_collector')
      ->set('defaults.formatter', 'test_mail_collector')
      ->save();
    //     Reset the state variable that holds sent messages.
    \Drupal::state()->set('system.test_mail_collector', []);

    // If we have honeypot installed then ensure that we disable time_limit
    // So that automated tests / bots can run
    $this->h_config = \Drupal::configFactory()
      ->getEditable('honeypot.settings');
    $this->saved_honeypot_time_limit = $this->h_config->get('time_limit');
    if ($this->saved_honeypot_time_limit) {
      $this->h_config
        ->set('time_limit', '0')
        ->save();
    }
  }

  /**
   * @AfterScenario @email
   */
  public function afterEmailScenario(AfterScenarioScope $scope) {
    // revert mail system after scenarios agged with @email
    $this->m_config
      ->set('defaults.sender', $this->saved_mailsystem_defaults['sender'])
      ->set('defaults.formatter', $this->saved_mailsystem_defaults['formatter'])
      ->save();

    // Ensure we protect against spambots again if honeypot is installed
    if ($this->saved_honeypot_time_limit) {
      $this->h_config
        ->set('time_limit', $this->saved_honeypot_time_limit)
        ->save();
    }
  }

  /**
   * @Then an email should be sent to :recipient
   */
  public function anEmailShouldBeSentTo($recipient) {
    // Reset state cache.
    \Drupal::state()->resetCache();
    $mails = \Drupal::state()->get('system.test_mail_collector') ?: [];
    $last_mail = end($mails);
    //    print_r($last_mail);

    if (!$last_mail) {
      throw new Exception('No mail was sent.');
    }
    if ($last_mail['to'] != $recipient) {
      throw new \Exception("Unexpected recpient: " . $last_mail['to']);
    }
  }

  /**
   * @When I follow the link in the registration email
   */
  public function iFollowTheLinkInTheRegistrationEmail() {
    $mails = \Drupal::state()->get('system.test_mail_collector') ?: [];
    $last_mail = end($mails);
    if (!$last_mail) {
      throw new Exception('No mail was sent.');
    }

    $matches = [];
    $no = preg_match("/(http:\/\/.*registrationpassword.*)/", $last_mail['body'], $matches);
    if (!$no) {
      throw new Exception('No registration password link found in the mail');
    }

    $path = $matches[1];
    $this->visitPath($path);
  }

  /**
   * @Given I have a test product with rule :rule
   */
  public function iHaveATestProductWithRule($rule) {

    $filePath = rtrim(realpath($this->getMinkParameter('files_path')), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'test.png';
    if (!is_file($filePath)) {
      throw new Exception("Image test.png cannot be found in the files_path");
    }

    $fileHandle = fopen($filePath, 'r');
    $fileData = file_save_data($fileHandle, 'public://test.png');
    fclose($fileHandle);

    $image = File::create([
      'uid' => 1,
      'filename' => 'test.png',
      'uri' => $fileData->getFileUri(),
      'status' => 1,
    ]);
    $image->save();

    $filePath = rtrim(realpath($this->getMinkParameter('files_path')), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'test.pdf';
    if (!is_file($filePath)) {
      throw new Exception("Document test.pdf cannot be found in the files_path");
    }

    $fileHandle = fopen($filePath, 'r');
    $fileData = file_save_data($fileHandle, 'public://test.pdf');
    fclose($fileHandle);

    $pdf = File::create([
      'uid' => 1,
      'filename' => 'test.pdf',
      'uri' => $fileData->getFileUri(),
      'status' => 1,
    ]);
    $pdf->save();

    $docParagraph = \Drupal\paragraphs\Entity\Paragraph::create([
      'field_pg_doc_item_file_file' => $pdf,
      'field_pg_doc_item_file_title' => 'Behat Test Document Title',
      'type' => 'pg_doc_item_file',
    ]);
    $docParagraph->save();

    $variation1 = ProductVariation::create([
      'title' => 'Behat SKU Title',
      'type' => 'doc_var',
      'sku' => 'BehatTestVariation',
      'price' => new Price('24.00', 'EUR'),
    ]);
    $variation1->save();

    $product = Product::create([
      'uid' => 1,
      'type' => 'doc',
      'title' => t('Behat Test Product'),
      'field_doc_body' => 'Behat Test Product oneliner of description!',
      'field_doc_category' => 946,
      'field_doc_dao_rules' => $rule,
      'field_doc_files' => $docParagraph,
      'field_doc_main_pic' => $image,
      'field_doc_second_pic' => $image,
      'field_doc_third_pic' => $image,
      'field_doc_overlay_body' => 'Behat Test Overlay Body',
      'field_doc_overlay_title' => 'Behat Slider Title',
      'field_doc_meta_tags' => 'a:0:{}',
      'stores' => 1,
      'variations' => [$variation1],
    ]);
    $product->save();
    drupal_flush_all_caches();
    $this->visitPath('/product/' . $product->id());
  }

  /**
   * @Given I make sure no test products exist
   */
  public function iMakeSureNoTestProductsExist() {
    $query = \Drupal::entityQuery('commerce_product');
    $query->condition('title', 'Behat Test Product');
    $ids = $query->execute();

    entity_delete_multiple('commerce_product', $ids);
  }

  /**
   * @Then after clicking :link, download starts
   */
  public function afterClickingDownloadStarts($link) {
    $page = $this->getSession()->getPage();
    $link = $page->findLink($link);
    $href = $link->getAttribute('href');
  }

  /**
   * @Then /^the option "([^"]*)" from select "([^"]*)" is selected$/
   * Example: And the "Option" from select "Select" is selected
   */
  public function theOptionFromSelectIsSelected($optionValue, $select) {
    $selectField = $this->getSession()->getPage()->find('css', $select);
    if (NULL === $selectField) {
      throw new \Exception(sprintf('The select "%s" was not found in the page %s', $select, $this->getSession()
        ->getCurrentUrl()));
    }

    $optionField = $selectField->find('xpath', "//option[@selected='selected']");
    if (NULL === $optionField) {
      throw new \Exception(sprintf('No option is selected in the %s select in the page %s', $select, $this->getSession()
        ->getCurrentUrl()));
    }

    if ($optionField->getValue() != $optionValue) {
      throw new \Exception(sprintf('The option "%s" was not selected in the page %s, %s was selected', $optionValue, $this->getSession()
        ->getCurrentUrl(), $optionField->getValue()));
    }
  }

  /**
   * Checks if the specified GET param equal to the specified value.
   * Example: Then the URL contains GET param "category"
   *
   * @Then /^the URL GET param "([^"]*)" doesn't equal "([^"]*)"$/
   */
  public function getParamExists($param, $value) {
    $url = parse_url($this->getSession()->getCurrentUrl());
    if (!empty($url['query'])) {
      parse_str($url['query'], $query);
      if (!empty($query[$param])) {
        if ($query[$param] === $value) {
          throw new \Exception("The GET param $param is equal $value. But shouldn't.");
        }
      }
    }
  }
  
}

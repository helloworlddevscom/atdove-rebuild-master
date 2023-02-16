<?php

namespace Drupal\Tests\Behat;

use Drupal\DrupalExtension\Context\RawDrupalContext;
use PHPUnit\Framework\Assert;

/**
 * Contains miscellaneous step definitions for working with browser responses.
 */
class BrowserResponseContext extends RawDrupalContext {

  /**
   * @Then /^I (?:am|should be) redirected to "([^"]*)"$/
   */
  public function iAmRedirectedTo($expectedUrl) {
    $host = \Drupal::request()->getHost();
    $split = explode($host, $this->getSession()->getCurrentUrl());
    Assert::assertEquals($split[1], $expectedUrl);
  }
}

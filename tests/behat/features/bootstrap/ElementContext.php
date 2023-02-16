<?php

namespace Drupal\Tests\Behat;

use Behat\Gherkin\Node\PyStringNode;
use Behat\Mink\Element\ElementInterface;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\Mink\Exception\ExpectationException;
use Behat\Mink\Exception\UnsupportedDriverActionException;
use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\RawDrupalContext;

/**
 * Contains miscellaneous step definitions for working with HTML elements.
 *
 * @internal
 *   This is an internal part of Lightning Core's testing system and may be
 *   changed or removed at any time without warning. It should not be extended,
 *   instantiated, or used in any way by external code! If you need to use this
 *   functionality, you should copy the relevant code into your own project.
 */
class ElementContext extends RawDrupalContext {

  /**
   * Asserts that an element is empty.
   *
   * @param string $selector
   *   The element's CSS selector.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   *   If the element contains any HTML (leading and trailing white space is
   *   trimmed out).
   *
   * @Then the :selector element(s) should be empty
   */
  public function assertEmptyElement($selector) {
    $content = $this->assertSession()
      ->elementExists('css', $selector)
      ->getHtml();

    if (trim($content)) {
      throw new ExpectationException("Expected element '$selector' to be empty, but it is not.", $this->getSession()->getDriver());
    }
  }

  /**
   * Clears a field.
   *
   * @param string $field
   *   The field to clear.
   * @param \Behat\Mink\Element\ElementInterface $container
   *   (optional) The element that contains the field.
   *
   * @When I clear :field
   */
  public function clearField($field, ElementInterface $container = NULL) {
    $this->assertSession()->fieldExists($field, $container)->setValue(FALSE);
  }

  /**
   * Asserts that a form field is present.
   *
   * Oddly, MinkContext does not appear to provide a step definition for this.
   *
   * @param string $field
   *   The field locator.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The form field element.
   *
   * @Then I should see a(n) :field field
   */
  public function assertField($field) {
    return $this->assertSession()->fieldExists($field);
  }

  /**
   * Asserts that a field element exists and is disabled.
   *
   * @param string $field
   *   The field locator.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The disabled form field element.
   *
   * @Then I should see a disabled :field field
   * @Then the :field field should be disabled
   */
  public function assertDisabledField($field) {
    return $this->assertSession()
      ->elementAttributeExists('named', ['field', $field], 'disabled');
  }

  /**
   * Asserts that a form field is not present.
   *
   * Oddly, MinkContext does not appear to provide a step definition for this.
   *
   * @param string $field
   *   The field locator.
   *
   * @Then I should not see a(n) :field field
   */
  public function assertNoField($field) {
    $this->assertSession()->fieldNotExists($field);
  }

  /**
   * Asserts that a select list has a particular option.
   *
   * @param string $select
   *   The select list to check.
   * @param string $option
   *   The option to look for.
   *
   * @Then the :select field should have a(n) :option option
   */
  public function assertOption($select, $option) {
    $this->assertSession()
      ->elementExists('named', ['option', $option], $this->assertField($select));
  }

  /**
   * Asserts that a select list does not have a particular option.
   *
   * @param string $select
   *   The select list to check.
   * @param string $option
   *   The option to look for.
   *
   * @Then the :select field should not have a(n) :option option
   */
  public function assertNoOption($select, $option) {
    $this->assertSession()
      ->elementNotExists('named', ['option', $option], $this->assertField($select));
  }

  /**
   * Asserts that a select list has a set of options.
   *
   * @param string $select
   *   The select list to check.
   * @param \Behat\Gherkin\Node\PyStringNode $options
   *   The options to look for.
   *
   * @Then the :select field should have options:
   */
  public function assertOptions($select, PyStringNode $options) {
    foreach ($options->getStrings() as $option) {
      $this->assertOption($select, $option);
    }
  }

  /**
   * Asserts that a select list does not have a set of options.
   *
   * @param string $select
   *   The select list to check.
   * @param \Behat\Gherkin\Node\PyStringNode $options
   *   The options to look for.
   *
   * @Then the :select field should not have options:
   */
  public function assertNoOptions($select, PyStringNode $options) {
    foreach ($options->getStrings() as $option) {
      $this->assertNoOption($select, $option);
    }
  }

  /**
   * Switches to a frame.
   *
   * @param string $name
   *   The frame name.
   *
   * @When I switch to the :name frame
   * @When I enter the :name frame
   */
  public function enterFrame($name) {
    $this->getSession()->switchToIFrame($name);
  }

  /**
   * Switches out of an frame, into the main window.
   *
   * @When I (switch|return) to the window
   * @When I (exit|leave) the frame
   */
  public function exitFrame() {
    $this->getSession()->switchToWindow();
  }

  /**
   * Asserts that vertical tab set exists.
   *
   * @param \Behat\Mink\Element\ElementInterface $container
   *   (optional) The element containing the vertical tab set.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The vertical tab set.
   *
   * @Then I should see a set of vertical tabs
   */
  public function assertVerticalTabs(ElementInterface $container = NULL) {
    return $this
      ->assertSession()
      ->elementExists('css', 'ul.vertical-tabs-list', $container);
  }

  /**
   * Asserts that a specific vertical tab exists.
   *
   * @param string $tab
   *   The text of the tab.
   * @param \Behat\Mink\Element\ElementInterface $container
   *   (optional) The element containing the vertical tab set.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The vertical tab.
   *
   * @Then I should see a :tab vertical tab
   * @Then I should see the :tab vertical tab
   */
  public function assertVerticalTab($tab, ElementInterface $container = NULL) {
    return $this
      ->assertSession()
      ->elementExists(
        'named',
        ['link', $tab],
        $this->assertVerticalTabs($container)
      );
  }

  /**
   * Asserts that a specific vertical tab does not exist.
   *
   * @param string $tab
   *   The text of the tab.
   * @param \Behat\Mink\Element\ElementInterface $container
   *   (optional) The element containing the vertical tab set.
   *
   * @Then I should not see a :tab vertical tab
   * @Then I should not see the :tab vertical tab
   */
  public function assertNoVerticalTab($tab, ElementInterface $container = NULL) {
    $this
      ->assertSession()
      ->elementNotExists(
        'named',
        ['link', $tab],
        $this->assertVerticalTabs($container)
      );
  }

  /**
   * Asserts that a specific set of vertical tabs exists.
   *
   * @param \Behat\Gherkin\Node\PyStringNode $tabs
   *   The text of the tabs, one per line.
   * @param \Behat\Mink\Element\ElementInterface $container
   *   (optional) The element containing the vertical tab set.
   *
   * @Then I should see the vertical tabs:
   */
  public function assertVerticalTabMultiple(PyStringNode $tabs, ElementInterface $container = NULL) {
    foreach ($tabs->getStrings() as $tab) {
      $this->assertVerticalTab($tab, $container);
    }
  }

  /**
   * Asserts that a set of specific vertical tabs does not exist.
   *
   * @param \Behat\Gherkin\Node\PyStringNode $tabs
   *   The text of the tabs, one per line.
   * @param \Behat\Mink\Element\ElementInterface $container
   *   (optional) The element containing the vertical tab set.
   *
   * @Then I should not see the vertical tabs:
   */
  public function assertNotVerticalTabMultiple(PyStringNode $tabs, ElementInterface $container = NULL) {
    foreach ($tabs->getStrings() as $tab) {
      $this->assertNoVerticalTab($tab, $container);
    }
  }

  /**
   * Asserts that horizontal tab set exists.
   *
   * @param \Behat\Mink\Element\ElementInterface $container
   *   (optional) The element containing the horizontal tab set.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The horizontal tab set.
   *
   * @Then I should see a set of horizontal tabs
   */
  public function assertHorizontalTabs(ElementInterface $container = NULL) {
    return $this
      ->assertSession()
      ->elementExists('css', 'ul.horizontal-tabs-list', $container);
  }

  /**
   * Asserts that a specific horizontal tab exists.
   *
   * @param string $tab
   *   The text of the tab.
   * @param \Behat\Mink\Element\ElementInterface $container
   *   (optional) The element containing the horizontal tab set.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The horizontal tab.
   *
   * @Then I should see a :tab horizontal tab
   * @Then I should see the :tab horizontal tab
   */
  public function assertHorizontalTab($tab, ElementInterface $container = NULL) {
    return $this
      ->assertSession()
      ->elementExists(
        'named',
        ['link', $tab],
        $this->assertHorizontalTabs($container)
      );
  }

  /**
   * Asserts that a specific horizontal tab does not exist.
   *
   * @param string $tab
   *   The text of the tab.
   * @param \Behat\Mink\Element\ElementInterface $container
   *   (optional) The element containing the horizontal tab set.
   *
   * @Then I should not see a :tab horizontal tab
   * @Then I should not see the :tab horizontal tab
   */
  public function assertNoHorizontalTab($tab, ElementInterface $container = NULL) {
    $this
      ->assertSession()
      ->elementNotExists(
        'named',
        ['link', $tab],
        $this->assertHorizontalTabs($container)
      );
  }

  /**
   * Asserts that a specific set of horizontal tabs exists.
   *
   * @param \Behat\Gherkin\Node\PyStringNode $tabs
   *   The text of the tabs, one per line.
   * @param \Behat\Mink\Element\ElementInterface $container
   *   (optional) The element containing the horizontal tab set.
   *
   * @Then I should see the horizontal tabs:
   */
  public function assertHorizontalTabMultiple(PyStringNode $tabs, ElementInterface $container = NULL) {
    foreach ($tabs->getStrings() as $tab) {
      $this->assertHorizontalTab($tab, $container);
    }
  }

  /**
   * Asserts that a set of specific horizontal tabs does not exist.
   *
   * @param \Behat\Gherkin\Node\PyStringNode $tabs
   *   The text of the tabs, one per line.
   * @param \Behat\Mink\Element\ElementInterface $container
   *   (optional) The element containing the horizontal tab set.
   *
   * @Then I should not see the horizontal tabs:
   */
  public function assertNotHorizontalTabMultiple(PyStringNode $tabs, ElementInterface $container = NULL) {
    foreach ($tabs->getStrings() as $tab) {
      $this->assertNoHorizontalTab($tab, $container);
    }
  }

  /**
   * Asserts the existence of a <details> element by its summary text.
   *
   * @param string $summary
   *   The summary text (case-insensitive).
   * @param \Behat\Mink\Element\ElementInterface $container
   *   The element in which to search for the <details> element.
   *
   * @return \Behat\Mink\Element\NodeElement|bool
   *   The <details> element, or FALSE if it was not found.
   */
  private function findDetails($summary, ElementInterface $container) {
    $lowercase_summary = mb_strtolower($summary);

    /** @var \Behat\Mink\Element\NodeElement $element */
    foreach ($container->findAll('css', 'details > summary') as $element) {
      $lowercase_element_text = mb_strtolower($element->getText());

      if ($lowercase_element_text === $lowercase_summary) {
        return $element->getParent();
      }
    }
    return FALSE;
  }

  /**
   * Asserts the existence of a <details> element by its summary text.
   *
   * @param string $summary
   *   The exact summary text.
   * @param \Behat\Mink\Element\ElementInterface $container
   *   (optional) The element in which to search for the <details> element.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The <details> element.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   *   If the element is not found in the container.
   *
   * @Then I should see a(n) :summary details element
   */
  public function assertDetails($summary, ElementInterface $container = NULL) {
    $session = $this->getSession();

    $element = $this->findDetails($summary, $container ?: $session->getPage());
    if ($element) {
      return $element;
    }
    else {
      throw new ElementNotFoundException($session->getDriver(), 'details', 'summary', $summary);
    }
  }

  /**
   * Asserts the existence of an open <details> element by its summary text.
   *
   * @param string $summary
   *   The exact summary text.
   * @param \Behat\Mink\Element\ElementInterface $container
   *   (optional) The element in which to search for the <details> element.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   *   If the <details> element is closed.
   *
   * @Then I should see an open :summary details element
   */
  public function assertOpenDetails($summary, ElementInterface $container = NULL) {
    $open = $this->assertDetails($summary, $container)->hasAttribute('open');

    if (!$open) {
      throw new ExpectationException(
        "Expected '$summary' details to be open, but it is closed.",
        $this->getSession()->getDriver()
      );
    }
  }

  /**
   * Asserts the existence of a closed <details> element by its summary text.
   *
   * @param string $summary
   *   The exact summary text.
   * @param \Behat\Mink\Element\ElementInterface $container
   *   (optional) The element in which to search for the <details> element.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   *   If the <details> element is open.
   *
   * @Then I should see a closed :summary details element
   */
  public function assertClosedDetails($summary, ElementInterface $container = NULL) {
    try {
      $this->assertOpenDetails($summary, $container);
    }
    catch (ExpectationException $e) {
      return;
    }

    throw new ExpectationException(
      "Expected '$summary' details to be closed, but it is open.",
      $this->getSession()->getDriver()
    );
  }

  /**
   * Clicks an arbitrary element, found by CSS selector.
   *
   * @param string $selector
   *   The CSS selector.
   *
   * @When I click the :selector element
   */
  public function click($selector) {
    $element = $this->assertSession()->elementExists('css', $selector);

    try {
      $this->scrollTo($selector);
    }
    catch (UnsupportedDriverActionException $e) {
      // Don't worry about it.
    }
    $element->click();
  }

  /**
   * Scrolls an element into the viewport.
   *
   * @param string $selector
   *   The element's CSS selector.
   *
   * @When I scroll to the :selector element
   */
  public function scrollTo($selector) {
    $this->getSession()
      ->executeScript('document.querySelector("' . addslashes($selector) . '").scrollIntoView()');
  }

  /**
   * Asserts that a minimum number of elements match a CSS selector.
   *
   * @param string $selector
   *   The selector.
   * @param int $n
   *   The number of elements expected to match the selector.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   *   If the fewer elements than expected match the selector.
   *
   * @Then at least :n element(s) should match :selector
   */
  public function matchAtLeast($selector, $n) {
    $session = $this->getSession();

    $count = count($session->getPage()->findAll('css', $selector));

    if ($count < $n) {
      throw new ExpectationException("Expected '$selector' to match at least $n elements, but it matched $count.", $session->getDriver());
    }
  }

  /**
   * Asserts that a number of elements match a CSS selector.
   *
   * @param string $selector
   *   The selector.
   * @param int $n
   *   The number of elements expected to match the selector.
   *
   * @Then exactly :n element(s) should match :selector
   */
  public function matchExactly($selector, $n) {
    $this->assertSession()->elementsCount('css', $selector, $n);
  }

  /**
   * Asserts that a block with a specific plugin ID is present.
   *
   * @param string $plugin_id
   *   The block plugin ID.
   *
   * @return \Behat\Mink\Element\ElementInterface
   *   The block element.
   *
   * @Then I should see a :plugin_id block
   */
  public function assertBlockExists($plugin_id) {
    return $this->assertSession()
      ->elementExists('css', "[data-block-plugin-id='$plugin_id']");
  }

  /**
   * Asserts that a block with a specific plugin ID is not present.
   *
   * @param string $plugin_id
   *   The block plugin ID.
   *
   * @Then I should not see a :plugin_id block
   */
  public function assertBlockNotExists($plugin_id) {
    $this->assertSession()
      ->elementNotExists('css', "[data-block-plugin-id='$plugin_id']");
  }

  /**
   * Asserts that an element has contextual links.
   *
   * @param \Behat\Mink\Element\ElementInterface $element
   *   The element which is expected to contain contextual links.
   *
   * @return \Behat\Mink\Element\ElementInterface
   *   The list of contextual links.
   */
  private function assertContextualLinks(ElementInterface $element) {
    return $this->assertSession()
      ->elementExists('css', 'ul.contextual-links', $element);
  }

  /**
   * Asserts that an element has a specific contextual link.
   *
   * @param \Behat\Mink\Element\ElementInterface $element
   *   The element which is expectd to contain the contextual link.
   * @param string $link_class
   *   The CSS class of the contextual link.
   *
   * @return \Behat\Mink\Element\ElementInterface
   *   The contextual link.
   */
  private function assertContextualLink(ElementInterface $element, $link_class) {
    return $this->assertSession()
      ->elementExists('css', "li.$link_class", $this->assertContextualLinks($element));
  }

  /**
   * Asserts that a block has contextual link(s).
   *
   * @param string $plugin_id
   *   The block plugin ID.
   * @param string $link_class
   *   (optional) The CSS class of a specific contextual link.
   *
   * @see ::assertContextualLinks()
   * @see ::assertContextualLink()
   * @see ::assertBlockExists()
   *
   * @Then the :plugin_id block should have contextual links
   * @Then the :plugin_id block should have a :link_class contextual link
   * @Then I should see a :plugin_id block with contextual links
   * @Then I should see a :plugin_id block with a :link_class contextual link
   */
  public function assertBlockHasContextual($plugin_id, $link_class = NULL) {
    $element = $this->assertBlockExists($plugin_id);

    return $link_class
      ? $this->assertContextualLink($element, $link_class)
      : $this->assertContextualLinks($element);
  }

  /**
   * @Then the :field field should not be empty
   */
  public function theFieldShouldNotBeEmpty($field)
  {
    if(strlen($this->assertSession()->fieldExists($field)->getValue()) === 0) {
      throw new ExpectationException("Expected '$field' to not be empty, but it was infact empty.", $this->getSession()->getDriver());
    }
  }

  /**
   * @Then I should see the text :text in the :column column
   *
   * @param $text
   * @param $column
   */
  public function iShouldSeeTheTextInTheColumn($text, $column)
  : void {
    $page = $this->getSession()->getPage();
    $tables = $page->findAll('css', 'table');

    $text_found = FALSE;
    $heading_found = FALSE;

    if (count($tables)) {
      foreach ($tables as $table) {
        // Bust up the table heading from the found table into an array.
        $dom = new \DOMDocument();
        $dom->loadHTML($table->find('css', 'thead tr')
          ->find('css', 'tr')
          ->getHtml());

        $heading_index = FALSE;
        // Iterate through table headings, try and find the index of column.
        foreach ($dom->getElementsByTagName('th') as $index => $table_heading) {
          $thead_text = $table_heading->nodeValue;

          // Views click to sort adds spaces and text to thead, this removes.
          if (strpos($thead_text, '  ')) {
            $thead_text = trim(substr($thead_text, 0, strpos($thead_text, '  ')));
          }

          if (strtolower($thead_text) === strtolower($column)) {
            $heading_index = $index;
          }
        }

        // Proceed if heading index was found.
        if ($heading_index !== FALSE) {
          $heading_found = TRUE;
          $relevant_table_cells = $table->findAll('css', 'td:nth-child(' . ($heading_index + 1) . ')');
          // Iterate over all table cells.
          foreach ($relevant_table_cells as $relevant_table_cell) {
            // Check if table cell has the text we are looking for.
            $contents = $relevant_table_cell->getText();
            if ($contents === $text) {
              $text_found = TRUE;
            }
          }
        }
      }
      if (!$heading_found) {
        throw new RuntimeException("The table heading {$column}  was not found.");
      }
      if (!$text_found) {
        $message = "No table cells in the {$column} column with text {$text} were found.";
        throw new RuntimeException($message);
      }
    }
    else {
      throw new RuntimeException('No tables found on the page.');
    }
  }

  /**
   * @Then the :button button should be disabled
   */
  public function theButtonShouldBeDisabled($button)
  {
    $session = $this->getSession();
    $button = $session->getPage()->findButton($button);
    return $button->getAttribute('disabled');
  }


}

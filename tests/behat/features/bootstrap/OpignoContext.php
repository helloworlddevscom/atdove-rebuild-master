<?php

namespace Drupal\Tests\Behat;

use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\h5p\Entity\H5PContent;
use Drupal\opigno_module\Entity\OpignoActivity;
Use Drupal\node\Entity\Node;
use RuntimeException;
use Drupal\opigno_module\Entity\OpignoAnswer;
use Drupal\user\Entity\User;
use Drupal\Core\Url;
use Drupal\atdove_organizations\OrganizationsManager;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Behat\Tester\Exception\PendingException;
use Drupal\group\Entity\Group;
use Drupal\paragraphs\Entity\Paragraph;


/**
 * Defines application features from the specific context.
 */
class OpignoContext extends RawDrupalContext {

  protected $h5pContent = [];
  protected $opignoActivities = [];
  protected $opignoNodes = [];

  /**
   * Initializes context.
   *
   * Every scenario gets its own context instance.
   * You can also pass arbitrary arguments to the
   * context constructor through behat.yml.
   */
  public function __construct() {
  }

  /**
   * Create an opigno quiz.
   *
   * @Given there is an opigno quiz titled :title
   *
   * @param string $title
   */
  public function iCreateContentWith(string $title)
  : void {

    // Create H5P content.
    // Depending on whether the test runs via Lando
    // or CircleCI, the path will differ.
    $path = 'tests/behat/features/bootstrap/TestContent/json/opigno.quiz.json';
    if (file_exists('/app/' . $path)) {
      $json_content = file_get_contents('/app/' . $path);
    }
    else {
      $json_content = file_get_contents('/home/circleci/project/' . $path);
    }

    $fields = [
      'library_id' => '62',
      'title' => $title,
      'parameters' => $json_content,
      'filtered_parameters' => $json_content,
      'disabled_features' => 0,
      'authors' => '[]',
      'changes' => '[]',
      'license' => 'U',
    ];

    $h5p_content = H5PContent::create($fields);
    $h5p_content->save();
    $this->h5pContent[] = [
      'id' => $h5p_content->id(),
      'name' => $title,
    ];

    // Create Opigno h5p.
    $new_activity = OpignoActivity::create([
      'name' => $title,
      'type' => 'opigno_h5p',
      'uid' => 1,
      'opigno_h5p' => $h5p_content->id(),
    ]);

    // @todo remove error supression on this. Notice from Opigno causes false failure.
    @$new_activity->save();

    $this->opignoActivities[] = [
      'id' => $new_activity->id(),
      'name' => $title,
      'type' => 'quiz'
    ];

  }

  /**
   * Deletes entities created during the scenario.
   *
   * @afterScenario
   */
  public function tearDown() {
    // Delete all generate h5p content.
    foreach ($this->h5pContent as $h5p) {
      H5PContent::load($h5p['id'])->delete();
    }

    // Delete all Opigno activities.
    foreach ($this->opignoActivities as $activity) {
      OpignoActivity::load($activity['id'])->delete();
    }

    // Delete all Opigno nodes.
    foreach ($this->opignoNodes as $node) {
      Node::load($node['id'])->delete();
    }
  }

  /**
   * @Given there is an opigno article titled :title referencing quiz :quizTitle
   */
  public function thereIsAnOpignoArticleTitledReferencingQuiz($title, $quizTitle) {
    $quiz = $this->findActivity('quiz', $quizTitle);

    // Exit with error if no quiz has been created with the title.
    if ($quiz === FALSE) {
      throw new RuntimeException("Unable to find behat created quiz with name/title: " . $quizTitle);
    }

    // Create article referencing the quiz.
    $opignoActivity = OpignoActivity::create([
      'name' => $title,
      'type' => 'atdove_article',
      'uid' => 1,
      'field_opigno_quiz' => $quiz->id(),
      'field_related_quiz' => 1,
    ]);

    // @todo Determine how to save the activity without bypassing a weird notice.
    @$opignoActivity->save();

    // Stash the opigno activity for later cleanup.
    $this->opignoActivities[] = [
      'id' => $opignoActivity->id(),
      'name' => $title,
      'type' => 'article'
    ];

  }

  /**
   * @Given there is an accredited opigno article titled :title referencing quiz :quizTitle
   */
  public function thereIsAnAccreditedArticleTitledReferencingQuiz($title, $quizTitle) {
    $quiz = $this->findActivity('quiz', $quizTitle);

    // Exit with error if no quiz has been created with the title.
    if ($quiz === FALSE) {
      throw new RuntimeException("Unable to find behat created quiz with name/title: " . $quizTitle);
    }

    $acc_paragraph = paragraph::create([
      'type' => 'p_accreditation_info',
      'field_p_accreditations' => [
        'target_id' => '1',
      ],
      'field_p_accreditation_id' => [
        "value" => '441-441',
      ],
    ]);
    $acc_paragraph->save();

    // Create article referencing the quiz.
    $opignoActivity = OpignoActivity::create([
      'name' => $title,
      'type' => 'atdove_article',
      'uid' => 1,
      'field_opigno_quiz' => $quiz->id(),
      'field_related_quiz' => 1,
      'field_credit_hours' => 1,
      'field_ce_matter_category' => 'Scientific Program',
      'field_accreditation_info' => [
        [
          'target_id' => $acc_paragraph->id(),
          'target_revision_id' => $acc_paragraph->getRevisionId(),
        ],
      ]
    ]);

    // @todo Determine how to save the activity without bypassing a weird notice.
    @$opignoActivity->save();

    // Stash the opigno activity for later cleanup.
    $this->opignoActivities[] = [
      'id' => $opignoActivity->id(),
      'name' => $title,
      'type' => 'article'
    ];

  }

  /**
   * @When I load the opigno article with title :title
   */
  public function iLoadTheOpignoArticleWithTitle($title)
  {
    $quiz = $this->findActivity('article', $title);

    // Exit with error if no quiz has been created with the title.
    if ($quiz === FALSE) {
      throw new RuntimeException("Unable to find behat created article with name/title: " . $title);
    }
    $this->getSession()->visit(
      $this->locatePath($quiz->toUrl()->toString())
    );
  }

  /**
   * Helper function for finding an activity.
   *
   * @param string $type
   *   The opigno article type/bundle to look for.
   * @param string $title
   *   The title that you wish to search for.
   *
   * @return \Drupal\Core\Entity\EntityBase|\Drupal\Core\Entity\EntityInterface|false
   *  The loaded article with title, or FALSE if no article created in behat.
   */
  protected function findActivity($type, $title) {
    $article = FALSE;

    foreach ($this->opignoActivities as $activity) {
      if ($activity['type'] == $type && $activity['name'] == $title) {
        $article = OpignoActivity::load(($activity['id']));
        break;
      }
    }

    return $article;
  }

  /**
   * @Given I assigned the user :username the article with title :articleTitle
   * @Given the user :username is assigned the article with title :articleTitle
   */
  public function iAssignedTheUserTheArticleWithTitle($username, $articleTitle) {
    $user = user_load_by_name($username);

    if ($user === FALSE) {
      throw new RuntimeException("Unable to load the user with name: " . $username);
    }

    $quiz = $this->findActivity('article', $articleTitle);

    // Exit with error if no quiz has been created with the title.
    if ($quiz === FALSE) {
      throw new RuntimeException("Unable to find behat created article with name/title: " . $articleTitle);
    }

    $node = Node::create([
      'type'        => 'assignment',
      'title'       => $articleTitle,
      'field_assignee' => [
        'target_id' => $user->id()
      ],
      'field_assigned_content' => [
        'target_id' => $quiz->id()
      ],
      'field_due_date' => [
        'value' => '2042-12-31'
      ],
      'field_certificate' => [
        'target_id' => 1
      ]
    ]);
    $node->save();

    $this->opignoNodes[] = [
      'id' => $node->id(),
      'title' => $articleTitle,
      'type' => 'assignment'
    ];
  }

  /**
   * @When I click the h5p answer :answerText
   *
   * @param string $answerText
   */
  public function clickH5pAnswer($answerText) {
    $h5pAnswers = $this->getSession()
      ->getPage()
      ->findAll('css', "li.h5p-answer")
    ;

    foreach ($h5pAnswers as $answer) {
      if(strpos($answer->getText(), $answerText) !== FALSE) {
        $answer->click();
        return;
      }
    }

    throw new RuntimeException("No h5p based answer was found with text: " . $answerText);
  }

  /**
   * @When I should have received a quiz score of :score of :max
   *
   * @param int $expected_score
   *   The expected amount of answers the user actually got right.
   * @param int $expected_max
   *   The expected maximum/total amount of questions.
   */
  public function quizScoreOf(int $expected_score, int $expected_max) {
    $score_container = $this->getSession()
      ->getPage()
      ->find('css', ".h5p-joubelui-score-numeric")
    ;

    $found_score = $score_container->find('css', 'span.h5p-joubelui-score-number-counter')->getText();
    $found_max = $score_container->find('css', 'span.h5p-joubelui-score-max')->getText();

    if ($expected_score != $found_score && $found_max != $expected_max) {
      throw new RuntimeException("Expected a quiz score of $expected_score / $expected_max but instead found $found_score / $found_max");
    }
  }

}

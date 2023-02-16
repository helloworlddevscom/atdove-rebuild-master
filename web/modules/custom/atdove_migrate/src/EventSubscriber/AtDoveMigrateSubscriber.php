<?php

namespace Drupal\atdove_migrate\EventSubscriber;

use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Drupal\migrate\Event\MigrateRollbackEvent;
use Drupal\opigno_group_manager\Entity\OpignoGroupManagedLink;
use Drupal\Core\Database\Database;
use Drupal\group\Entity\Group;
use Drupal\media\Entity\Media;
use Drupal\opigno_module\Entity\OpignoActivity;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\user\Entity\User;
use Drupal\ggroup\GroupHierarchyManager;
use Drupal\group\Entity\GroupContentInterface; 
use Drupal\ggroup\Graph\GroupGraphStorageInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\node\Entity\Node;
use Drupal\file\Entity\File;
use Drupal\h5p\Entity\H5PContent;
use Drupal\h5p\H5PDrupal\H5PDrupal;
use Drupal\opigno_module\Entity\OpignoModule;
use Drupal\group\Entity\GroupContent;
use Drupal\opigno_group_manager\Entity\OpignoGroupManagedContent;
use Drupal\Core\File\FileSystemInterface;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_store\Entity\Store;

/**
 * Creates relationships between modules into group after migrations.
 */
class AtDoveMigrateSubscriber implements EventSubscriberInterface {

  /**
   * GroupsMigrateSubscriber constructor.
   */
  public function __construct() {}
  /**
   * Executes on post row save.
   *
   * @param \Drupal\migrate\Event\MigratePostRowSaveEvent $event
   *   The migrate post row save event.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function onPostRowSave(MigratePostRowSaveEvent $event) {
    $d8_connection = \Drupal::database();
    $connection = Database::getConnection('default', 'migrate');
    $migration_id = $event->getMigration()->id();
    $nid = $event->getRow()->getSourceProperty('nid');
    $uid = $event->getRow()->getSourceProperty('uid');
    if ($migration_id == 'atdove_org_groups') {
      //Array of group ids of the organizations this group belongs to  
      $gids = $event->getRow()->getSourceProperty('gids'); //We set this in the source plugin!
      $gid = $gids[0];

      //This is the the id of the this group
      $nid = $event->getRow()->getSourceProperty('nid');
      $group = Group::load($nid);
      foreach ($gids as $gid) {
        // Add newly created training plan to organization as a subgroup
        $group_org = Group::load($gid);
        if ($group_org != NULL) {
          $group_org->addContent($group, 'subgroup:organizational_groups');
          $group_org->save();
        }
          
      }
    }

    if ($migration_id == 'atdove_groups') {

      $group_id = $nid;
      $group = \Drupal\group\Entity\Group::load($group_id);

      $clinic_image = $event->getRow()->getSourceProperty('field_clinic_logo');

      if(!empty($clinic_image['filename'])) {
        $c_file_data = file_get_contents($clinic_image['source_path']);
        $fileRepository = \Drupal::service('file.repository');
        $c_file = $fileRepository->writeData($c_file_data, 'public://' . $clinic_image['title'], FileSystemInterface::EXISTS_REPLACE);
        $c_fid = $c_file->id();
      }
      $c_image_created[] = [
        'target_id' => $c_fid,
      ];

      // Look up organization's license

      $query = $connection->select('commerce_license', 'cl');
      $query->join('og_membership', 'ogm', 'ogm.etid = cl.uid');
      $query->join('commerce_product', 'cp', 'cp.product_id = cl.product_id');
      $query->join('field_data_field_additional_seats', 'fas', 'fas.entity_id = cp.product_id');
      $query->join('taxonomy_term_data', 'ttd', 'ttd.tid = fas.field_additional_seats_tid');
      $query->join('field_data_field_total_seats', 'fts', 'fts.entity_id = ttd.tid');
      $query->join('users_roles','r','r.uid=ogm.etid and r.rid=10');
      $group_q = $query->orConditionGroup()
        ->condition('ogm.gid', $group_id);
      $query->fields('cl', [
                'product_id',
                'status',
                'expires',])
            ->fields('fts', [
                'field_total_seats_value',
            ]);
      $group_status = $query->condition($group_q)
          ->execute()
          ->fetchAll();

      // Default to inactive.
      $active_status = 'inactive';
      $product_id = NULL;
      $expiry_date = NULL;
      $member_limit = NULL;
      foreach ($group_status as $group_lic) {
        // If expired, set to inactive.
        if ($group_lic->status != '2') {
          $active_status = 'inactive';
          $product_id = $group_lic->product_id;
          $expiry_date = $group_lic->expires;
          $expiry_date = date('Y-m-d\TH:i:s', $expiry_date);
          $member_limit = $group_lic->field_total_seats_value;
        }
        // If active, break out of the loop.
        else {
          $active_status = 'active';
          $product_id = $group_lic->product_id;
          $expiry_date = $group_lic->expires;
          $expiry_date = date('Y-m-d\TH:i:s', $expiry_date);
          $member_limit = $group_lic->field_total_seats_value;
          break;
        }
      }
      $group->set('field_clinic_logo', $c_image_created);
      $group->set('field_license_status', $active_status);
      $group->set('field_current_expiration_date', $expiry_date);
      $group->set('field_current_product_id', $product_id);
      $group->set('field_member_limit', $member_limit);
      $group->save();

    }

    if ($migration_id == 'atdove_article_opigno_activity' || $migration_id == 'atdove_node_blog') {
      //Array of group ids of the organizations this group belongs to  
      $accreditation_info_fields = $event->getRow()->getSourceProperty('field_accreditation_info_new');
      //Create new paragraph, fill it with the old field collection values
      if ($migration_id == 'atdove_node_blog') {
        $node = \Drupal::entityTypeManager()
          ->getStorage('node')
          ->load($nid);
          $body = 'body';
      }
      elseif ($migration_id == 'atdove_article_opigno_activity') {
        $node = OpignoActivity::load($nid);
        $body =  'field_article_body';
      }

      $node_uid = '1';
      // Get user id of node
      $node_uid = $connection->select('node', 'n')
        ->fields('n', ['uid'])
        ->condition('nid', $nid)
        ->execute()
        ->fetchField();

      //Get summary field due to a migration bug
      $body_summary = $connection->select('field_data_body', 'fdb')
        ->fields('fdb', ['body_summary'])
        ->condition('entity_id', $nid)
        ->execute()
        ->fetchField();
        $body_summary = strip_tags($body_summary);
        $body_summary= str_replace("&nbsp;"," ",$body_summary);
        $body_summary= str_replace("<p>","",$body_summary);
        $body_summary= str_replace("</p>","",$body_summary);

      $node->set('uid', $node_uid);

      $paragraph = paragraph::create([
        'type' => 'p_accreditation_info',
        'field_p_accreditations' => [
          'target_id' => $accreditation_info_fields['accreditation'],
        ],
        'field_p_accreditation_id' => [
          "value" => $accreditation_info_fields['accreditation_id'],
        ],
      ]);
      $paragraph->save();
      $node->field_accreditation_info = [
        [
          'target_id' => $paragraph->id(),
          'target_revision_id' => $paragraph->getRevisionId(),
        ],
      ];

      // Now add images. First we create the media entity, then add to the node.
      $images = $event->getRow()->getSourceProperty('images');
      $article_body = $event->getRow()->getSourceProperty('body');
      $html = $article_body[0]['value'];
      if (empty($images)) {
        //because client deleted images for some reason
        $article_body = $event->getRow()->getSourceProperty('body');
        $dom = new \DOMDocument();
        $dom->loadHTML($html);
        foreach ($dom->getElementsByTagName('img') as $i => $img) {
          $src = $img->getAttribute('src');
            $title = substr($src, strrpos($src, '/' )+1);
            $path = pathinfo($src);
            $images[$i] = [
              'alt' => '',
              'title' => $title,
              'source_path' => 'https://www.atdove.org' . $img->getAttribute('src'),
              'dir' => ltrim($path['dirname'], '/'),
            ];
        }
      }

      $images_created = [];
      $file_system = \Drupal::service('file_system');

      if (is_array($images)) {
        foreach ($images as $key => $image) {
          $new_dir = str_replace('sites/atdove.org/files/', '', $image['dir']);
          $file_data = file_get_contents($image['source_path']);
          $insert_create_dir = 'public://' . $new_dir . '/';
          $file_system->prepareDirectory($insert_create_dir, FileSystemInterface::CREATE_DIRECTORY);
          $fileRepository = \Drupal::service('file.repository');
          $file = $fileRepository->writeData($file_data, 'public://' . $new_dir . '/' . $image['title'], FileSystemInterface::EXISTS_REPLACE);
          $media = Media::create([
            'bundle' => 'image',
            'uid' => \Drupal::currentUser()->id(),
            'name' => $image['title'],
            'field_media_image' => [
              'target_id' => $file->id(),
            ],
          ]);
          $media->setPublished(TRUE)->save();
    
          $images_created[] = [
            'target_id' => $media->id(),
          ];
        }
      }

      $new_html = str_replace('/images/', '/sites/default/files/images/', $html);
      $new_html = str_replace('/sites/atdove.org/files/', '/sites/default/files/', $new_html);
      $node->set($body, $new_html);
      if ($migration_id == 'atdove_node_blog') {
        $node->body->format = 'full_html'; 
      }
      elseif ($migration_id == 'atdove_article_opigno_activity') {
        $node->field_article_body->format = 'full_html';
      }    

      $node->field_media_image->setValue($images_created);

      // Main article image field.
      $article_image = $event->getRow()->getSourceProperty('article_image');
      $a_file_data = file_get_contents($article_image['source_path']);
      $create_dir = 'public://' . $article_image['dir'] . '/';
      $file_system->prepareDirectory($create_dir, FileSystemInterface::CREATE_DIRECTORY);
      $fileRepository = \Drupal::service('file.repository');
      $a_file = $fileRepository->writeData($a_file_data, 'public://' . $article_image['dir'] . '/' . $article_image['title'], FileSystemInterface::EXISTS_REPLACE);
      $a_media = Media::create([
        'bundle' => 'image',
        'uid' => \Drupal::currentUser()->id(),
        'name' => $article_image['title'],
        'field_media_image' => [
          'target_id' => $a_file->id(),
        ],
      ]);
      $a_media->setPublished(TRUE)->save();

      $a_image_created = [
        'target_id' => $a_media->id(),
      ];

      if ($migration_id == 'atdove_article_opigno_activity') {
          $node->field_article_image->setValue($a_image_created);
          $node->field_article_body->summary = $body_summary;
      }
      if ($migration_id == 'atdove_node_blog') {
         $node->field_blog_image->setValue($a_image_created);
         $node->body->summary = $body_summary;
      }
        
      $node->save();
    }


    if ($migration_id == 'atdove_node_guides') {
      //This is the the nid of the this article
      $nid = $event->getRow()->getSourceProperty('nid');
      $node = \Drupal::entityTypeManager()
        ->getStorage('node')
        ->load($nid);
      
      // Now add images. First we create the media entity, then add to the node.
      $images = $event->getRow()->getSourceProperty('images');
      $images_created = [];
      foreach ($images as $key => $image) {
        $file_data = file_get_contents($image['source_path']);
        $fileRepository = \Drupal::service('file.repository');
        $file = $fileRepository->writeData($file_data, 'public://guides/' . $image['title'], FileSystemInterface::EXISTS_REPLACE);
        $media = Media::create([
          'bundle' => 'image',
          'uid' => \Drupal::currentUser()->id(),
          'name' => $image['title'],
          'field_media_image' => [
            'target_id' => $file->id(),
          ],
        ]);
        $media->setPublished(TRUE)->save();
  
        $images_created[] = [
          'target_id' => $media->id(),
        ];
      }
      $node->field_media_image->setValue($images_created);
      $node->save();

    }


    if ($migration_id == 'atdove_users') {
      ini_set('default_socket_timeout', 1);
      $uid = $event->getRow()->getSourceProperty('uid');

      //Array of group ids of the organizations this group belongs to  
      $field_org_employee_ids_fields = $event->getRow()->getSourceProperty('field_org_employee_ids_new');
      
      //Create new paragraph, fill it with the old field collection values
      $user = \Drupal::entityTypeManager()
        ->getStorage('user')
        ->load($uid);

      $paragraph = paragraph::create([
        'type' => 'p_user_org_employee_ids',
        'field_p_user_employee_id' => [
          'value' => $field_org_employee_ids_fields['field_employee_id'],
        ],
        'field_p_user_org_id_for_employee' => [
          "value" => $field_org_employee_ids_fields['field_org_id_for_employee_id'],
        ],
      ]);
      $paragraph->save();

      $user->set('field_user_org_employee_ids', [
        'target_id' => $paragraph->id(),
        'target_revision_id' => $paragraph->getRevisionId(),
      ]);
 
      // Main user image field.
      $user_image = $event->getRow()->getSourceProperty('user_image');

      $picture_field = $event->getRow()->getSourceProperty('picture');

      if($user_image['file_name'] !== '') {
        $url = $user_image['source_path'];
        $file_data = file_get_contents($url);
        $fileRepository = \Drupal::service('file.repository');
        $file = $fileRepository->writeData($file_data, 'public://pictures/' . $user_image['file_name'], FileSystemInterface::EXISTS_REPLACE);
        $u_fid = $file->id();
        $user->user_picture->setValue($u_fid);    

       }
      $user->save();
    }

    if ($migration_id == 'atdove_video_opigno_activity') {

      $node = OpignoActivity::load($nid);

      // Get user id of node
      $node_uid = $connection->select('node', 'n')
          ->fields('n', ['uid'])
          ->condition('nid', $nid)
          ->execute()
          ->fetchField();

      $node->set('uid', $node_uid);
      
      // Now add images. First we create the media entity, then add to the node.
      $images = $event->getRow()->getSourceProperty('images');
  
      $images_created = [];
      foreach ($images as $key => $image) {
        if(!empty($image['file_name'])) {
          $file_data = file_get_contents($image['source_path']);
          $fileRepository = \Drupal::service('file.repository');
          $file = $fileRepository->writeData($file_data, 'public://' . $image['title'], FileSystemInterface::EXISTS_REPLACE);
          $media = Media::create([
            'bundle' => 'image',
            'uid' => \Drupal::currentUser()->id(),
            'name' => $image['title'],
            'field_media_image' => [
              'target_id' => $file->id(),
            ],
          ]);
          $media->setPublished(TRUE)->save();
    
          $images_created[] = [
            'target_id' => $media->id(),
          ];
        }
      }
      $node->field_media_image->setValue($images_created);
      // Video thumbnail image field.
      $thumbnail_image = $event->getRow()->getSourceProperty('thumbnail_image');
      if(!empty($thumbnail_image['file_name'])) {
        $a_file_data = file_get_contents($thumbnail_image['source_path']);
        $fileRepository = \Drupal::service('file.repository');
        $a_file = $fileRepository->writeData($a_file_data, 'public://' . $thumbnail_image['title'], FileSystemInterface::EXISTS_REPLACE);
        $a_media = Media::create([
          'bundle' => 'image',
          'uid' => \Drupal::currentUser()->id(),
          'name' => $thumbnail_image['title'],
          'field_media_image' => [
            'target_id' => $a_file->id(),
          ],
        ]);
        $a_media->setPublished(TRUE)->save();

        $a_image_created[] = [
          'target_id' => $a_media->id(),
        ];
        $node->field_thumbnail_image->setValue($a_image_created);
      }

      // Video featured image field.
      $f_image = $event->getRow()->getSourceProperty('featured_image');
      if(!empty($f_image['file_name'])) {
        $f_file_data = file_get_contents($f_image['source_path']);
        $fileRepository = \Drupal::service('file.repository');
        $f_file = $fileRepository->writeData($f_file_data, 'public://' . $f_image['title'], FileSystemInterface::EXISTS_REPLACE);
        $f_media = Media::create([
          'bundle' => 'image',
          'uid' => \Drupal::currentUser()->id(),
          'name' => $f_image['title'],
          'field_media_image' => [
            'target_id' => $f_file->id(),
          ],
        ]);
        $f_media->setPublished(TRUE)->save();

        $f_image_created[] = [
          'target_id' => $f_media->id(),
        ];
        $node->field_featured_image->setValue($f_image_created);
      } 
      $node->save();
    }

    if ($migration_id == 'atdove_quiz_opigno_activity') {

      $introduction = trim(strip_tags($event->getRow()->getSourceProperty('body')[0]['value']));
      $introduction = trim(preg_replace('/\n/', ' ', $introduction));
      $introduction = trim(preg_replace('/\t/', ' ', $introduction));
      $introduction = trim(preg_replace('/\r/', ' ', $introduction));
      $introduction = addcslashes($introduction,'"');
      // $node = \Drupal::entityTypeManager()
      //   ->getStorage('node')
      //   ->load($nid);
    
      $connection = Database::getConnection('default', 'migrate');
      $quiz_id = $nid;
      $questions = [];

      // Get user id of node
      $node_uid = $connection->select('node', 'n')
          ->fields('n', ['uid'])
          ->condition('nid', $quiz_id)
          ->execute()
          ->fetchField();

      // $node->set('uid', $node_uid);

      //Quiz Title
      $query_quiz_title = $connection->select('node', 'n')
          ->fields('n', ['title'])
          ->condition('vid', $quiz_id)
          ->execute()
          ->fetchField();

      $current_q_vid = $connection->select('node', 'n')
          ->fields('n', ['vid'])
          ->condition('nid', $quiz_id)
          ->execute()
          ->fetchField();

      // Grab Related Questions
      $query_q_ids = $connection->select('quiz_node_relationship', 'qnr')
        ->fields('qnr', ['child_nid', 'child_vid'])
        ->condition('parent_nid', $quiz_id)
        ->condition('parent_vid', $current_q_vid)
        ->execute()
        ->fetchAll();

      // Get pass rate of quiz node
      $quiz_passrate = $connection->select('quiz_node_properties', 'qnp')
          ->fields('qnp', ['pass_rate'])
          ->condition('nid', $quiz_id)
          ->execute()
          ->fetchField();

      foreach ($query_q_ids as $query_q_id) {
        // Grab Related answers
        $query_a = $connection->select('quiz_multichoice_answers', 'qma')
          ->fields('qma', ['answer', 'score_if_chosen'])
          ->condition('question_nid', $query_q_id->child_nid)
          ->condition('question_vid', $query_q_id->child_vid)
          ->execute()
          ->fetchAll();
        $query_q_title = $connection->select('field_data_body', 'fb')
          ->fields('fb', ['body_value'])
          ->condition('entity_id', $query_q_id->child_nid)
          ->execute()
          ->fetchField();
        $questions[$query_q_id->child_nid][] = $query_q_title;
        $questions[$query_q_id->child_nid][] = $query_a;
      }
      $json_questions_string = [];
      foreach ($questions as $key => $question) {
      $answers = $question[1];
      $answers_array = [];
      foreach ($answers as $answer) {
        $stripped_answer = trim(strip_tags($answer->answer));
        $stripped_answer = trim(preg_replace('/\t/', ' ', $stripped_answer));
        $stripped_answer = trim(preg_replace('/\r/', ' ', $stripped_answer));
        $stripped_answer = addcslashes($stripped_answer,'"');
        if ($answer->score_if_chosen == 1) {
          $correct = 'true';
        }
        else {
          $correct = 'false';
        }
        $answers_array[] = '{"correct":' . $correct . ',"tipsAndFeedback":{"tip":"","chosenFeedback":"","notChosenFeedback":""},"text":"<div>' . $stripped_answer . '<\/div>\n"}';
      }
      $stripped_q = trim(strip_tags($question[0]));
      $stripped_q = trim(preg_replace('/\t/', ' ', $stripped_q));
      $stripped_q = trim(preg_replace('/\r/', ' ', $stripped_q));
      $stripped_q = addcslashes($stripped_q,'"');

      $json_answers_string = implode(',', $answers_array);
      $json_questions_string[] =  '{"params":{"media":{"disableImageZooming":false},"answers":[' . $json_answers_string . '],"overallFeedback":[{"from":0,"to":100}],"behaviour":{"enableRetry":true,"enableSolutionsButton":false,"enableCheckButton":false,"type":"auto","singlePoint":false,"randomAnswers":true,"showSolutionsRequiresInput":true,"confirmCheckDialog":false,"confirmRetryDialog":false,"autoCheck":false,"passPercentage":' . $quiz_passrate . ',"showScorePoints":true},"UI":{"checkAnswerButton":"Check","showSolutionButton":"Show solution","tryAgainButton":"Retry","tipsLabel":"Show tip","scoreBarLabel":"You got :num out of :total points","tipAvailable":"Tip available","feedbackAvailable":"Feedback available","readFeedback":"Read feedback","wrongAnswer":"Wrong answer","correctAnswer":"Correct answer","shouldCheck":"Should have been checked","shouldNotCheck":"Should not have been checked","noInput":"Please answer before viewing the solution"},"confirmCheck":{"header":"Finish ?","body":"Are you sure you wish to finish ?","cancelLabel":"Cancel","confirmLabel":"Finish"},"confirmRetry":{"header":"Retry ?","body":"Are you sure you wish to retry ?","cancelLabel":"Cancel","confirmLabel":"Confirm"},"question":"<p>'. $stripped_q . '<\/p>\n"},"library":"H5P.MultiChoice 1.14","metadata":{"contentType":"Multiple Choice","license":"U","title":"' . $stripped_q . '"},"subContentId":""}';

      }
      $json_string = implode(',', $json_questions_string);

      // Load Activity
      $new_activity = OpignoActivity::load($nid);
      $title = 'Quiz - ' . $event->getRow()->getSourceProperty('title');
      $j_title = addcslashes($title,'"');
      $j_title = trim(preg_replace('/\t/', ' ', $j_title));

      $json_content = '{"introPage":{"showIntroPage":false,"startButtonText":"Start Quiz","introduction":"' . $introduction . '","title":"' . $j_title . '"},"progressType":"dots","passPercentage":' . $quiz_passrate . ',"questions":[' . $json_string . '],"disableBackwardsNavigation":false,"randomQuestions":false,"endGame":{"showResultPage":true,"showSolutionButton":true,"showRetryButton":true,"noResultMessage":"Finished","message":"Your result:","overallFeedback":[{"from":0,"to":100}],"solutionButtonText":"Show solution","retryButtonText":"Retry","finishButtonText":"Finish","showAnimations":false,"skippable":false,"skipButtonText":"Skip video"},"override":{"checkButton":true},"texts":{"prevButton":"Previous question","nextButton":"Next question","finishButton":"Finish","textualProgress":"Question: @current of @total questions","jumpToQuestion":"Question %d of %total","questionLabel":"Question","readSpeakerProgress":"Question @current of @total","unansweredText":"Unanswered","answeredText":"Answered","currentQuestionText":"Current question"}}';

      if ($uid !== NULL) {
       $new_activity->set('uid', $uid); 
      }
      else {
        $new_activity->set('uid', '1'); 
      }


      $nid_result = $d8_connection->select('h5p_content', 'h5p')
      ->fields('h5p')
      ->condition('id', $nid,'=')
      ->execute()
      ->fetchAssoc();

      @json_decode($json_content, TRUE);
      $is_valid = json_last_error();
      if (json_last_error() === JSON_ERROR_NONE) {
        if($nid_result) {
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
        }
        else {
          $fields = [
            'id' => $nid,
            'library_id' => '62',
            'title' => $title,
            'parameters' => $json_content,
            'filtered_parameters' => $json_content,
            'disabled_features' => 0,
            'authors' => '[]',
            'changes' => '[]',
            'license' => 'U',
          ];      
        }

        $h5p_content = H5PContent::create($fields);
        $h5p_content->save();
        $new_activity->set('opigno_h5p', $h5p_content->id());
        $new_activity->set('name', $title);
        $new_activity->save();

      }
      else {
        print_r("JSON COULD NOT BE VALIDATED.");
        print_r($json_content);
      }    
    }

    if ($migration_id == 'atdove_learning_paths') {
      $activities = [];
      $connection = Database::getConnection('default', 'migrate');
      $nid = $event->getRow()->getSourceProperty('nid');

      // Create a module with the name "Title Module" for this training plan
      $title = $event->getRow()->getSourceProperty('title');
      $field_related_videos = $event->getRow()->getSourceProperty('field_related_videos');
      $field_related_articles = $event->getRow()->getSourceProperty('field_related_articles');

      // Get all activities that should go in this module
      foreach ($field_related_videos as  $video) {
        
         $video_quiz_query = $connection->select('field_data_field_related_quiz', 'frq')
          ->fields('frq', ['field_related_quiz_target_id'])
          ->condition('entity_id', $video['target_id'])
          ->execute()
          ->fetchField();

          $activities[] = $video['target_id'];
          $activities[] = $video_quiz_query;
                //Save first article id to grab thumbnail
        if (array_key_first($field_related_videos) == $key) {
         $tp_image_backup_vid = $video['target_id'];
        }
      }

      foreach ($field_related_articles as $key => $article) {
        
         $article_quiz_query = $connection->select('field_data_field_related_quiz', 'frqq')
          ->fields('frqq', ['field_related_quiz_target_id'])
          ->condition('entity_id', $article['target_id'])
          ->execute()
          ->fetchField();
          $activities[] = $article['target_id'];
          $activities[] = $article_quiz_query;
        
      
          //Save first article id to grab thumbnail
        if (array_key_first($field_related_articles) == $key) {
         $tp_image_backup = $article['target_id'];
        }
      }

      $group = \Drupal\group\Entity\Group::load($nid);
      $entity_type_manager = \Drupal::entityTypeManager();

      // Create module.
      $module = \Drupal::entityTypeManager()->getStorage('opigno_module')->load($nid);
      if ($module == NULL) {
          $module = $entity_type_manager->getStorage('opigno_module')->create([
          'name' => $title,
          'id' => $nid,
        ]);
        $module->save();

        $activities = OpignoActivity::loadMultiple($activities);

        // Add activities to a module.
        $opigno_module_controller = \Drupal::service('opigno_module.opigno_module');
        $opigno_module_controller->activitiesToModule($activities, $module);

      }

      // Add module as group content to Training Plan
      $group_content = OpignoGroupManagedContent::createWithValues($group->id(),'ContentTypeModule',$module->id(),0,1);
      $group_content->save();

      $content_types_manager = \Drupal::service('opigno_group_manager.content_types.manager');
      $plugin_definition = $content_types_manager->getDefinition('ContentTypeModule');
      $added_entity = \Drupal::entityTypeManager()
        ->getStorage($plugin_definition['entity_type'])
        ->load($module->id());

      $group->addContent($added_entity, $plugin_definition['group_content_plugin_id']);

      //Add New Training plan to it's Organization
      $gids = $event->getRow()->getSourceProperty('field_organization');
      $group->set('field_learning_path_visibility', 'public');
      $group->set('field_learning_path_published', TRUE);

      // Add training plan image
      $image = $event->getRow()->getSourceProperty('tp_image');

      if (!empty($image)) {

        $file_data = file_get_contents($image['source_path']);
        $fileRepository = \Drupal::service('file.repository');
        $file = $fileRepository->writeData($file_data, 'public://guides/' . $image['title'], FileSystemInterface::EXISTS_REPLACE);
        $media = Media::create([
          'bundle' => 'image',
          'uid' => \Drupal::currentUser()->id(),
          'name' => $image['title'],
          'field_media_image' => [
            'target_id' => $file->id(),
          ],
        ]);
        $media->setPublished(TRUE)->save();
      
        $image_created = [
          'target_id' => $media->id(),
        ];
        $group->set('field_learning_path_media_image', $image_created);

      }
      else {

        if (!empty($tp_image_backup_vid)) {
          //$vid_node = Node::load($tp_image_backup_vid);
          $vid_node = OpignoActivity::load($tp_image_backup_vid);
          if ($vid_node && isset($vid_node->field_thumbnail_image) && $vid_node->field_thumbnail_image->entity != NULL) {
            $img_id = $vid_node->field_thumbnail_image->entity->id();
            $image_ref = [
              'target_id' => $img_id,
            ];
           $group->set('field_learning_path_media_image', $image_ref);
          }

        }
        elseif (!empty($tp_image_backup)) {
          // If there's no training plan thumbnail, grab the one from the first article
          // Look up image in article
          //$art_node = Node::load($tp_image_backup);
          $art_node = OpignoActivity::load($tp_image_backup);
          if ($art_node && isset($art_node->field_article_image)) {
            $img_id = $art_node->field_article_image->entity->id();
            $image_ref = [
              'target_id' => $img_id,
            ];
            $group->set('field_learning_path_media_image', $image_ref);
     
          }
        }
      }

      $group->save();

      foreach ($gids as $org_id) {

        $id = strval($org_id['target_id']);
     
        // Add newly created training plan to organization as a subgroup.
        $group_org = \Drupal\group\Entity\Group::load($id);
        if ($group_org)  {
          $group_org->addContent($group, 'subgroup:learning_path');
          $group_org->save();          
        }
      }
    }

    if ($migration_id == 'atdove_assignments') {

      $field_assigned_content = $event->getRow()->getSourceProperty('field_assigned_content');

      $quiz_id = $connection->select('field_data_field_related_quiz', 'frqq')
        ->fields('frqq', ['field_related_quiz_target_id'])
        ->condition('entity_id', $field_assigned_content[0]['target_id'])
        ->execute()
        ->fetchField();

      $pass_rate = $connection->select('quiz_node_properties', 'qnpr')
        ->fields('qnpr', ['pass_rate'])
        ->condition('nid', $quiz_id)
        ->execute()
        ->fetchField();

      // Get user's quiz results.
      $field_assignee = $event->getRow()->getSourceProperty('new_field_assignee');
      $query_quiz_result = $connection->select('quiz_node_results', 'qnres')
        ->fields('qnres', ['score'])
        ->condition('nid', $quiz_id)
        ->condition('uid', $field_assignee)
        ->execute()
        ->fetchAll();

      foreach ($query_quiz_result as $result_id) {
        $results[] = $result_id->score;
      }

      $status = '';
      if (!empty($results)) {
        $highest_score = max($results);
        if ($highest_score >= $pass_rate) {
          $status = 'passed';
        }
        else {
          $status = 'failed';
        }
      }

      //$field_completed = $event->getRow()->getSourceProperty('field_completed');

      // Set status.
      $asg_node = Node::load($nid);
      $asg_node->set('field_assignment_status', $status);
      $asg_node->field_certificate->setValue('1');
      //$asg_node->field_completed->setValue(date('Y-m-d\TH:i:s',$field_completed));
      $asg_node->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      //MigrateEvents::POST_IMPORT => 'onPostImport',
      //MigrateEvents::PRE_IMPORT => 'onPreImport',   
      MigrateEvents::POST_ROW_SAVE => 'onPostRowSave',
    ];
  }

}
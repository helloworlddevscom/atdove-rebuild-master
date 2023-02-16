<?php

/**
 * @file
 * FreeTrialModalController class.
 */

namespace Drupal\atdove_opigno\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Controller\ControllerBase;

class FreeTrialModalController extends ControllerBase {

  public function modal_person() {
    $options = [
      'dialogClass' => 'popup-dialog-class',
      'width' => '50%',
    ];
    $response = new AjaxResponse();
    $response->addCommand(new OpenModalDialogCommand(t('Free Trial'), t('You can assign procedural shorts, 
    	CE lectures, or medical articles to a single team member, group or multiple groups. 
    	But you’ll need to sign up for a <a href="@freetrial">Free Trial</a> to do that!', array('@freetrial' => '/join')), $options));
    
    return $response;
  }
  public function modal_tp() {
    $options = [
      'dialogClass' => 'popup-dialog-class',
      'width' => '50%',
    ];
    $response = new AjaxResponse();
    $response->addCommand(new OpenModalDialogCommand(t('Free Trial'), t('Training plans allow you to organize 
    	assignments for your organization however it requires a premium account. Sign up for the <a href="@freetrial">Free Trial</a>.', array('@freetrial' => '/join')), $options));
    
    return $response;
  }
}
<?php

require_once realpath(LIB_DIR.'/Module.php');

class ErrorModule extends Module {
  protected $id = 'error';

  private $errors = array(
    'data' => array(
      'status'  => '504 Gateway Timeout',
      'message' => 'We are sorry the server is currently experiencing errors. Please try again later.',
    ),
    'internal' => array(
      'status'  => '500 Internal Server Error',
      'message' => 'Internal server error',
    ),
    'notfound' => array(
      'status'  => '404 Not Found',
      'message' => 'Page not found',
    ),
    'forbidden' => array(
      'status'  => '403 Forbidden',
      'message' => 'Not authorized to view this page',
    ),
    'device_notsupported' => array(
      'status'  => null,
      'message' => 'This functionality is not supported on this device',
    ),
    'disabled'  => array(
      'message' =>  'This module has been disabled'
    ),
    'protected' => array(
      'message' =>  'This module requires you to login',
      'linkText' => 'Click here to login'
    ),
    'default' => array(
      'status'  => '500 Internal Server Error',
      'message' => 'Unknown error',
    )
  );

  protected function initializeForPage() {
    $code = $this->getArg('code', 'default');
    $url  = $this->getArg('url', '');
    
    $error = isset($this->errors[$code]) ? 
      $this->errors[$code] : $this->errors['default'];;
    
    if (isset($error['status'])) {
      header('Status: '.$error['status']);
    }

    if (isset($error['linkText'])) {
        $this->assign('linkText', $error['linkText']);
    }
    
    $this->assign('navImageID', 'about');
    $this->assign('message', $error['message']);
    $this->assign('url', $url);
  }
}
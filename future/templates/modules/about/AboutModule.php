<?php
/**
  * @package Module
  * @subpackage About
  */

/**
  * @package Module
  * @subpackage About
  */
class AboutModule extends Module {
  protected $id = 'about';
  
  private function getPhraseForDevice() {
    switch($this->platform) {
      case 'iphone':
        return 'iPhone';
        
      case 'android':
        return 'Android phones';
        
      default:
        switch ($this->pagetype) {
          case 'compliant':
            return 'touchscreen phones';
          
          case 'basic':
          default:
            return 'non-touchscreen phones';
        }
    }
  }
  
  protected function initializeForPage() {
    switch ($this->page) {
      case 'index':
        $this->loadWebAppConfigFile('about-index', 'aboutPages');
        break;
        
      case 'about_site':
        $this->assign('devicePhrase', $this->getPhraseForDevice()); // TODO: this should be more generic, not part of this module
        break;
      
      case 'about':
        break;
      
      case 'pane':
        break;

      default:
        $this->redirectTo('index');
        break;
    }
  }
}

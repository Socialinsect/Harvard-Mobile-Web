<?php

require_once realpath(LIB_DIR.'/Module.php');

class LinksModule extends Module {
  protected $id = 'links';
  
  protected function initializeForPage() {
    $links = $this->loadWebAppConfigFile('links-index', 'links');
    
    $springboard = isset($links['springboard']) && $links['springboard'];
    $description = self::argVal($links, 'description', null);    
    
    foreach ($links as $index => $link) {
      if (!is_array($link)) {
        unset($links[$index]);
      } else if (isset($link['icon'])) {
        $links[$index]['img'] = "/modules/{$this->id}/images/{$link['icon']}";
      }
    }
    
    $this->assign('springboard', $springboard);
    $this->assign('description', $description);
    $this->assign('links',       $links);
  }
}

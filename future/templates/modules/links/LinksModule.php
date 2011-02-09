<?php

require_once realpath(LIB_DIR.'/Module.php');

class LinksModule extends Module {
  protected $id = 'links';
  
  protected function initializeForPage() {
    $links = $this->loadWebAppConfigFile('links-index', 'links');
    
    $displayType = self::argVal($links, 'springboard', false) ? 'springboard' : 'list';
    $description = self::argVal($links, 'description', null);    
    
    foreach ($links as $index => $link) {
      if (!is_array($link)) {
        unset($links[$index]);
      } else if (self::argVal($link, 'icon', false)) {
        $links[$index]['img'] = "/modules/{$this->id}/images/{$link['icon']}{$this->imageExt}";
      }
    }
    
    $this->assign('displayType', $displayType);
    $this->assign('description', $description);
    $this->assign('links',       $links);
  }
}

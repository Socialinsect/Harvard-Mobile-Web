<?php

class SiteConfig extends ConfigGroup {

  private $apiVars = array();

  public function loadAPIFile($name, $section = true, $ignoreError = false) {
    if (!in_array($name, array_keys($this->apiVars))) {
      $file = realpath_exists(SITE_CONFIG_DIR."/api/$name.ini");
      if ($file) {
        $this->apiVars[$name] = parse_ini_file($file, $section);
        $this->replaceAPIVariables($this->apiVars[$name]);
        return true;

      } else {
        if (!$ignoreError) {
          error_log(__FUNCTION__."(): no api configuration file for '$name'");
        }
        return false;
      }
    }
    return true;
  }

  function __construct() {
    // Load main configuration file
    $config = ConfigFile::factory(MASTER_CONFIG_DIR."/config.ini");
    $this->addConfig($config);
    
    $siteDir  = realpath_exists($this->getVar('SITE_DIR'));
    $siteMode = $this->getVar('SITE_MODE');
    
    // Set up defines relative to SITE_DIR
    define('SITE_DIR',             $siteDir);
    define('SITE_LIB_DIR',         SITE_DIR.'/lib');
    define('DATA_DIR',             SITE_DIR.'/data');
    define('CACHE_DIR',            SITE_DIR.'/cache');
    define('LOG_DIR',              SITE_DIR.'/logs');
    define('SITE_CONFIG_DIR',      SITE_DIR.'/config');

    $config = ConfigFile::factory(SITE_CONFIG_DIR."/config.ini");
    $this->addConfig($config);

    $config = ConfigFile::factory(SITE_CONFIG_DIR."/config-$siteMode.ini");
    $this->addConfig($config);

    // Set up theme define
    define('THEME_DIR', SITE_DIR.'/themes/'.$this->getVar('ACTIVE_THEME'));
    //error_log(print_r($this->configVars, true));
  }

}

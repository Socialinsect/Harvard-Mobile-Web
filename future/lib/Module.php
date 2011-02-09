<?php
/**
  * @package Module
  */

/**
  * Breadcrumb Parameter
  */
define('MODULE_BREADCRUMB_PARAM', '_b');
define('DISABLED_MODULES_COOKIE', 'disabledmodules');
define('MODULE_ORDER_COOKIE', 'moduleorder');

abstract class Module {

  protected $id = 'none';
  protected $moduleName = '';
  
  protected $session;
  
  protected $page = 'index';
  protected $args = array();
  
  protected $pagetype = 'unknown';
  protected $platform = 'unknown';
  protected $supportsCerts = false;
  
  protected $imageExt = '.png';
  
  private $pageConfig = null;
  
  private $pageTitle           = 'No Title';
  private $breadcrumbTitle     = 'No Title';
  private $breadcrumbLongTitle = 'No Title';
  
  private $inlineCSSBlocks = array();
  private $cssURLs = array();
  private $inlineJavascriptBlocks = array();
  private $inlineJavascriptFooterBlocks = array();
  private $onOrientationChangeBlocks = array();
  private $onLoadBlocks = array('scrollTo(0,1);');
  private $javascriptURLs = array();

  private $moduleDebugStrings = array();
  
  private $breadcrumbs = array();

  private $fontsize = 'medium';
  private $fontsizes = array('small', 'medium', 'large', 'xlarge');
  
  private $templateEngine = null;
  private $siteVars = null;
  
  private $htmlPager = null;
  private $inPagedMode = true;
  
  private $tabbedView = null;
  
  protected $cacheMaxAge = 0;

  //
  // Tabbed View support
  //
  
  protected function enableTabs($tabKeys, $defaultTab=null, $javascripts=array()) {
    $currentTab = $tabKeys[0];
    if (isset($this->args['tab']) && in_array($this->args['tab'], $tabKeys)) {
      $currentTab = $this->args['tab'];
      
    } else if (isset($defaultTab) && in_array($defaultTab, $tabKeys)) {
      $currentTab = $defaultTab;
    }
    
    $tabs = array();
    foreach ($tabKeys as $tabKey) {
      $title = ucwords($tabKey);
      $configKey = "tab_{$tabKey}";
      if (isset($this->pageConfig, $this->pageConfig[$configKey]) && 
          strlen($this->pageConfig[$configKey])) {
        $title = $this->pageConfig[$configKey];
      }
      
      $tabArgs = $this->args;
      $tabArgs['tab'] = $tabKey;
      
      $tabs[$tabKey] = array(
        'title' => $title,
        'url'   => $this->buildBreadcrumbURL($this->page, $tabArgs, false),
        'javascript' => isset($javascripts[$tabKey]) ? $javascripts[$tabKey] : '',
      );
    }
    
    $this->tabbedView = array(
      'tabs'       => $tabs,
      'current'    => $currentTab,
    );

    $this->addInlineJavascriptFooter("showTab('{$currentTab}Tab');");
  }
  
  //
  // Pager support
  // Note: the first page is 0 (0 ... pageCount-1)
  //
  protected function enablePager($html, $encoding, $pageNumber) {
    $this->htmlPager = new HTMLPager($html, $encoding, $pageNumber);
  }
  
  // Override in subclass if you are using the pager
  protected function urlForPage($pageNumber) {
    return '';
  }
    
  private function getPager() {
    $pager = array(
      'pageNumber'   => $this->htmlPager->getPageNumber(),
      'pageCount'    => $this->htmlPager->getPageCount(),
      'inPagedMode'  => $this->htmlPager->getPageNumber() != HTMLPager::ALL_PAGES,
      'html' => array(
        'all'  => $this->htmlPager->getAllPagesHTML(),
        'page' => $this->htmlPager->getPageHTML(),
      ),
      'url' => array(
        'prev'  => null,
        'next'  => null,
        'all'   => $this->urlForPage(HTMLPager::ALL_PAGES),
        'pages' => array(),
      ),
    );

    for ($i = 0; $i < $pager['pageCount']; $i++) {
      $pager['url']['pages'][] = $this->urlForPage($i).
        $this->getBreadcrumbArgString('&', false);
    }
        
    if ($pager['pageNumber'] > 0) {
      $pager['url']['prev'] = $pager['url']['pages'][$pager['pageNumber']-1];
    }
    
    if ($pager['pageNumber'] < ($pager['pageCount']-1)) {
      $pager['url']['next'] = $pager['url']['pages'][$pager['pageNumber']+1];
    }
    
    return $pager;
  }
  
  //
  // Font size controls
  //
  private function getFontSizeCSS() {
    switch ($this->fontsize) {
      case 'small':
        return 'body { font-size: 89%; line-height: 1.33em }';
      case 'large':
        return 'body { font-size: 125%; line-height: 1.33em }';
      case 'xlarge':
        return 'body { font-size: 150%; line-height: 1.33em }';
      default:
        return 'body { font-size: 100%; line-height: 1.33em }';
    }
  }
   
  private function getFontSizeURLs() {
    $urls = array();
    
    $args = $this->args;
    foreach ($this->fontsizes as $fontsize) {
      $args['font'] = $fontsize;
      $urls[$fontsize] = self::buildURL($this->page, $args);
    }
    return $urls;
  }

  //
  // Minify URLs
  //
  private function getMinifyUrls() {
    $page = preg_replace('/[\s-]+/', '+', $this->page);
    $minKey = "{$this->id}-{$page}-{$this->pagetype}-{$this->platform}-".md5(SITE_DIR);
    $minDebug = $GLOBALS['siteConfig']->getVar('MINIFY_DEBUG') ? '&debug=1' : '';
    
    return array(
      'css' => "/min/g=css-$minKey$minDebug",
      'js'  => "/min/g=js-$minKey$minDebug",
    );
  }

  //
  // Google Analytics for non-Javascript devices
  //
  private function googleAnalyticsGetImageUrl($gaID) {
    if (isset($gaID) && strlen($gaID)) {
      $url = '/ga.php?';
      $url .= "utmac=$gaID";
      $url .= '&utmn=' . rand(0, 0x7fffffff);
  
      $referer = $this->argVal($_SERVER, 'HTTP_REFERER');
      $path    = $this->argVal($_SERVER, 'REQUEST_URI');
  
      if (!isset($referer)) {
        $referer = '-';
      }
      $url .= '&utmr=' . urlencode($referer);
  
      if (isset($path)) {
        $url .= '&utmp=' . urlencode($path);
      }
  
      $url .= '&guid=ON';

      return $url;
      
    } else {
      return '';
    }
  }

  //
  // Lazy load
  //
  private function loadTemplateEngineIfNeeded() {
    if (!isset($this->templateEngine)) {
      $this->templateEngine = new TemplateEngine($this->id);
    }
  }
  
  //
  // URL helper functions
  //
  protected static function argVal($args, $key, $default=null) {
    if (isset($args[$key])) {
      return $args[$key];
    } else {
      return $default;
    }
  }
  
  protected function getArg($key, $default='') {
    return self::argVal($this->args, $key, $default);
  }

  protected static function buildURL($page, $args=array()) {
    $argString = '';
    if (isset($args) && count($args)) {
      $argString = http_build_query($args);
    }
    
    return $page.(strlen($argString) ? "?$argString" : "");
  }

  public static function buildURLForModule($id, $page, $args=array()) {
    $path = self::getPathSegmentForModuleID($id);
  
    return URL_BASE."$path/".self::buildURL($page, $args);
  }
  
  protected function buildMailToLink($to, $subject, $body) {
    $to = trim($to);
    
    if ($to == '' && $GLOBALS['deviceClassifier']->mailToLinkNeedsAtInToField()) {
      $to = '@';
    }

    $url = "mailto:{$to}?".http_build_query(array("subject" => $subject, 
                                                  "body"    => $body));
    
    // mailto url's do not respect '+' (as space) so we convert to %20
    $url = str_replace('+', '%20', $url); 
    
    return $url;
  }

  public function redirectToModule($id, $page, $args=array()) {
    $url = self::buildURLForModule($id, $page, $args);
    //error_log('Redirecting to: '.$url);
    
    header("Location: $url");
    exit;
  }

  protected function redirectTo($page, $args=null, $preserveBreadcrumbs=false) {
    if (!isset($args)) { $args = $this->args; }
    
    $url = '';
    if ($preserveBreadcrumbs) {
      $url = $this->buildBreadcrumbURL($page, $args, false);
    } else {
      $url = self::buildURL($page, $args);
    }
    
    //error_log('Redirecting to: '.$url);
    header("Location: $url");
    exit;
  }
    
  //
  // Modules can have a path name which overrides the url path their pages reside at. 
  //
  public static function getModuleIDForPathSegment($path) {
    $GLOBALS['siteConfig']->loadWebAppFile('modules');
    
    $modulesConfig = $GLOBALS['siteConfig']->getWebAppVar('modules');
    foreach ($modulesConfig as $moduleID => $moduleConfig) {
      if (isset($moduleConfig['path']) && $moduleConfig['path'] == $path) {
        return $moduleID;
      }
    }
    return $path;
  }
  
  public static function getPathSegmentForModuleID($id) {
    $GLOBALS['siteConfig']->loadWebAppFile('modules');
    
    $modulesConfig = $GLOBALS['siteConfig']->getWebAppVar('modules');
    if (isset($modulesConfig[$id], $modulesConfig[$id]['path'])) {
      return $modulesConfig[$id]['path'];
    }
    return $id;
  }
  
  //
  // Factory function
  // instantiates objects for the different modules
  //
  public static function factory($path, $page='index', $args=array()) {
    // Is there a path alias for this module?
    $id = self::getModuleIDForPathSegment($path);
    
    $className = ucfirst($id).'Module';
    
    $modulePaths = array(
      THEME_DIR."/modules/$id/Theme{$className}.php"=>"Theme" .$className,
      SITE_DIR."/modules/$id/Site{$className}.php"=>"Site" .$className,
      MODULES_DIR."/$id/$className.php"=>$className
    );
    
    foreach($modulePaths as $path=>$className){ 
      $moduleFile = realpath_exists($path);
      if ($moduleFile && include_once($moduleFile)) {
        return new $className($page, $args);
      }
    }
    throw new PageNotFound("Module '$id' not found while handling '{$_SERVER['REQUEST_URI']}'");
  }
  
  protected function initSession() {
    if (!$this->session) {
        $authorityClass = $GLOBALS['siteConfig']->getVar('AUTHENTICATION_AUTHORITY');
        $authorityArgs = $GLOBALS['siteConfig']->getSection('authentication');
        $AuthenticationAuthority = AuthenticationAuthority::factory($authorityClass, $authorityArgs);
        
        $this->session = new Session($AuthenticationAuthority);
    }
  }
  
  function __construct($page='index', $args=array()) {
    $GLOBALS['siteConfig']->loadWebAppFile('modules');
    
    $modules = $GLOBALS['siteConfig']->getWebAppVar('modules');
    
    if($this->id == 'error'){
      // prevents infinite redirects and misconfiguration
      $this->moduleName = (isset($modules[$this->id])) ? $modules[$this->id]['title'] : 'Error';
      $this->pagetype      = $GLOBALS['deviceClassifier']->getPagetype();
      $this->args = $args;
      return;
      
    } else if (isset($modules[$this->id])) {
      $this->moduleName = $modules[$this->id]['title'];
      $moduleData = $modules[$this->id];
    
    } else {
      throw new Exception("Module data for $this->id not found");
    }
    
    $disabled = self::argVal($moduleData, 'disabled', false);
    if ($disabled) {
        $this->redirectToModule('error', '', array('code'=>'disabled', 'url'=>$_SERVER['REQUEST_URI']));
    }
    
    $secure = self::argVal($moduleData, 'secure', false);
    if ($secure && (!isset($_SERVER['HTTPS']) || ($_SERVER['HTTPS'] !='on'))) { 
        // redirect to https (at this time, we are assuming it's on the same host)
         $redirect= "https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
         header("Location: $redirect");    
         exit();
    }
    
    if ($GLOBALS['siteConfig']->getVar('AUTHENTICATION_ENABLED')) {
        $this->initSession();
        $user = $this->getUser();
        $this->assign('session_userID', $user->getUserID());
        $protected = self::argVal($moduleData, 'protected', false);
        if ($protected) {
            if (!$this->session->isLoggedIn()) {
                $this->redirectToModule('error', '', array(
                  'code' => 'protected', 
                  'url'  => self::buildURLForModule('login', 'index', array(
                    'url' => $_SERVER['REQUEST_URI']
                  ))
                ));
            }
        }
    }
    
    $this->page = $page;
    $this->args = $args;
    
    $this->pagetype      = $GLOBALS['deviceClassifier']->getPagetype();
    $this->platform      = $GLOBALS['deviceClassifier']->getPlatform();
    $this->supportsCerts = $GLOBALS['deviceClassifier']->getSupportsCerts();
    
    // Pull in fontsize
    if (isset($args['font'])) {
      $this->fontsize = $args['font'];
      setcookie('fontsize', $this->fontsize, time() + $GLOBALS['siteConfig']->getVar('LAYOUT_COOKIE_LIFESPAN'), COOKIE_PATH);      
    
    } else if (isset($_COOKIE['fontsize'])) { 
      $this->fontsize = $_COOKIE['fontsize'];
    }
    
    switch ($this->pagetype) {
      case 'compliant':
        $this->imageExt = '.png';
        break;
        
      case 'touch':
      case 'basic':
        $this->imageExt = '.gif';
        break;
    }
    
    $this->initialize();
  }
  
  //
  // User functions
  //
  public function getUser()
  {
    $this->initSession();
    return $this->session->getUser();
  }

  //
  // Module list control functions
  //
  protected function getAllModules() {
    $modules = $GLOBALS['siteConfig']->getWebAppVar('modules');
    return $modules;
  }

  protected function getModuleNavlist() {
    $navModules = $this->getNavigationModules(false);
    $separator = array('separator' => array('separator' => true));
    return array_merge($navModules['primary'], $separator, $navModules['secondary']);
  }
  
  protected function getModuleCustomizeList() {    
    $navModules = $this->getNavigationModules(true);
    return $navModules['primary'];
  }

  protected function getNavigationModules($includeDisabled=true) {
    $allModules = $this->getAllModules();
    
    $disabledIDs = array();
    if (isset($_COOKIE[DISABLED_MODULES_COOKIE]) && $_COOKIE[DISABLED_MODULES_COOKIE] != "NONE") {
      $disabledIDs = explode(",", $_COOKIE[DISABLED_MODULES_COOKIE]);
    }
    
    $modules = array(
      'primary'   => array(),
      'secondary' => array(),
    );

    foreach ($allModules as $id => $info) {
      $type = $info['primary'] ? 'primary' : 'secondary';
      $disabled = in_array($id, $disabledIDs) || (!count($disabledIDs) && $info['disabled']);
      
      if ($info['homescreen'] && ($includeDisabled || !$disabled)) {
        $selected = $this->id == $id;
        $primary = $type == 'primary';

        $classes = array();
        if ($selected) { $classes[] = 'selected'; }
        if (!$primary) { $classes[] = 'utility'; }
  
        $imgSuffix = ($this->pagetype == 'tablet' && $selected) ? '-selected' : '';

        $modules[$type][$id] = array(
          'title'       => $info['title'],
          'shortTitle'  => self::argVal($info, 'shortTitle', $info['title']),
          'url'         => '/'.self::argVal($info, 'path', $id).'/',
          'primary'     => $primary,
          'disableable' => $info['disableable'],
          'disabled'    => $disabled,
          'img'         => "/modules/home/images/{$id}{$imgSuffix}".$this->imageExt,
          'class'       => implode(' ', $classes),
        );
      }
    }
    
    if (isset($_COOKIE[MODULE_ORDER_COOKIE])) {
      $sortedIDs = array_merge(array('home'), explode(",", $_COOKIE[MODULE_ORDER_COOKIE]));
      $unsortedIDs = array_diff(array_keys($modules['primary']), $sortedIDs);
            
      $sortedModules = array();
      foreach (array_merge($sortedIDs, $unsortedIDs) as $id) {
        if (isset($modules['primary'][$id])) {
          $sortedModules[$id] = $modules['primary'][$id];
        }
      }
      $modules['primary'] = $sortedModules;
    }
    //error_log('$modules(): '.print_r(array_keys($modules), true));
    return $modules;
  }
  
  protected function setNavigationModuleOrder($moduleIDs) {
    $lifespan = $GLOBALS['siteConfig']->getVar('MODULE_ORDER_COOKIE_LIFESPAN');
    $value = implode(",", $moduleIDs);
    
    setcookie(MODULE_ORDER_COOKIE, $value, time() + $lifespan, COOKIE_PATH);
    $_COOKIE[MODULE_ORDER_COOKIE] = $value;
    //error_log(__FUNCTION__.'(): '.print_r($value, true));
  }
  
  protected function setNavigationHiddenModules($moduleIDs) {
    $lifespan = $GLOBALS['siteConfig']->getVar('MODULE_ORDER_COOKIE_LIFESPAN');
    $value = count($moduleIDs) ? implode(",", $moduleIDs) : 'NONE';
    
    setcookie(DISABLED_MODULES_COOKIE, $value, time() + $lifespan, COOKIE_PATH);
    $_COOKIE[DISABLED_MODULES_COOKIE] = $value;
    //error_log(__FUNCTION__.'(): '.print_r($value, true));
  }
  
  //
  // Functions to add inline blocks of text
  // Call these from initializeForPage()
  //
  protected function addInlineCSS($inlineCSS) {
    $this->inlineCSSBlocks[] = $inlineCSS;
  }
  protected function addInternalCSS($path) {
    $this->cssURLs[] = '/min/g='.MIN_FILE_PREFIX.$path;
  }
  protected function addExternalCSS($url) {
    $this->cssURLs[] = $url;
  }
  protected function addInlineJavascript($inlineJavascript) {
    $this->inlineJavascriptBlocks[] = $inlineJavascript;
  }
  protected function addInlineJavascriptFooter($inlineJavascript) {
    $this->inlineJavascriptFooterBlocks[] = $inlineJavascript;
  }
  protected function addOnOrientationChange($onOrientationChange) {
    $this->onOrientationChangeBlocks[] = $onOrientationChange;
  }
  protected function addOnLoad($onLoad) {
    $this->onLoadBlocks[] = $onLoad;
  }
  protected function addInternalJavascript($path) {
    $this->javascriptURLs[] = '/min/g='.MIN_FILE_PREFIX.$path;
  }
  protected function addExternalJavascript($url) {
    $this->javascriptURLs[] = $url;
  }
  
  //
  // Breadcrumbs
  //
  
  private function encodeBreadcrumbParam($breadcrumbs) {
    return urlencode(gzdeflate(json_encode($breadcrumbs), 9));
  }
  
  private function decodeBreadcrumbParam($breadcrumbs) {
    return json_decode(gzinflate(urldecode($breadcrumbs)), true);
  }
  
  private function loadBreadcrumbs() {
    if ($breadcrumbArg = $this->getArg(MODULE_BREADCRUMB_PARAM)) {
      $breadcrumbs = $this->decodeBreadcrumbParam($breadcrumbArg);
      if (is_array($breadcrumbs)) {
        for ($i = 0; $i < count($breadcrumbs); $i++) {
          $b = $breadcrumbs[$i];
          
          $breadcrumbs[$i]['title'] = $b['t'];
          $breadcrumbs[$i]['longTitle'] = $b['lt'];
          
          $breadcrumbs[$i]['url'] = "{$b['p']}";
          if (strlen($b['a'])) {
            $breadcrumbs[$i]['url'] .= "?{$b['a']}";
          }
          
          $linkCrumbs = array_slice($breadcrumbs, 0, $i);
          if (count($linkCrumbs)) { 
            $this->cleanBreadcrumbs(&$linkCrumbs);
            
            $crumbParam = http_build_query(array(
              MODULE_BREADCRUMB_PARAM => $this->encodeBreadcrumbParam($linkCrumbs),
            ));
            if (strlen($crumbParam)) {
              $breadcrumbs[$i]['url'] .= (strlen($b['a']) ? '&' : '?').$crumbParam;
            }
          }
        }

        $this->breadcrumbs = $breadcrumbs;
        
      }
    }
    //error_log(__FUNCTION__."(): loaded breadcrumbs ".print_r($this->breadcrumbs, true));
  }
  
  private function cleanBreadcrumbs(&$breadcrumbs) {
    foreach ($breadcrumbs as $index => $breadcrumb) {
      unset($breadcrumbs[$index]['url']);
      unset($breadcrumbs[$index]['title']);
      unset($breadcrumbs[$index]['longTitle']);
    }
  }
  
  private function getBreadcrumbString($addBreadcrumb=true) {
    $breadcrumbs = $this->breadcrumbs;
    
    $this->cleanBreadcrumbs(&$breadcrumbs);
    
    if ($addBreadcrumb && $this->page != 'index') {
      $args = $this->args;
      unset($args[MODULE_BREADCRUMB_PARAM]);
      
      $breadcrumbs[] = array(
        't'  => $this->breadcrumbTitle,
        'lt' => $this->breadcrumbLongTitle,
        'p'  => $this->page,
        'a'  => http_build_query($args),
      );
    }
    //error_log(__FUNCTION__."(): saving breadcrumbs ".print_r($breadcrumbs, true));
    return $this->encodeBreadcrumbParam($breadcrumbs);
  }
  
  private function getBreadcrumbArgs($addBreadcrumb=true) {
    return array(
      MODULE_BREADCRUMB_PARAM => $this->getBreadcrumbString($addBreadcrumb),
    );
  }

  protected function buildBreadcrumbURL($page, $args, $addBreadcrumb=true) {
    return "$page?".http_build_query(array_merge($args, $this->getBreadcrumbArgs($addBreadcrumb)));
  }
  
  protected function buildBreadcrumbURLForModule($id, $page, $args, $addBreadcrumb=true) {
    $path = self::getPathSegmentForModuleID($id);
  
    return URL_BASE."$path/$page?".http_build_query(array_merge($args, $this->getBreadcrumbArgs($addBreadcrumb)));
  }
  
  protected function getBreadcrumbArgString($prefix='?', $addBreadcrumb=true) {
    return $prefix.http_build_query($this->getBreadcrumbArgs($addBreadcrumb));
  }

  //
  // Page config
  //
  private function loadPageConfig() {
    if (!isset($this->pageConfig)) {
      // Load site configuration and help text
      $this->loadWebAppConfigFile('site', false);
      $this->loadWebAppConfigFile('help');
  
      // load module config file
      $modulePageConfig = $this->loadWebAppConfigFile($this->id, "{$this->id}PageConfig", true);
    
      $this->pageTitle = $this->moduleName;
  
      if (isset($modulePageConfig[$this->page])) {
        $pageConfig = $modulePageConfig[$this->page];
        
        if (isset($pageConfig['pageTitle'])) {
          $this->pageTitle = $pageConfig['pageTitle'];
        }
          
        if (isset($pageConfig['breadcrumbTitle'])) {
          $this->breadcrumbTitle = $pageConfig['breadcrumbTitle'];
        } else {
          $this->breadcrumbTitle = $this->pageTitle;
        }
          
        if (isset($pageConfig['breadcrumbLongTitle'])) {
          $this->breadcrumbLongTitle = $pageConfig['breadcrumbLongTitle'];
        } else {
          $this->breadcrumbLongTitle = $this->pageTitle;
        }     
        $this->pageConfig = $pageConfig;
      } else {
        $this->pageConfig = array();
      }
    }
  }
  
  // Programmatic overrides for titles generated from backend data
  protected function getPageTitle() {
    return $this->pageTitle;
  }
  protected function setPageTitle($title) {
    $this->pageTitle = $title;
  }
  protected function getBreadcrumbTitle() {
    return $this->breadcrumbTitle;
  }
  protected function setBreadcrumbTitle($title) {
    $this->breadcrumbTitle = $title;
  }
  protected function getBreadcrumbLongTitle() {
    return $this->breadcrumbLongTitle;
  }
  protected function setBreadcrumbLongTitle($title) {
    $this->breadcrumbLongTitle = $title;
  }

  //
  // Module debugging
  //
  protected function addModuleDebugString($string) {
    $this->moduleDebugStrings[] = $string;
  }

  //
  // Theme config files
  //
  
  protected function loadWebAppConfigFile($name, $keyName=null, $ignoreError=false) {
    $this->loadTemplateEngineIfNeeded();

    if ($keyName === null) { $keyName = $name; }
    
    if (!$GLOBALS['siteConfig']->loadWebAppFile($name, true, $ignoreError)) {
      return array();
    }
        
    $themeVars = $GLOBALS['siteConfig']->getWebAppVar($name);
    
    if ($keyName === false) {
      foreach($themeVars as $key => $value) {
        $this->templateEngine->assign($key, $value);
      }
    } else {
      $this->templateEngine->assign($keyName, $themeVars);
    }
    
    return $themeVars;
  }
  
  //
  // Feed config files
  //
  
  protected function loadFeedData() {
    return $GLOBALS['siteConfig']->loadFeedData($this->id);
  }
  
  //
  // Convenience functions
  //
  public function assignByRef($var, &$value) {
    $this->loadTemplateEngineIfNeeded();
        
    $this->templateEngine->assignByRef($var, $value);
  }
  
  public function assign($var, $value) {
    $this->loadTemplateEngineIfNeeded();
        
    $this->templateEngine->assign($var, $value);
  }
  
  public function getTemplateVars($key) {
    $this->loadTemplateEngineIfNeeded();
    
    return $this->templateEngine->getTemplateVars($key);
  }
  
  private function setPageVariables() {
    $this->loadTemplateEngineIfNeeded();
        
    $this->loadPageConfig();
    
    // Set variables common to all modules
    $this->assign('moduleID',     $this->id);
    $this->assign('moduleName',   $this->moduleName);
    $this->assign('page',         $this->page);
    $this->assign('isModuleHome', $this->page == 'index');
    
    // Font size for template
    $this->assign('fontsizes',    $this->fontsizes);
    $this->assign('fontsize',     $this->fontsize);
    $this->assign('fontsizeCSS',  $this->getFontSizeCSS());
    $this->assign('fontSizeURLs', $this->getFontSizeURLs());

    // Minify URLs
    $this->assign('minify', $this->getMinifyUrls());
    
    // Google Analytics
    $gaID = $GLOBALS['siteConfig']->getVar('GOOGLE_ANALYTICS_ID');
    $this->assign('GOOGLE_ANALYTICS_ID', $gaID);
    $this->assign('gaImageURL', $this->googleAnalyticsGetImageUrl($gaID));
    
    // Breadcrumbs
    $this->loadBreadcrumbs();
    
    // Tablet module nav list
    if ($this->pagetype == 'tablet') {
      $this->addInternalJavascript('/common/javascript/lib/iscroll.js');
      $this->assign('moduleNavList', $this->getModuleNavlist());
    }
            
    // Set variables for each page
    $this->initializeForPage();

    $this->assign('pageTitle', $this->pageTitle);

    // Variables which may have been modified by the module subclass
    $this->assign('inlineCSSBlocks',              $this->inlineCSSBlocks);
    $this->assign('cssURLs',                      $this->cssURLs);
    $this->assign('inlineJavascriptBlocks',       $this->inlineJavascriptBlocks);
    $this->assign('onOrientationChangeBlocks',    $this->onOrientationChangeBlocks);
    $this->assign('onLoadBlocks',                 $this->onLoadBlocks);
    $this->assign('inlineJavascriptFooterBlocks', $this->inlineJavascriptFooterBlocks);
    $this->assign('javascriptURLs',               $this->javascriptURLs);
    
    $this->assign('breadcrumbs',            $this->breadcrumbs);
    $this->assign('breadcrumbArgs',         $this->getBreadcrumbArgs());
    $this->assign('breadcrumbSamePageArgs', $this->getBreadcrumbArgs(false));

    $this->assign('moduleDebugStrings',     $this->moduleDebugStrings);

    // Module Help
    if ($this->page == 'help') {
      $this->assign('hasHelp', false);
      
      $template = 'common/'.$this->page;
    } else {
      $helpConfig = $this->getTemplateVars('help');
      $this->assign('hasHelp', isset($helpConfig[$this->id]));
      
      $template = "modules/{$this->id}/{$this->page}";
    }
    
    // Pager support
    if (isset($this->htmlPager)) {
      $this->assign('pager', $this->getPager());
    }
    
    // Tab support
    if (isset($this->tabbedView)) {
      $this->assign('tabbedView', $this->tabbedView);
    }
    
    // Access Key Start
    $accessKeyStart = count($this->breadcrumbs);
    if ($this->id != 'home') {
      $accessKeyStart++;  // Home link
      if ($this->page != 'index') {
        $accessKeyStart++;  // Module home link
      }
    }
    $this->assign('accessKeyStart', $accessKeyStart);

    /* set cache age. Modules that present content that rarely changes can set this value
    to something higher */
    header(sprintf("Cache-Control: max-age=%d", $this->cacheMaxAge));
    header("Expires: " . gmdate('D, d M Y H:i:s', time() + $this->cacheMaxAge) . ' GMT');
    
    return $template;
  }
  
  //
  // Display page
  //
  public function displayPage() {
    $template = $this->setPageVariables();
    
    // Load template for page
    $this->templateEngine->displayForDevice($template);    
  }
  
  //
  // Fetch page
  //
  public function fetchPage() {
    $template = $this->setPageVariables();
    
    // return template for page in variable
    return $this->templateEngine->fetchForDevice($template);    
  }
  
  protected function setCacheMaxAge($age) {
    $this->cacheMaxAge = intval($age);
  }
  
  
  //
  // Subclass this function to set up variables for each template page
  //
  abstract protected function initializeForPage();

  //
  // Subclass this function to perform initialization just after __construct()
  //
  protected function initialize() {} 
    
  //
  // Subclass these functions for federated search support
  // Return 2 items and a link to get more
  //
  public function federatedSearch($searchTerms, $maxCount, &$results) {
    return 0;
  }
  
  protected function urlForFederatedSearch($searchTerms) {
    return $this->buildBreadcrumbURLForModule($this->id, 'search', array(
      'filter' => $searchTerms,
    ), false);
  }
}

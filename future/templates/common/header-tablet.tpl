{extends file="findExtends:common/header.tpl"}

{block name="viewportHeadTag"}
  <meta name="viewport" id="viewport" 
    content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0" />
{/block}

{block name="javascript"}
  {*<link href="/media/jquery/jquery.mobile-1.0a2.css" rel="stylesheet" media="all" type="text/css"/>

  <script src="/media/jquery/jquery-1.4.4.min.js" type="text/javascript"></script>
  <script type="text/javascript">
    {literal}
      $(document).bind("mobileinit", function(){
        $.extend($.mobile , {
          ajaxLinksEnabled: false,
          ajaxFormsEnabled: false,
          metaViewportContent: false
        });
      });
    {/literal}
  </script>
  
  <script src="/media/jquery/jquery.mobile-1.0a2.js" type="text/javascript"></script>*}
  <script src="/media/iscroll.js" type="text/javascript"></script>
  {$smarty.block.parent}
{/block}

{block name="onLoad"} onload="tabletInit(); {if count($onLoadBlocks)}onLoad();{/if}"{/block}

{block name="navbar"}
  <div id="navbar"{if $hasHelp} class="helpon"{/if}>
    <div class="breadcrumbs{if $isModuleHome} homepage{/if}">
      {if $moduleID == 'home'}
        <span class="pagetitle">
          <img src="/common/images/logo-home.png" width="45" height="45" alt="" class="moduleicon" />
          {$pageTitle}
        </span>        
      {else}
        <a name="top" href="/home/" class="homelink">
          <img src="/common/images/homelink.png" width="57" height="45" alt="Home" />
        </a>
        
        {$breadcrumbHTML}
        <span class="pagetitle">
          {if $isModuleHome}
            <img src="/common/images/title-{$navImageID|default:$moduleID}.png" width="28" height="28" alt="" class="moduleicon" />
          {/if}
          {$pageTitle}
        </span>
        {if $hasHelp}
          <div class="help">
            <a href="help.php"><img src="/common/images/help.png" width="46" height="45" alt="Help" /></a>
          </div>
        {/if}
      {/if}
    </div>
  </div>
{/block}
  {block name="containerStart"}
    <div id="wrapper">
      <div id="container">
  {/block}

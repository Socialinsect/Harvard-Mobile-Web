{capture name="banner" assign="banner"}
  {$bannerImg = array()}
  {$bannerImg['src'] = "/modules/{$moduleID}/images/logo-home"}
  
  {block name="bannerImageDetails"}
    {$bannerImg['src'] = $bannerImg['src']|cat:".png"}
    {$bannerImg['width'] = 265}
    {$bannerImg['height'] = 45}
  {/block}

  {block name="topItem"}
  {/block}
  
  <h1{if isset($topItem)} class="roomfornew"{/if}>
    <img src="{$bannerImg['src']}" width="{$bannerImg['width']}" height="{$bannerImg['height']}" alt="{$SITE_NAME}" />
  </h1>
  
  {if isset($topItem)}
    <div id="new"><a href="/about/new.php"><span class="newlabel">NEW:</span>{$topItem}</a></div>
  {/if}
{/capture}

{include file="findInclude:common/header.tpl" customHeader=$banner scalable=false}

{include file="findInclude:common/search.tpl" placeholder="Search "|cat:$SITE_NAME}

{if $home['springboard']}
  {include file="findInclude:common/springboard.tpl" springboardItems=$modules springboardID="homegrid"}
{else}
  {include file="findInclude:common/navlist.tpl" navlistItems=$modules}
{/if}

{block name="homeFooter"}
{/block}

{include file="findInclude:common/footer.tpl"}

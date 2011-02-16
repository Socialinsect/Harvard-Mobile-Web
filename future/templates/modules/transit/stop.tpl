{include file="findInclude:common/header.tpl"}

<div class="nonfocal">
  <h2 class="refreshContainer">
    {block name="refreshButton"}
      <div id="refresh"><a href="{$refreshURL}">
        <img src="/common/images/refresh.png" alt="Update" width="82" height="32">
      </a></div>
    {/block}
    {$stopName}
  </h2>
  <div class="smallprint">
    Refreshed at {$lastRefresh|date_format:"%l:%M"}<span class="ampm">{$lastRefresh|date_format:"%p"}</span><br/>
    Will refresh automatically in <span id="reloadCounter">{$autoReloadTime}</span> seconds
  </div>
</div>
<div id="map">
  <img src="{$mapImageSrc}" height="{$mapImageHeight}" width="{$mapImageWidth}" />
</div>

<h3 class="nonfocal">Currently serviced by:</h3>
  
{if count($runningRoutes)}  
  {foreach $runningRoutes as $i => $routeInfo}
    {capture name="subtitle" assign="subtitle"}
      {include file="findInclude:modules/{$moduleID}/include/predictions.tpl" predictions=$routeInfo['predictions']}
    {/capture}
    {if $subtitle}
      {$runningRoutes[$i]['subtitle'] = $subtitle}
    {/if}
  {/foreach}

  {include file="findInclude:common/navlist.tpl" navlistItems=$runningRoutes accessKey=false subTitleNewline=true}
{else}
  <div class="focal">No routes currently servicing this stop</div>  
{/if}

{if count($offlineRoutes)}
  <h3 class="nonfocal">Services at other times by:</h3>
  {include file="findInclude:common/navlist.tpl" navlistItems=$offlineRoutes accessKey=false}
{/if}

{include file="findInclude:common/footer.tpl"}

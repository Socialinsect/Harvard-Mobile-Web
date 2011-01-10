{include file="findInclude:common/header.tpl"}

<div class="nonfocal">
  Show: 
  {if !$openOnly}<strong>{else}<a href="{$openNowToggleURL}">{/if}
    All libraries
  {if !$openOnly}</strong>{else}</a>{/if}
  |
  {if $openOnly}<strong>{else}<a href="{$openNowToggleURL}">{/if}
    Open libraries
  {if $openOnly}</strong>{else}</a>{/if}
</div>
{if count($entries)}
  {include file="findInclude:common/navlist.tpl" navlistItems=$entries accessKey=false}
{else}
  <div class="focal">No results</div>
{/if}

{include file="findInclude:common/footer.tpl"}

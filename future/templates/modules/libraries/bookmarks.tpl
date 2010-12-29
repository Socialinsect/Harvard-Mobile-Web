{include file="findInclude:common/header.tpl"}

<div class="nonfocal">
  <h2>My Bookmarked  {if $bookmarkType == 'item'}Items{else}Libraries{/if}</h2>
</div>

{if count($results)}
  {if $bookmarkType == 'item'}
    {include file="findInclude:modules/{$moduleID}/itemlist.tpl" items=$results}
  {else}
    {include file="findInclude:common/results.tpl" results=$results accessKey=false}
  {/if}
{else}
  <div class="focal">
    No bookmarks
  </div>
{/if}
{include file="findInclude:common/footer.tpl"}

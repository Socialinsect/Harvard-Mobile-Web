{include file="findInclude:common/header.tpl"}

{if count($entries)}
  {include file="findInclude:common/navlist.tpl" navlistItems=$entries accessKey=false}
{else}
  <div class="focal">No results</div>
{/if}

{include file="findInclude:common/footer.tpl"}

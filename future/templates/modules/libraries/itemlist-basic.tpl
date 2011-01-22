{extends file="findExtends:modules/{$moduleID}/itemlist.tpl"}

{block name="itemTitle"}
  {$item['index']}.&nbsp;{$item['title']}
{/block}

{block name="itemSubtitle"}
  <img src="/modules/{$moduleID}/images/{$item['format']}.gif" alt="{$item['formatDesc']}" width="16" height="16" />&nbsp;
  {$item['date']}{if $item['date'] && $item['creator']} | {/if}{$item['creator']}
{/block}

{block name="pageControls"}
{/block}

{block name="itemList"}
  {$smarty.block.parent}
  <p class="nonfocal">
    {if $prevURL}
      <a href="{$prevURL}">< Previous {$pageSize} results</a>
    {/if}
    {if $prevURL && $nextURL}&nbsp;|&nbsp;{/if}
    {if $nextURL}
      <a href="{$nextURL}">Next {$pageSize} results ></a>
    {/if}
  </p>
{/block}

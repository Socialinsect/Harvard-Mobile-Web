{extends file="findExtends:modules/{$moduleID}/itemlist.tpl"}

{block name="itemTitle"}
  {$item@iteration}. {$item['title']}
{/block}

{block name="itemSubtitle"}
  <img src="/modules/{$moduleID}/images/{$item['format']}.gif" alt="{$item['formatDesc']}" width="16" height="16" />
  {$item['date']}{if $item['date'] && $item['creator']} | {/if}{$item['creator']}
{/block}

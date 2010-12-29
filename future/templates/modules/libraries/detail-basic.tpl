{extends file="findExtends:modules/{$moduleID}/detail.tpl"}

{block name="title"}
{/block}

{block name="header"}
  {if $item['edition']}{$item['edition']} | {/if}
  {if $item['creator']}{$item['creator']} | {/if}
  {if $item['date']}{$item['date']} | {/if}
  {$item['format']|capitalize}{if strlen($item['type'])}: {$item['type']}{/if}
  <br/>
  <img src="/common/images/bookmark-{if $item['bookmarked']}on{else}off{/if}.gif" alt="" />
  <a id="bookmark" href="{$bookmarkURL}">
    {if $item['bookmarked']}Remove bookmark{else}Bookmark this item{/if}
  </a>
  <br/>
{/block}

{block name="locationHeader"}
  <a href="{$location['url']}"><strong>{$location['name']}</strong></a><br/>
{/block}

{block name="item"}
  <img src="/modules/{$moduleID}/images/{$class}.gif" alt="" />
  {$info['available']} of {$info['total']}
  {if $info['type'] != collection}available - {$info['type']}{else}restricted{/if}<br/>
{/block}

{block name="fulllist"}
  <h2>{$item['title']}</h2>
  {$smarty.block.parent}
{/block}

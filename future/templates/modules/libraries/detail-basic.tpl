{extends file="findExtends:modules/{$moduleID}/detail.tpl"}

{block name="itemDetails"}
  {if $item['creator']}<a class="authorLink" href="{$item['creatorURL']}">{$item['creator']}</a> | {/if}
  {if $item['edition']}{$item['edition']} | {/if}
  {if $item['date'] || $item['publisher']}{$item['publisher']} {$item['date']} | {/if}
  {$item['format']|capitalize}{if strlen($item['type'])}: {$item['type']}{/if}
  {if $item['workType']}<br/>Work Type: {$item['workType']}{/if}
  {if $item['thumbnail']}
    {if $item['id']}<br/>HOLLIS #: {$item['id']}{/if}
    <br/><span class="smallprint">1 of {$item['imageCount']} images
      {if $item['fullImageUrl']}<a href="{$item['fullImageUrl']}">(full image)</a>{/if}
    </span>
    <br/><img src="{$item['thumbnail']}" alt="{$item['title']} thumbnail image" />
    
  {/if}
{/block}

{block name="itemDetailHeader"}
  <span class="smallprint">{$itemDetails}</span><br/>
{/block}

{block name="locationTitle"}
  <a href="{$location['url']}"><strong>{$location['name']}</strong></a><br/>
{/block}

{block name="locationURL"}
{/block}

{block name="item"}
  <img src="/modules/{$moduleID}/images/{$class}.gif" alt="" /> {$itemText}<br/>
{/block}

{block name="fulllist"}
  <h2>{$item['title']}</h2>
  <img src="/common/images/bookmark-{if $item['bookmarked']}on{else}off{/if}.gif" alt="" />
  <a id="bookmark" href="{$bookmarkURL}">
    {if $item['bookmarked']}Remove bookmark{else}Bookmark this item{/if}
  </a>
  {$smarty.block.parent}
{/block}

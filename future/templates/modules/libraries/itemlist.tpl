{foreach $items as $i => $item}
  {capture name="title" assign="title"}
    {block name="itemTitle"}
      <div class="ellipsis_wrapper">
        <div class="ellipsis" id="ellipsis_{$i}">{$item['index']}. {$item['title']}</div>
    {/block}
  {/capture}
  {capture name="subtitle" assign="subtitle"}
    {block name="itemSubtitle"}
        <div class="{$item['format']}">
          {$item['date']}{if $item['date'] && $item['creator']} | {/if}{$item['creator']}
        </div>
      </div>
    {/block}
  {/capture}
  {$items[$i]['title'] = $title}
  {$items[$i]['subtitle'] = $subtitle}
{/foreach}
{block name="pageControls"}
  {if $prevURL}
    {$prevPageItem = array()}
    {capture name="prevTitle" assign="prevTitle"}Previous {$pageSize} results{/capture}
    {$prevPageItem['title'] = $prevTitle}
    {$prevPageItem['url'] = $prevURL}
    {$tmp = array_unshift($items, $prevPageItem)}
  {/if}
  {if $nextURL}
    {$nextPageItem = array()}
    {capture name="nextTitle" assign="nextTitle"}Next {$pageSize} results{/capture}
    {$nextPageItem['title'] = $nextTitle}
    {$nextPageItem['url'] = $nextURL}
    {$tmp = array_push($items, $nextPageItem)}
  {/if}
{/block}
{block name="itemList"}
  <div class="listItems">
    {include file="findInclude:common/results.tpl" results=$items subTitleNewline=true}
  </div>
{/block}

{foreach $items as $i => $item}
  {capture name="title" assign="title"}
    {block name="itemTitle"}
      <div class="ellipsis_wrapper">
        <div class="ellipsis" id="ellipsis_{$i}">{$item@iteration}. {$item['title']}</div>
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
{block name="itemList"}
  <div class="listItems">
    {include file="findInclude:common/results.tpl" results=$items subTitleNewline=true}
  </div>
{/block}

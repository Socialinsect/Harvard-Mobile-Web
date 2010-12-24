{include file="findInclude:common/header.tpl"}

{include file="findInclude:modules/{$moduleID}/libraryName.tpl"}

{foreach $item['hours'] as $entry}
  {block name="item"}
    {capture name="title" assign="title"}
      {if $entry['label']}
        <span class="label">{$entry['label']}<br/></span>
      {/if}
      <span class="value">{$entry['title']}</span>
    {/capture}
    {$item['hours'][$entry@index]['title'] = $title}
    {$item['hours'][$entry@index]['label'] = null}
  {/block}
{/foreach}
{block name="itemList"}
  {include file="findInclude:common/navlist.tpl" navlistItems=$item['hours'] accessKey=false labelColon=false}
{/block}

{include file="findInclude:common/footer.tpl"}

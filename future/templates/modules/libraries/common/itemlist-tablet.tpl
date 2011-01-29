{extends file="findExtends:modules/{$moduleID}/common/itemlist.tpl"}

{block name="itemTitle"}
  <div class="ellipsis_wrapper">
    <div class="ellipsis" id="ellipsis_{$i}">{$item['index']}. {$item['title']} 
      {if $item['nonLatinTitle']} ({$item['nonLatinTitle']}){/if}
    </div>
{/block}

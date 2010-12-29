{extends file="findExtends:modules/{$moduleID}/fullHours.tpl"}

{block name="item"}
{/block}

{block name="itemList"}
  <div class="focal">
    {foreach $item['hours'] as $entry}
      <h3>{$entry['label']}</h3>
      <p>{$entry['title']}</p>
      {if !$entry@last}<p class="divider"></p>{/if}
    {/foreach}
  </div>
{/block}

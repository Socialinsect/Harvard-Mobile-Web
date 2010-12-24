{extends file="findExtends:modules/{$moduleID}/locationAndHours.tpl"}

{block name="item"}
{/block}

{block name="itemList"}
  <div class="focal">
    {foreach $section as $entry}
      <h3>{$entry['label']}</h3>
      <p>
        {if $entry['url']}<a href="{$entry['url']}">{/if}
          {$entry['title']}
        {if $entry['url']}</a>{/if}
      </p>
      {if !$entry@last}<p class="divider"></p>{/if}
    {/foreach}
  </div>{if !$sectionIsLast}<br/>{/if}
{/block}

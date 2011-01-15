{extends file="findExtends:modules/{$moduleID}/availability.tpl"}

{block name="header"}
  <h2>
    {$location['name']}
    {if $location['primaryname'] != $location['name']}<br/>({$location['primaryname']}){/if}
  </h2>
  <span class="smallprint">
    {if $location['hours'] && $location['hours'] != 'closed'}
      Open today {$location['hours']}
    {else}
      Closed today
    {/if}
    (<a id="infoLink" href="{$infoURL}">get info</a>)
  </span><br/>
{/block}

{block name="itemTitle"}
  {$item['count']} {$item['state']}
  {if $item['secondaryStatus']}({$item['secondaryStatus']}){/if}
  <br/>
{/block}
{block name="extraItemInfo"}
  {capture name="label" assign="label"}
    <img src="/modules/{$moduleID}/images/{$class}.gif" alt="" />
  {/capture}
  {$listItem['label'] = $label}
  {capture name="subtitle" assign="subtitle"}
    {if $item['callNumber']}{$item['callNumber']}{if $item['description']}, {/if}{/if}
    {if $item['description']}{$item['description']}{/if}
  {/capture}
  {$listItem['subtitle'] = $subtitle}
{/block}

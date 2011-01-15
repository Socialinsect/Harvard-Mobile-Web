{extends file="findExtends:modules/{$moduleID}/request.tpl"}

{block name="header"}
   <h2>
    {$info['name']}
    {if $info['primaryname'] != $info['name']}<br/>({$info['primaryname']}){/if}
  </h2>
  <span class="smallprint">
    {if $info['hours'] && $info['hours'] != 'closed'}
      Open today {$info['hours']}
    {else}
      Closed today
    {/if}
    (<a id="infoLink" href="{$info['infoUrl']}">get info</a>)
  </span><br/>
{/block}


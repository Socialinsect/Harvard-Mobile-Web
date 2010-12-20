{extends file="findExtends:modules/{$moduleID}/availability.tpl"}

{block name="header"}
  <h2>
    {$location['name']}
  </h2>
  <span class="smallprint">
    {if $location['hours'] && $location['hours'] != 'closed'}
      Open today {$location['hours']}
    {else}
      Closed today
    {/if}
    (<a id="infoLink" href="{$infoURL}">get info</a>)
  </span><br/>
  {$subTitleNewline = true}
{/block}

{block name="itemTitle"}
  <img src="/modules/{$moduleID}/images/{$class}.gif" alt="" />
  {$info['count']}
  {if $type == 'available'}
    available
  {elseif $type == 'collection'}
    may be available
  {elseif $info['status']}
    {$info['status']}
  {else}
    {$type}
  {/if}
{/block}
{block name="itemSubTitle"}
  {$info['callNumber']}
{/block}

{block name="itemHeader"}
  <div class="nonfocal">
    {$itemHeader}
{/block}

{block name="itemFooter"}
  </div><br/>
{/block}

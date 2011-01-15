{extends file="findExtends:modules/{$moduleID}/index.tpl"}

{block name="header"}
  {if $tabbedView['current'] == 'location'}
    {$current|date_format:"%a %B %e"}
  {else}
    Menu for <strong>{$current|date_format:"%a %b %e"}</strong>
  {/if}
{/block}


{block name='sideNavClass'}nonfocal{/block}

{block name="tabView"}
  <div class="focal">
    {include file="findInclude:common/tabs.tpl" tabBodies=$tabBodies}
  </div>
{/block}

{block name="mealPane"}
  <h3>{$foodType}</h3>
  <p class="results">
    {foreach $foods as $food}
      {$food['item']}<br/>
    {/foreach}
  </p>
{/block}

{block name="locationStatusImageDetails"}
  {$statusImages[$status]['src']    = $statusImages[$status]['src']|cat:".gif"}
  {$statusImages[$status]['height'] = "13"}
  {$statusImages[$status]['width']  = "13"}
{/block}

{block name="locationStatusKeys"}
  <p class="iconlegend"></p>
  {foreach $statusImages as $statusImage}
    <p>
      <img src="/modules/{$moduleID}/images/{$statusImage['src']}" width="{$statusImage['height']}" height="{$statusImage['width']}" alt="{$statusImage['alt']}"/>
      {$statusImage['title']}&nbsp;
    </p>    
  {/foreach}
{/block}

{block name="locationDiningStatuses"}  
  {foreach $diningStatuses as $diningStatus}
    {$statusImage = $statusImages[$diningStatus['status']]}
    <p>
      <img src="/modules/{$moduleID}/images/{$statusImage['src']}" width="{$statusImage['height']}" height="{$statusImage['width']}" alt="{$statusImage['alt']}"/>
      <a class="dininghall {$diningStatus['status']}" href="{$diningStatus['url']}">
        {$diningStatus['name']}
      </a><span class="smallprint">: {$diningStatus['summary']}</span>
    </p>
  {/foreach}
{/block}

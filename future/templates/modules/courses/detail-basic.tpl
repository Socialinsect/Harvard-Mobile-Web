{extends file="findExtends:modules/{$moduleID}/detail.tpl"}

{block name="infoPane"}
  {if !count($times) && !count($infoItems)}
    <p> No detailed information to display </p>
  {else}
    <h4>Lecture:</h4>
    {foreach $times as $time}
      <p>
        {if isset($time['location']) && isset($time['url'])}
          {$time['days']} {$time['time']} 
          (<a class="map" href="{$time['url']}">{$time['location']}</a>)
        {else}
          {$time['days']} {$time['time']}
        {/if}
      </p>
    {/foreach}
    {foreach $infoItems as $item}
      <p class="divider"></p>
      <h3>{$item['header']}</h3>
      <p>{$item['content']|escape}</p>
    {/foreach}
  {/if}
{/block}

{block name="staffPane"}
  {foreach $staff['instructors'] as $instructor}
    <p><a href="{$instructor['url']}" class="people">{$instructor['title']}</a></p>
  {/foreach}
  
  {if count($staff['tas'])}
    <h3>TAs:</h3>
    {foreach $staff['tas'] as $ta}
      <p><a href="{$ta['url']}" class="people">{$ta['title']}</a></p>
    {/foreach}
  {/if}
{/block}

{block name="courseHeader"}
  <h2>{$className}: {$classTitle}</h2>
  <p class="address">{$term}{if strlen($classUrl)} | 
  <span class="{if $isInMyClasses}ms_on{else}ms_off{/if}">
    <img src="/common/images/bookmark-{if $isInMyClasses}on{else}off{/if}.gif" alt="" width="21" height="16" />My Courses
    (<a id="bookmark" href="{$toggleMyClassesURL}">{if $isInMyClasses}remove{else}add{/if}</a>)
  </span> | 
  <a href="{$classUrl}" target="_new">Course Website</a>{/if}</p>
{/block}


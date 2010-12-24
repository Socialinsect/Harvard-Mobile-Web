{include file="findInclude:common/header.tpl"}

<div class="nonfocal">
  {block name="header"}
    <h2>
      <a id="infoLink" href="{$infoURL}">
        <img class="infoLink" src="/common/images/info_button@2x.png" alt="get info" width="44" height="38" />
      </a>
      {$location['name']}
    </h2>
    <span class="distance">
      {if $location['hours'] && $location['hours'] != 'closed'}
        Open today {$location['hours']}
      {else}
        Closed today
      {/if}
    </span><br/>
  {/block}
</div>

{foreach $location['collections'] as $collection}
  <div class="nonfocal">
    <h3>{$collection['name']}</h3>
  {foreach $collection['items'] as $item}
    {if !$item@first}<div class="focal">{/if}
      {if $item['type'] != 'collection' || $collection['callNumber']}
        <span class="smallprint">
          {if $item['type'] != 'collection'}{$item['type']}{/if}
          {if $item['type'] != 'collection' && $collection['callNumber']}<br/>{/if}
          {if $collection['callNumber']}{$collection['callNumber']}{/if}
        </span>
      {/if}
    </div>
    
    {$list = array()}
    {foreach $item['types'] as $type => $info}
      {if $type != 'collection'}
        {$listItem = array()}
        {capture name="title" assign="title"}
          {if $type == 'available'}
            {$class = 'available'}
          {elseif $type == 'requestable'}
            {$class = 'requestable'}
          {else}
            {$class = 'unavailable'}
          {/if}
          {block name="itemTitle"}
            <span class="itemType {$class}">
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
            </span>
          {/block}
        {/capture}
        {$listItem['title'] = $title}
        {if $info['url']}
          {$listItem['url'] = $info['url']}
        {/if}
        {$list[] = $listItem}
      {/if}
    {/foreach}
    {if count($list)}
      <div class="items">
        {include file="findInclude:common/navlist.tpl" navlistItems=$list subTitleNewline=false accessKey=false}
      </div>
    {/if}
  {/foreach}
{/foreach}

{include file="findInclude:common/footer.tpl"}

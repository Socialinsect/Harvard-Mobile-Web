{include file="findInclude:common/header.tpl"}

{$results = array()}
{$i = 0}

{$results[$i] = array()}
{capture name="header" assign="header"}
  {block name="header"}
    <a id="bookmark" class="{if $item['bookmarked']}bookmarked{/if}" onclick="toggleBookmark(this, '{$item['id']}', '{$item['cookie']}')"></a>
    <h2>{$item['title']}</h2>
    <p class="smallprint">
      {if $item['creator']}<br/>{$item['creator']}{/if}
      {if $item['edition']}<br/>{$item['edition']}{/if}
      {if $item['date'] || $item['publisher']}<br/>{$item['date']} {$item['publisher']}{/if}
      {if $item['formatDesc'] && $item['format']|lower != 'image'}
        <br/>{$item['formatDesc']|capitalize}{if strlen($item['type'])}: {$item['type']}{/if}
      {/if}
      {if $item['workType']}<br/>Work Type: {$item['workType']}{/if}
      {if $item['thumbnail']}
        {if $item['id']}<br/>HOLLIS #: {$item['id']}{/if}
        <div class="thumbnail">
          <div class="smallprint">1 of {$item['imageCount']} images</div>
          {if $item['fullImageUrl']}<a href="{$item['fullImageUrl']}">{/if}
            <img src="{$item['thumbnail']}" alt="{$item['title']} thumbnail image" />
          {if $item['fullImageUrl']}<br/><span class="smallprint">(click for full image)</span></a>{/if}
        </div>
      {/if}
    </p>
  {/block}
{/capture}
{$results[$i]['title'] = $header}
{$i = $i + 1}

{if $item['isOnline']}
  {$results[$i] = array()}
  {$results[$i]['title'] = '<strong>Available Online</strong>'}
  {$results[$i]['url'] = $item['onlineUrl']}
  {$i = $i + 1}
{/if}

{foreach $locations as $location}
  {$results[$i] = array()}
  {capture name="title" assign="title"}
    {block name="locationHeader"}
      <strong>{$location['name']}</strong><br/>
      <div class="distance" id="location_{$location['id']}"></div>
    {/block}
    {foreach $location['collections'] as $collection}
      {foreach $collection['items'] as $info}
        
        {if $info['available'] > 0}
          {$class = 'available'}
        {elseif $info['requestable'] > 0}
          {$class = 'requestable'}
        {else}
          {$class = 'unavailable'}
        {/if}
        {block name="item"}
          <div class="itemType {$class}">
            {$info['available']} of {$info['total']}
            {if $info['type'] != 'collection'}
              available - {$info['type']}
            {else}
              may be available
            {/if}<br/>
          </div>
        {/block}
      {/foreach}
    {/foreach}
  {/capture}
  {$results[$i]['title'] = $title}
  {$results[$i]['url'] = $location['url']}
  {$i = $i + 1} 
{/foreach}

{block name="fulllist"}
  {include file="findInclude:common/navlist.tpl" navlistItems=$results accessKey=false}
{/block}

{include file="findInclude:common/footer.tpl"}

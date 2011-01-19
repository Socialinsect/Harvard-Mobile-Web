{include file="findInclude:common/header.tpl"}

{capture name="itemDetails" assign="itemDetails"}
  {block name="itemDetails"}
    {if $item['creator']}<br/><a class="authorLink" href="{$item['creatorURL']}">{$item['creator']}</a>{/if}
    {block name="nonLatinCreator"}
      {if $item['nonLatinCreator']} ({$item['nonLatinCreator']}){/if}
    {/block}
    {if $item['edition']}<br/>{$item['edition']}{/if}
    {if $item['date'] || $item['publisher']}<br/>{$item['publisher']} {$item['date']}{/if}
    {if ($item['formatDesc'] || $item['type']) && $item['format']|lower != 'image'}
      <br/>{if $item['formatDesc']}{$item['formatDesc']|capitalize}{if strlen($item['type'])}:{/if}{/if}
      {if strlen($item['type'])}{$item['type']}{/if}
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
  {/block}
{/capture}

{$results = array()}
{$i = 0}

{$results[$i] = array()}
{capture name="header" assign="header"}
  {block name="itemDetailHeader"}
    <a id="bookmark" class="{if $item['bookmarked']}bookmarked{/if}" onclick="toggleBookmark(this, '{$item['id']}', '{$item['cookie']}')"></a>
    <h2>{$item['title']}{if $item['nonLatinTitle']} ({$item['nonLatinTitle']}){/if}</h2>
    <div class="smallprint">
      {$itemDetails}
    </div>
  {/block}
{/capture}
{$results[$i]['title'] = $header}
{$i = $i + 1}

{if $item['isOnline']}
  {$results[$i] = array()}
  {$results[$i]['title'] = '<strong>Available Online</strong>'}
  {$results[$i]['class'] = 'external'}
  {$results[$i]['linkTarget'] = 'new'}
  {$results[$i]['url'] = $item['onlineUrl']}
  {$i = $i + 1}
{/if}

{foreach $locations as $location}
  {$results[$i] = array()}
  {capture name="title" assign="title"}
    {block name="locationTitle"}
      <strong>{$location['name']}</strong><br/>
      <div class="distance" id="location_{$location['id']}"></div>
    {/block}
    {foreach $location['categories'] as $category}
      
      {if $category['available'] > 0}
        {$class = 'available'}
      {elseif $category['requestable'] > 0}
        {$class = 'requestable'}
      {else}
        {$class = 'unavailable'}
      {/if}
      {capture name="itemText" assign="itemText"}
        {if $category['collection'] > 0}
          {$category['collection']} may be available
        {else}
          {$category['available']} of {$category['total']} available - {$category['holdingStatus']}
        {/if}
      {/capture}
      {block name="item"}
        <div class="itemType {$class}">
          {$itemText}
        </div>
      {/block}
    {/foreach}
  {/capture}
  {$results[$i]['title'] = $title}
  {block name="locationURL"}
    {$results[$i]['url'] = $location['url']}
  {/block}
  {$i = $i + 1} 
{/foreach}

{block name="fulllist"}
  {include file="findInclude:common/navlist.tpl" navlistItems=$results accessKey=false}
{/block}

{include file="findInclude:common/footer.tpl"}

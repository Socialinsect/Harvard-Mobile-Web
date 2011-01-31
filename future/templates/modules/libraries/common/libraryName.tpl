{capture name="libraryName" assign="libraryName"}
  <h2>{$item['name']}
    {if $item['fullName'] && $item['fullName'] != $item['name']}
      <br/>({$item['fullName']})
    {/if}
  </h2>
{/capture}

<div class="nonfocal libraryName">
  {block name="header"}
    <a id="bookmark" class="{if $item['bookmarked']}bookmarked{/if}" onclick="toggleBookmark(this, '{$item['id']}', '{$item['cookie']}')"></a>
    {$libraryName}
  {/block}
</div>

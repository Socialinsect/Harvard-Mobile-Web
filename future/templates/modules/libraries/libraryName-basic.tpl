{extends file="findExtends:modules/{$moduleID}/libraryName.tpl"}

{block name="header"}
    {$libraryName}
  </div>
  <div class="focal">
    <img src="/common/images/bookmark-{if $item['bookmarked']}on{else}off{/if}.gif" alt="" />
    <a id="bookmark" href="{$bookmarkURL}">
      {if $item['bookmarked']}Remove bookmark{else}Bookmark this {$item['type']}{/if}
    </a>
{/block}

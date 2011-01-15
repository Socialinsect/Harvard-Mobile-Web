{extends file="findExtends:modules/{$moduleID}/detail.tpl"}

{block name="nonLatinCreator"}
{/block}

{block name="itemDetailHeader"}
  <a id="bookmark" href="{$bookmarkURL}">
    <img src="/common/images/bookmark-{if $item['bookmarked']}on{else}off{/if}.gif" alt="" />
  </a>
  <h2>{$item['title']}</h2>
  <div class="smallprint">
    {$itemDetails}
  </div>
{/block}

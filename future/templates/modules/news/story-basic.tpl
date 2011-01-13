{extends file="findExtends:modules/news/story.tpl"}

{block name="shareImage"}{/block}

{block name="byline"}
  {$smarty.block.parent}
  <a href="{$shareEmailURL}">Email this article</a>
{/block}

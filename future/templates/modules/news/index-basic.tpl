{extends file="findExtends:modules/$moduleID/index.tpl"}

{block name="newsHeader"}
  <h2>{$currentSection['title']}</h2>
{/block}

{block name="newsFooter"}
    {block name="searchsection"}
        {include file="findInclude:common/search.tpl" extraArgs=$hiddenArgs}
    {/block}
  {include file="findInclude:common/footer.tpl" additionalLinks=$sections}
{/block}

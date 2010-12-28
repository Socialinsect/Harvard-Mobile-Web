{block name="header"}
    {include file="findInclude:common/header.tpl"}
{/block}


<div class="nonfocal">
  <h2>{$schoolNameShort}</h2>
</div>

{block name="searchsection"}
    {include file="findInclude:common/search.tpl" emphasized=false placeholder="Search keyword, #, or instructor" extraArgs=$extraSearchArgs}
{/block}

{include file="findInclude:common/navlist.tpl" navlistItems=$courses}

{include file="findInclude:common/footer.tpl"}

{block name="header"}
    {include file="findInclude:common/header.tpl"}
{/block}

{block name="searchsection"}
    {include file="findInclude:common/search.tpl" placeholder="Search Map" tip="You can search by any category shown in the 'Browse by' list below."}
{/block}

<div class="nonfocal">
  <h3>Browse map by:</h3>
</div>
{include file="findInclude:common/navlist.tpl" navlistItems=$categories}

{include file="findInclude:common/footer.tpl"}

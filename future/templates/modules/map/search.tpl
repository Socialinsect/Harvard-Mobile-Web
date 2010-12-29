{block name="header"}
    {include file="findInclude:common/header.tpl"}
{/block}

{block name="searchsection"}
    {include file="findInclude:common/search.tpl" placeholder="Search Map" emphasized=false}
{/block}

{include file="findInclude:common/results.tpl" results=$places}

{include file="findInclude:common/footer.tpl"}

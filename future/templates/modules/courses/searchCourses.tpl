{block name="header"}
    {include file="findInclude:common/header.tpl"}
{/block}

{block name="searchsection"}
    {include file="findInclude:common/search.tpl" emphasized=false placeholder="Search Courses" searchPage='searchCourses'}
{/block}

{include file="findInclude:common/navlist.tpl" navlistItems=$schools}

{include file="findInclude:common/footer.tpl"}

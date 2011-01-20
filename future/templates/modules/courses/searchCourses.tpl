{include file="findInclude:common/header.tpl"}

{include file="findInclude:common/search.tpl" emphasized=false placeholder="Search Courses" searchPage='searchCourses'}

{if count($schools)}
  {include file="findInclude:common/navlist.tpl" navlistItems=$schools}
{else}
  <div class="nonfocal">
    No results found
  </div>
{/if}

{include file="findInclude:common/footer.tpl"}

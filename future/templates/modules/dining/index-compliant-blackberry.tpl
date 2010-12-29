{extends file="findExtends:modules/{$moduleID}/index.tpl"}

{block name="searchsection"}
  {include file="findInclude:common/search-compliant-blackberry.tpl" placeholder="Search "|cat:$moduleName}
{/block}

{include file="findInclude:common/header.tpl"}

{include file="findInclude:common/search.tpl" inputName='keywords' placeholder=$searchPlaceholder}


{foreach $sections as $section}
  {include file="findInclude:common/navlist.tpl" navlistItems=$section['items'] accessKey=false}
{/foreach}

{include file="findInclude:common/footer.tpl"}

{include file="findInclude:common/header.tpl"}

{foreach $sections as $section}
  {if $section['heading']}
    <div class="nonfocal"><h3>{$section['heading']}</h3></div>
  {/if}
  {include file="findInclude:common/navlist.tpl" navlistItems=$section['items'] accessKey=false subTitleNewline=true}
{/foreach}

{include file="findInclude:common/footer.tpl"}

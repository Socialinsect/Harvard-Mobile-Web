{include file="findInclude:common/header.tpl"}

{if isset($description) && strlen($description)}
  <p class="{block name='headingClass'}nonfocal smallprint{/block}">
    {$description}
  </p>
{/if}

{if $displayType == 'springboard'}
  {include file="findInclude:common/springboard.tpl" springboardItems=$links springboardID="links"}
{elseif $displayType == 'list'}
  {include file="findInclude:common/navlist.tpl" navlistItems=$links}
{/if}

<p class="clear"> </p>

{include file="findInclude:common/footer.tpl"}

{include file="findInclude:common/header.tpl"}

{$firstField = array_shift($fields)}
{$lastField = array_pop($fields)}

<div class="focal">
  {block name="firstField"}
    <h2>
      {include file="findInclude:common/listItem.tpl" item=$firstField accessKey=false}
    </h2>
  {/block}
  
  {block name="fields"}
    {if count($fields)}
      {include file="findInclude:common/navlist.tpl" navlistItems=$fields accessKey=false}
    {/if}
  {/block}
  
  <p class="legend">
    {include file="findInclude:common/listItem.tpl" item=$lastField accessKey=false}
  </p>

</div>

{include file="findInclude:common/footer.tpl"}

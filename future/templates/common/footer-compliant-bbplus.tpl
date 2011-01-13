{extends file="findExtends:common/footer.tpl"}

{block name="footer"}
  <p class="fontsize">
    Font size:&nbsp;
    {foreach $fontsizes as $size}
      {if $size == $fontsize}
        <span class="font{$fontsize}">A</span>
      {else}
        <a href="{$fontSizeURL}{$size}" class="font{$size}">A</a>
      {/if}
      {if !$size@last} | {/if}
    {/foreach}
  </p>
  
  {$smarty.block.parent}
{/block}

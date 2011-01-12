{extends file="findExtends:common/footer-touch.tpl"}

{block name="footer"}

  {if $moduleID != 'home'}
    <div id="footerlinks">
      <a href="#top">Back to top</a> | <a href="../home/">{$SITE_NAME} home</a>{if $session_userID} | <a href="../login">{$session_userID} logged in</a>{/if}
    </div>
  {/if}

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

  <p class="bb"> </p>

  <div id="footer">
    {$footerHTML}
  </div>

  {if strlen($gaImageURL)}
    <img src="{$gaImageURL}" />
  {/if}
{/block}

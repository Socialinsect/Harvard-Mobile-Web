{extends file="findExtends:common/footer.tpl"}

{block name="footer"}
  {if $moduleID != 'home'}
    <div id="footerlinks">
      <a href="#top">Back to top</a> 
      {if $hasHelp}| <a href="help.php">Help</a>{/if}
      | <a href="../home/">{$SITE_NAME} home</a>
      {if $session_userID}| <a href="../login">{$session_userID} logged in</a>{/if}
    </div>
  {/if}

  <div id="footer">
    {$footerHTML}
  </div>
  
  {if strlen($gaImageURL)}
    <img src="{$gaImageURL}" />
  {/if}
{/block}

{block name="footerJavascript"}
{/block}

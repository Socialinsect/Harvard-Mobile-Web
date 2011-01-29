  {if $moduleDebug && count($moduleDebugStrings)}
    <p class="legend nonfocal">
      {foreach $moduleDebugStrings as $string}
        <br/>{$string}
      {/foreach}
    </p>  
  {/if}
  
  {capture name="footerHTML" assign="footerHTML"}
    {if $COPYRIGHT_LINK}
      <a href="{$COPYRIGHT_LINK}" class="copyright">
    {/if}
        {$COPYRIGHT_NOTICE}
    {if $COPYRIGHT_LINK}
      </a>
    {/if}
  {/capture}
  
  {block name="footerNavLinks"}
    {if $moduleID != 'home'}
      <div id="footerlinks">
        <a href="#top">Back to top</a> | <a href="../home/">{$SITE_NAME} home</a>{if $session_userID} | <a href="../login">{$session_userID} logged in</a>{/if}
      </div>
    {/if}
  {/block}

  {block name="footer"}
    <div id="footer">
      {$footerHTML}
    </div>
  {/block}

  {if $moduleID == 'home' && $showDeviceDetection}
    <table class="devicedetection">
      <tr><th>Pagetype:</th><td>{$pagetype}</td></tr>
      <tr><th>Platform:</th><td>{$platform}</td></tr>
      <tr><th>Certificates:</th><td>{if $supportsCerts}yes{else}no{/if}</td></tr>
      <tr><th>User Agent:</th><td>{$smarty.server.HTTP_USER_AGENT}</td></tr>
    </table>
  {/if}

  {block name="footerJavascript"}
    {foreach $inlineJavascriptFooterBlocks as $script}
      <script type="text/javascript">
        {$script} 
      </script>
    {/foreach}
  {/block}
{block name="containerEnd"}
  </div>
{/block}

{block name="belowContent"}
{/block}
</body>
</html>

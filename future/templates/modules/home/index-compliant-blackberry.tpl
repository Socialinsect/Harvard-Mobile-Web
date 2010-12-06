{extends file="findExtends:modules/home/index-compliant-bbplus.tpl"}

{block name="bannercapture"}
    {capture name="banner" assign="banner"}
        <h1><img id="logo" src="/modules/home/images/logo-home.png" 
            width="210" height="35" alt="{$SITE_NAME}" /></h1>
    {/capture}
{/block}

{block name="homeFooter"}
<br clear="both"/>
<table border="0"><tr>
    <td>&nbsp;<!--The BlackBerry will ignore the <br> without this--></td>
</tr></table>

  <div id="download">
    <a href="../download/">
      <img src="/modules/home/images/download.png" width="32" height="26" 
      alt="Download" align="absmiddle" />
      Add the BlackBerry shortcut to your home screen
    </a>
    <br />
  </div>
{/block} 
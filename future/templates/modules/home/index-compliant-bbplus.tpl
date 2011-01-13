{extends file="findExtends:modules/{$moduleID}/index-touch-blackberry.tpl"}

{block name="bannerImageDetails"}
  {$bannerImg['src'] = $bannerImg['src']|cat:".png"}
  {$bannerImg['width'] = 400}
  {$bannerImg['height'] = 68}
{/block}

{block name="homeFooter"}
  <p class="bb"> </p>

  <div id="download">
    <a href="../download/">
      <img src="/modules/home/images/download.gif" width="32" height="26" 
      alt="Download" align="absmiddle" />
      Add the BlackBerry shortcut to your home screen
    </a>
    <br />
  </div>
{/block}

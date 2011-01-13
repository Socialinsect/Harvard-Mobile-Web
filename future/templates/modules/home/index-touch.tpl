{extends file="findExtends:modules/home/index.tpl"}

{block name="topItem"}
  <p class="bb"> </p>  
{/block}

{block name="bannerImageDetails"}
  {$bannerImg['src'] = $bannerImg['src']|cat:".gif"}
  {$bannerImg['width'] = 210}
  {$bannerImg['height'] = 35}
{/block}

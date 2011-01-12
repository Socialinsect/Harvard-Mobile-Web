{extends file="findExtends:modules/home/index.tpl"}

{block name="topItem"}{/block}

{block name="bannerImageDetails"}
  {$bannerImg['src'] = $bannerImg['src']|cat:".gif"}
  {$bannerImg['width'] = 210}
  {$bannerImg['height'] = 35}
{/block}

{extends file="findExtends:modules/{$moduleID}/index-touch-blackberry.tpl"}

{block name="bannerImageDetails"}
  {$bannerImg['src'] = $bannerImg['src']|cat:".gif"}
  {$bannerImg['width'] = 400}
  {$bannerImg['height'] = 67}
{/block}

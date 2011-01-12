{extends file="findExtends:modules/{$moduleID}/index.tpl"}

{block name="locationStatusImageDetails"}
  {$statusImages[$status]['src']    = $statusImages[$status]['src']|cat:".gif"}
  {$statusImages[$status]['height'] = "13"}
  {$statusImages[$status]['width']  = "13"}
{/block}

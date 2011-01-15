{extends file="findExtends:modules/{$moduleID}/detail.tpl"}

{block name="courseHeader"}
  <a id="myclasses" href="{$toggleMyClassesURL}">
    <img src="/common/images/bookmark-{if $isInMyClasses}on{else}off{/if}.gif" alt="" />
  </a>
  <h2>{$className}: {$classTitle}</h2>
  <p class="address">{$term}{if strlen($classUrl)} | <a href="{$classUrl}" target="_new">Course Website</a>{/if}</p>
{/block}

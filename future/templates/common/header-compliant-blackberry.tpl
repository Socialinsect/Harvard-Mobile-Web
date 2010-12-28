{extends file="findExtends:common/header-compliant.tpl"}

{block name="additionalHeadTags"}
    <meta name="HandheldFriendly" content="true" />
{/block}

{block name="pagetitle"}
    {if $isModuleHome}
      <img src="/common/images/title-{$navImageID|default:$moduleID}.png" width="28" height="28" alt="" class="moduleicon" />
    {/if}        
    <div class="pagetitlecontainer">
        <div class="pagetitle">
        {$pageTitle}
        </div>
    </div>
{/block}

{extends file="findExtends:common/header.tpl"}

{block name="javascript"}{/block}
{block name="onLoad"}{/block}

{block name="breadcrumbs"}
  {strip}
    {if !$isModuleHome && $moduleID != 'home'}
      <a href="./" class="moduleicon">
        <img src="/common/images/title-{$navImageID|default:$moduleID}.gif" width="28" height="28" alt="" />
      </a>
    {/if}
  {/strip}
{/block}

{block name="navbar"}
  {strip}
    <div id="navbar"{if $hasHelp} class="helpon"{/if}>
      <div class="breadcrumbs{if $isModuleHome} homepage{/if}">
        <a name="top" href="/home/" class="homelink">
          <img src="/common/images/homelink.gif" width="40" height="30" alt="Home" />
        </a>
        {$breadcrumbHTML}
          {if $isModuleHome}
            <img src="/common/images/title-{$navImageID|default:$moduleID}.gif" width="28" height="28" alt="" class="moduleicon" />
          {/if}
        <span class="pagetitle">
          {$pageTitle}
        </span>
      </div>
    </div>
  {/strip}
{/block}

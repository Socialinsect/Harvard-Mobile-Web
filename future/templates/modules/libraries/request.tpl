{include file="findInclude:common/header.tpl"}

<div class="nonfocal">
  {block name="header"}
    <h2>
      <a id="infoLink" href="{$info['infoUrl']}">
        <img class="infoLink" src="/common/images/info_button@2x.png" alt="get info" width="44" height="38" />
      </a>
      {$info['name']}
      {if $info['primaryname'] != $info['name']}<br/>({$info['primaryname']}){/if}
    </h2>
    {if $info['state'] == 'available'}
      <span class="distance">
        {if $info['hours'] && $info['hours'] != 'closed'}
          Open today {$info['hours']}
        {else}
          Closed today
        {/if}
      </span><br/>
    {/if}
  {/block}
</div>
<div class="nonfocal itemInfo">
  <h3>{$info['collectionName']}</h3>
  {if $info['holdingStatus'] != 'collection' || $info['callNumber']}
    <p class="smallprint">
      {if $info['callNumber']}{$info['callNumber']}{/if}
      {if $info['holdingStatus'] != 'collection'}
        {if $info['callNumber']}<br/>{/if}
        {$info['holdingStatus']}
      {/if}
      <br/>
      {$info['state']}
      {if $info['secondaryStatus']}({$info['secondaryStatus']}){/if}
    </p>
  {/if}
</div>

{$requestLinks = array()}
{if $info['requestURL']}
  {$requestLink = array()}
  {$requestLink['title'] = "Request Item"}
  {$requestLink['url'] = $info['requestURL']}
  {$requestLink['class'] = "external"}
  {$requestLinks[] = $requestLink}
{/if}
{if $info['scanURL']}
  {$requestLink = array()}
  {$requestLink['title'] = "Scan &amp; Deliver"}
  {$requestLink['url'] = $info['scanURL']}
  {$requestLink['class'] = "external"}
  {$requestLinks[] = $requestLink}
{/if}
{include file="findInclude:common/navlist.tpl" navlistItems=$requestLinks subTitleNewline=true accessKey=false linkTarget="new"}

{include file="findInclude:common/footer.tpl"}

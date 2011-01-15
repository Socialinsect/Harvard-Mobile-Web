{include file="findInclude:common/header.tpl"}

{include file="findInclude:modules/{$moduleID}/libraryName.tpl"}

{$hasInfo = false}
{foreach $item['infoSections'] as $key => $section}
  {$sectionIsLast = $section@last}
  {$entries = array()}
  {foreach $section as $entry}
    {block name="item"}
      {capture name="title" assign="title"}
        {if $entry['label']}
          <span class="longlabel">{$entry['label']}<br/></span>
          <span class="value">
        {/if}
            {$entry['title']}
        {if $entry['label']}
          </span>
        {/if}
      {/capture}
      {$section[$entry@index]['title'] = $title}
      {$section[$entry@index]['label'] = null}
    {/block}
    {$hasInfo = true}
  {/foreach}
  {block name="itemList"}
    {include file="findInclude:common/navlist.tpl" navlistItems=$section accessKey=false labelColon=false}
  {/block}
{/foreach}

{if !$hasInfo}
  <div class="focal">
    No information for this {$item['type']}
  </div>
{/if}

{include file="findInclude:common/footer.tpl"}

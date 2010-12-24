{include file="findInclude:common/header.tpl"}

{include file="findInclude:modules/{$moduleID}/libraryName.tpl"}

{foreach $item['infoSections'] as $key => $section}
  {$sectionIsLast = $section@last}
  {$entries = array()}
  {foreach $section as $entry}
    {$itemIsLast = $entry@last}
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
  {/foreach}
  {block name="itemList"}
    {include file="findInclude:common/navlist.tpl" navlistItems=$section accessKey=false labelColon=false}
  {/block}
{/foreach}


{include file="findInclude:common/footer.tpl"}

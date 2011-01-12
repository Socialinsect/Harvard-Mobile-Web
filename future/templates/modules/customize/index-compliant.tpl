{extends file="findExtends:modules/{$moduleID}/index.tpl"}

{block name="customize"}
  <div class="nonfocal smallprint"> 
    Use the arrow buttons to customize the order of icons on your homepage, and the checkboxes to toggle visibility. Your changes will be automatically saved.
  </div> 
  
  <ul class="nav iconic" id="homepageList">
    {foreach $modules as $id => $info}
      <li id="{$id}">
        {if $info['disableable']}
          <input type="checkbox" onclick="toggle(this);"{if !$info['disabled']} checked="checked"{/if} />
        {/if}
        <span class="nolink" style="background-image: url(/modules/{$moduleID}/images/{$id}-tiny.png)">
          {$info['title']}
          <span class="nolinkbuttons"> 
            <a href="#" onclick="moveUp(this); return false;">
              <div class="moveup">&nbsp;</div>
            </a> 
            <a href="#" onclick="moveDown(this); return false;">
              <div class="movedown">&nbsp;</div>
            </a> 
          </span> 
        </span>                   
      </li>
    {/foreach}
  </ul>
  <div class="formbuttons">
    <a class="formbutton" href="../home/"><div>Return to Home</div></a>
  </div>
{/block}

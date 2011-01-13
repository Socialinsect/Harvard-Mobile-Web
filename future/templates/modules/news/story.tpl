{include file="findInclude:common/header.tpl"}

<div class="news">
  <h1 class="slugline">{$title}</h1>

  <div id="storysubhead">
    {include file="findInclude:common/share.tpl" urlToBeShared={$storyURL}
    shareRemark={$shareRemark} shareEmailUrl={$shareEmailURL}}
            
    {if $pager['pageNumber'] == 0}
        <p class="byline">
          {block name="byline"}
              
            {if $author}
              <span class="credit">by <span class="author">{$author}</span><br /></span>
            {/if}
    
            <span class="postdate">{$date}</span>
          {/block}
        </p>    
    {/if}        
  </div><!--storysubhead-->
  
  <div id="storybody">
    {if $pager['pageNumber'] == 0}
        {if isset($image)}
          <div id="image">
            <img class="thumbnail" src="{$image['src']}">
          </div>
        {/if}
    {/if}
    
    {include file="findInclude:common/pager.tpl"}
  </div><!--storybody-->
</div>

{include file="findInclude:common/footer.tpl"}

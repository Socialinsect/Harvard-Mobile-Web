<a name="search" />
<div class="nonfocal">
  <form method="get" action="search.php">
    <table class="search" width="100%">
      <tr>
        <th align="right" width="6em"><label for="keywords">Keywords:</label></th>
        <td class="wrap"><div><input type="text" id="keywords" name="keywords" value="{$keywords}" /></div></td>
      </tr>
      <tr>
        <th align="right" width="6em"><label for="title">Title:</label></th>
        <td class="wrap"><div><input type="text" id="title" name="title" value="{$title}" /></div></td>
      </tr>
      <tr>
        <th align="right" width="6em"><label for="author">Author:</label></th>
        <td class="wrap"><div><input type="text" id="author" name="author" value="{$author}" /></div></td>
      </tr>
      <tr>
        <th align="right" width="6em"><label for="format">Format:</label></th>
        <td><select id="format" name="format"">
          {foreach $formats as $key => $value}
            <option value="{$key}"{if strval($key) == $format} selected="selected"{/if}>{$value}</option>
          {/foreach}
        </select></td>
      </tr>
      <tr>
        <th align="right" width="6em"><label for="location">Location:</label></th>
        <td><select id="location" name="location">
          {foreach $locations as $key => $value}
            <option value="{$key}"{if strval($key) == $location} selected="selected"{/if}>{$value}</option>
          {/foreach}
         </select></td>
      </tr>
    </table>
    <input type="hidden" name="advanced" value="1" />
    {if $page == 'search'}
      {foreach $breadcrumbSamePageArgs as $arg => $value}
        <input type="hidden" name="{$arg}" value="{$value}" />
      {/foreach}
    {else}
      {foreach $breadcrumbArgs as $arg => $value}
        <input type="hidden" name="{$arg}" value="{$value}" />
      {/foreach}
    {/if}
    
    <p class="formbuttons">
      <span class="formbuttonclose">
        <input type="submit" class="formbutton" value="Search" />
      </span>
    </p>
 </form>
</div>

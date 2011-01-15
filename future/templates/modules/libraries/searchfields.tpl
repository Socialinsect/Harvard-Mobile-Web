<a name="search"></a>
<div class="nonfocal">
  <form method="get" action="search.php">
    <table class="search" width="100%">
      <tr>
        <th align="right"><label for="keywords">Keywords:</label></th>
        <td class="wrap"><div><input type="text" id="keywords" name="keywords" value="{$keywords|escape}" /></div></td>
      </tr>
      <tr>
        <th align="right"><label for="title">Title:</label></th>
        <td class="wrap"><div><input type="text" id="title" name="title" value="{$title|escape}" /></div></td>
      </tr>
      <tr>
        <th align="right"><label for="author">Author:</label></th>
        <td class="wrap"><div><input type="text" id="author" name="author" value="{$author|escape}" /></div></td>
      </tr>
      <tr>
        <th align="right"><label for="format">Format:</label></th>
        <td><select id="format" name="format"">
          {foreach $formats as $key => $value}
            <option value="{$key}"{if strval($key) == $format} selected="selected"{/if}>{$value}</option>
          {/foreach}
        </select></td>
      </tr>
      <tr>
        <th align="right"><label for="location">Location:</label></th>
        <td><select id="location" name="location">
          {foreach $locations as $key => $value}
            <option value="{$key}"{if strval($key) == $location} selected="selected"{/if}>{$value}</option>
          {/foreach}
         </select></td>
      </tr>
      <tr>
        <th align="right"><label for="pubDate">Pub Date:</label></th>
        <td><select id="pubDate" name="pubDate">
          {foreach $pubDates as $key => $value}
            <option value="{$key}"{if strval($key) == $pubDate} selected="selected"{/if}>{$value}</option>
          {/foreach}
         </select></td>
      </tr>
      <tr>
        <th></th>
        <td>
          <input type="checkbox" id="language" name="language" value="eng"{if $language == 'eng'} checked="checked"{/if} />
          <label for="language">English language only</label>
        </td>
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

<a name="search"></a>
<div class="nonfocal">
  <form method="get" action="search.php">
    <p>
      <label for="keywords">Keywords:</label><br/>
      <input type="text" id="keywords" name="keywords" value="{$keywords|escape}" />
    </p>
    <p>
      <label for="title">Title:</label><br/>
      <input type="text" id="title" name="title" value="{$title|escape}" />
    </p>
    <p>
      <label for="author">Author:</label><br/>
      <input type="text" id="author" name="author" value="{$author|escape}" />
    </p>
    <p>
      <label for="format">Format:</label><br/>
      <select id="format" name="format"">
        {foreach $formats as $key => $value}
          <option value="{$key}"{if strval($key) == $format} selected="selected"{/if}>{$value}</option>
        {/foreach}
      </select>
    </p>
    <p>
      <label for="pubDate">Pub Date:</label><br/>
      <select id="pubDate" name="pubDate">
        {foreach $pubDates as $key => $value}
          <option value="{$key}"{if strval($key) == $pubDate} selected="selected"{/if}>{$value}</option>
        {/foreach}
      </select>
    </p>
    <p>{* Location goes last because the RAZR will only display this last *}
      <label for="location">Location:</label><br/>
      <select id="location" name="location">
        {foreach $locations as $key => $value}
          <option value="{$key}"{if strval($key) == $location} selected="selected"{/if}>{$value}</option>
        {/foreach}
      </select>
    </p>
    <p>
      <input type="checkbox" id="language" name="language" value="eng"{if $language == 'eng'} checked="checked"{/if} />
      <label for="language">English language only</label>
    </p>
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
    <p>
      <input type="submit" class="formbutton" value="Search" />
    </p>
 </form>
</div>

{extends file="findExtends:common/footer.tpl"}

{block name="containerEnd"}
      </div> <!-- container -->
    </div> <!-- wrapper -->
  </div> <!--nonfooternav -->
{/block}

{block name="footer"}
  {if $moduleID != 'home'}
    <div id="footer">
      {$footerHTML}
    </div>
  {/if}
{/block}

{block name="deviceDetection"}
{/block}

{block name="belowContent"}
  <div id="footernav">
    <div id="navsliderwrapper">
      <div id="navslider">
        {if count($moduleNavList)}
          <div class="module spacer"></div>{* leftmost module spacer *}
        {/if}
        {foreach $moduleNavList as $item}
          {if !$item['separator']}
            <div class="module{if $item['class']} {$item['class']}{/if}">
              <a href="{$item['url']}">
                <img src="{$item['img']}" alt="{$item['title']}" />
                <br/>{$item['shortTitle']}
                {if isset($item['badge'])}
                  <span class="badge">{$item['badge']}</span>
                {/if}
              </a>
            </div>
          {/if}
        {/foreach}
      </div>
    </div>
    <div id="slideleft"></div>
    <div id="slideright"></div>
  </div>
{/block}

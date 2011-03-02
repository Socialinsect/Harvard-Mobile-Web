{include file="findInclude:common/header.tpl" scalable=false}

{$tabBodies = array()}

{capture name="mapPane" assign="mapPane"}
  {block name='scrollLink'}{$scrollLink = "#scrolldown"}{/block}
  {block name="mapPane"}
    {if $hasMap}
      <div id="mapwrapper">
        <div id="mapscrollers">
          <div id="nw">
            <a href="{$scrollLink}" onclick="scroll('nw'); ">
              <img src="/common/images/blank.png" width="50" height="50" alt="NW" />
            </a>
          </div>
          <div id="n">
            <a href="{$scrollLink}" onclick="scroll('n'); ">
              <img src="/common/images/blank.png" width="50" height="50" alt="N" />
            </a>
          </div>
          <div id="ne">
            <a href="{$scrollLink}" onclick="scroll('ne'); ">
              <img src="/common/images/blank.png" width="50" height="50" alt="NE" />
            </a>
          </div>
          <div id="e">
            <a href="{$scrollLink}" onclick="scroll('e'); ">
              <img src="/common/images/blank.png" width="50" height="50" alt="E" />
            </a>
          </div>
          <div id="se">
            <a href="{$scrollLink}" onclick="scroll('se'); ">
              <img src="/common/images/blank.png" width="50" height="50" alt="SE" />
            </a>
          </div>
          <div id="s">
            <a href="{$scrollLink}" onclick="scroll('s'); ">
              <img src="/common/images/blank.png" width="50" height="50" alt="S" />
            </a>
          </div>
          <div id="sw">
            <a href="{$scrollLink}" onclick="scroll('sw'); ">
              <img src="/common/images/blank.png" width="50" height="50" alt="SW" />
            </a>
          </div>
          <div id="w">
            <a href="{$scrollLink}" onclick="scroll('w'); ">
              <img src="/common/images/blank.png" width="50" height="50" alt="W" />
            </a>
          </div>
          <img id="loadingimage" src="/common/images/loading2.gif" width="40" height="40" alt="Loading" />
        </div> <!-- id="mapscrollers" -->
        <img src="/common/images/blank.png" id="mapimage" width="{$imageWidth}" height="{$imageHeight}" alt="" onload="hide('loadingimage')"/> 
        <div id="mapzoom">
          <a href="#" onclick="zoomin(); return false;" id="zoomin">
            <img src="/common/images/blank.png" width="40" height="34" alt="Zoom In" />
          </a>
          <a href="#" onclick="zoomout(); return false;" id="zoomout">
            <img src="/common/images/blank.png" width="40" height="34" alt="Zoom Out" />
          </a>
          <a href="#" onclick="recenter(); return false;" id="recenter">
            <img src="/common/images/blank.png" width="40" height="34" alt="Recenter" />
          </a>
          {if $showFullscreen}
          <a href="" id="fullscreen">
            <img src="/common/images/blank.png" width="40" height="34" alt="Full Screen" />
          </a>
          {/if}
        </div>
      </div>
    {else}
      <img id="mapimage" width="{$imageWidth}" height="{$imageHeight}" alt="" onload="hide('loadingimage')" src="{$imageUrl}"/> 
    {/if}
  {/block}
{/capture}
{$tabBodies['map'] = $mapPane}

{capture name="photoPane" assign="photoPane"}
  {block name="photoPane"}
    <img id="loadingimage2" src="/common/images/loading2.gif" width="40" height="40" alt="Loading" />
    <img id="photo" src="" width="99.9%" alt="{$name} Photo" onload="hide('loadingimage2')" />
  {/block}
{/capture}
{$tabBodies['photo'] = $photoPane}

{capture name="detailPane" assign="detailPane"}
  {block name="detailPane"}
    {include file="findInclude:common/navlist.tpl" navlistItems=$details boldLabels=true accessKey=false}
  {/block}
{/capture}
{$tabBodies['detail'] = $detailPane}

{block name="tabView"}
  <a name="scrolldown"> </a>		
	<div class="focal shaded">

		<h2>{$name}</h2>
		<p class="address">{$address|replace:' ':'&shy; '}</p>	
    {include file="findInclude:common/tabs.tpl" tabBodies=$tabBodies}
  </div>
{/block}

{include file="findInclude:common/footer.tpl"}

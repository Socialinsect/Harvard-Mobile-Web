
function getCSSHeight(elem) {
  return elem.offsetHeight
    - parseFloat(getCSSValue(elem, 'border-top-width')) 
    - parseFloat(getCSSValue(elem, 'border-bottom-width'))
    - parseFloat(getCSSValue(elem, 'padding-top'))
    - parseFloat(getCSSValue(elem, 'padding-bottom'))
    - parseFloat(getCSSValue(elem, 'margin-top'))
    - parseFloat(getCSSValue(elem, 'margin-bottom'));
}

function moduleHandleWindowResize() {
  var blocks = document.getElementById('fillscreen').childNodes;
  
  for (var i = 0; i < blocks.length; i++) {
    var blockborder = blocks[i].childNodes[0];
    if (!blockborder) { continue; }
      
    var clipHeight = blocks[i].offsetHeight
      - parseFloat(getCSSValue(blockborder, 'border-top-width')) 
      - parseFloat(getCSSValue(blockborder, 'border-bottom-width'))
      - parseFloat(getCSSValue(blockborder, 'padding-top'))
      - parseFloat(getCSSValue(blockborder, 'padding-bottom'))
      - parseFloat(getCSSValue(blockborder, 'margin-top'))
      - parseFloat(getCSSValue(blockborder, 'margin-bottom'));
    
    blockborder.style.height = clipHeight+'px';
    
    // If the block ends in a list, clip off items in the list so that 
    // we don't see partial items
    if (blockborder.childNodes.length < 2) { continue; }
    var blockheader = blockborder.childNodes[0];
    var blockcontent = blockborder.childNodes[1];
    
    if (!blockcontent.childNodes.length) { continue; }
    var last = blockcontent.childNodes[blockcontent.childNodes.length - 1];
    
    if (last.nodeName == 'UL') {
      var listItems = last.childNodes;
      for (var j = 0; j < listItems.length; j++) {
        listItems[j].style.display = 'list-item'; // make all list items visible
      }
      
      // How big can the content be?
      var contentClipHeight = clipHeight - blockheader.offsetHeight;
  
      var k = listItems.length - 1;
      while (getCSSHeight(blockcontent) > contentClipHeight) {
        listItems[k].style.display = 'none';
        if (--k < 0) { break; } // hid everything, stop
      }
    }
  }
}

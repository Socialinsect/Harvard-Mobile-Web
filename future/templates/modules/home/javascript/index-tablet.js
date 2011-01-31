
function moduleHandleWindowResize() {
  var blocks = document.getElementById('fillscreen').childNodes;
  
  for (var i = 0; i < blocks.length; i++) {
    var blockborder = blocks[i].childNodes[0];
    if (!blockborder) { continue; }
      
    var height = blocks[i].offsetHeight
      - parseFloat(getCSSValue(blockborder, 'border-top-width')) 
      - parseFloat(getCSSValue(blockborder, 'border-bottom-width'))
      - parseFloat(getCSSValue(blockborder, 'padding-top'))
      - parseFloat(getCSSValue(blockborder, 'padding-bottom'))
      - parseFloat(getCSSValue(blockborder, 'margin-top'))
      - parseFloat(getCSSValue(blockborder, 'margin-bottom'));
    
    blockborder.style.height = height+'px';
    
    
    
  }
}

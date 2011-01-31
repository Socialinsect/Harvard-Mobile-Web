// Initalize the ellipsis event handlers
/*clipWithEllipsis(function () {
    
    var elems = [];
    for (var i = 0; i < 100; i++) { // cap at 100 divs to avoid overloading phone
        var elem = document.getElementByTagName('ellipsis_'+i);
        if (!elem) { break; }
        elems[i] = elem;
    }
    return elems;
});*/


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

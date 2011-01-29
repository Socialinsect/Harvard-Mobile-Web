// Initalize the ellipsis event handlers
clipWithEllipsis(function () {
    var elems = [];
    for (var i = 0; i < 100; i++) { // cap at 100 divs to avoid overloading phone
        var elem = document.getElementById('ellipsis_'+i);
        if (!elem) { break; }
        elems[i] = elem;
    }
    return elems;
});

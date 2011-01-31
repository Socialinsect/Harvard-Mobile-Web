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

function toggleBookmark(elem, id, cookie) {
  var bookmarks = getBookmarks(cookie);
  var index = bookmarks.indexOf(id);
  
  if (elem.className == 'bookmarked') {
    if (index != -1) {
      bookmarks.splice(index, 1);
    }
    elem.className = '';
  } else {
    if (index == -1) {
      bookmarks.push(id);
    }
    elem.className = 'bookmarked';
  }
  setBookmarks(bookmarks, cookie);
}

function getBookmarks(cookie) {
  var bookmarkString = getCookie(cookie);
  if (bookmarkString.length) {
    return bookmarkString.split(',');
  } else {
    return new Array();
  }
}

function setBookmarks(bookmarks, cookie) {
  setCookie(cookie, bookmarks.join(','));
}

function setLocationDistances(locations) {
  function toRadians(coord) {
    return coord * Math.PI / 180;
  }
  
  if (typeof navigator.geolocation == 'undefined') return;
  
  navigator.geolocation.getCurrentPosition(function (location) {
    var curLat = location.coords.latitude;
    var curLon = location.coords.longitude;
    var earthRadius = 6371; // km
    var milesPerKM = 1.609344000000865;
    
    for (var id in locations) {
      var elem = document.getElementById('location_'+id);
      if (!elem) { break; }
      
      var locLat = locations[id]['lat'];
      var locLon = locations[id]['lon'];
      
      // law of haversines
      var dLat = toRadians(locLat-curLat);
      var dLon = toRadians(locLon-curLon); 
      var a = Math.sin(dLat/2) * Math.sin(dLat/2) +
              Math.cos(toRadians(curLat)) * Math.cos(toRadians(locLat)) * 
              Math.sin(dLon/2) * Math.sin(dLon/2); 
      var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a)); 
      var km = earthRadius * c;
      
      elem.innerHTML = Math.round(km*milesPerKM*10)/10 +' miles away';
    }
  });
}

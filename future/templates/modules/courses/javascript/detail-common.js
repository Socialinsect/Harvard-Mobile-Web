function toggleMyClasses(objTrigger, classData) {
// Toggles the visible state of the object objTrigger on or off using CSS
	if(objTrigger.className == "ms_on") {
		objTrigger.className = "ms_off";
		removeMyClasses(classData);
	} else if(objTrigger.className == "ms_off") {
		objTrigger.className = "ms_on";
		addMyClasses(classData);
	}
}

function addMyClasses(classData) {
	var str = getMyClasses();
	if (str != "") {
		var arr = str.split(",");
	} else {
		var arr = new Array();
	}
	arr.push(classData.replace(/ /g, "+"));
	setMyClasses(arr.join());
}

function removeMyClasses(classData) {
	var arr = unescape(getMyClasses()).split(",");
	var arr2 = new Array();
	for (elt in arr) {
		if (!(arr[elt] == classData.replace(/ /g, "+")) && !(arr[elt] == "")) {
			arr2.push(arr[elt]);
		}
	}
	setMyClasses(arr2.join());
}

function getMyClasses() {
  return getCookie(MY_CLASSES_COOKIE);
}

function setMyClasses(myClasses) {
  setCookie(MY_CLASSES_COOKIE, myClasses, MY_CLASSES_COOKIE_DURATION, COOKIE_PATH);
}

.. _modules_libraries:

**********************
Libraries
**********************

The Libraries module provides an interface to the HOLLIS catalogs, as well as other library-related information. Specifically:

* Library and Archive Locations and Hours
* Links to mobile research resources and the Ask a Librarian service.
* Basic and advanced searches of HOLLIS.


=======
iPhone
=======

-----------------
Facility information
-----------------

The iPhone app retrieves library and archive locations and hours by making call to the libraries API with the simple command 'libraries':

	http://m.harvard.edu/api/?module=libraries&command=libraries

The server will check the library cache, and if it is not available or too old, it will get institution information from the url specified in URL_LIBRARIES_INFO in /site/Harvard/config.ini. Using that information, it will return a JSON array containing a dictionary with information about  each library. Here is an example of a library information dictionary:

.. code-block:: javascript

{
  "name": "Afro-American Studies Reading Room",
  "primaryname": "Afro-American Studies Reading Room",
  "id": "0003",
  "type": "library",
  "address": "Barker Center, 12 Quincy Street, Cambridge,MA 02138",
  "latitude": "42.372601",
  "longitude": "-71.114521",
  "hrsOpenToday": "9am - 5pm",
  "isOpenNow": false
}

When detail about a specific library is needed, an API call is made with the command libdetail and specifics about the library in question. For example:

	http://m.harvard.edu/api/?name=Afro-American+Studies+Reading+Room&id=0003&module=libraries&command=libdetail

The server would then consult its cache and return something along the lines of:

.. code-block:: javascript

{
  "name": "Afro-American Studies Reading Room",
  "primaryname": "Afro-American Studies Reading Room",
  "id": "0003",
  "type": "library",
  "address": "Barker Center, 12 Quincy Street, Cambridge,MA 02138",
  "directions": "",
  "longitude": "-71.114521",
  "latitude": "42.372601",
  "website": "",
  "email": "",
  "phone": [
    {
      "description": "Desk",
      "number": "617-495-4104"
    }
  ],
  "weeklyHours": [
    {
      "date": "20110112",
      "day": "Wednesday",
      "hours": "9am - 5pm"
    },
    {
      "date": "20110113",
      "day": "Thursday",
      "hours": "9am - 5pm"
    },
    {
      "date": "20110114",
      "day": "Friday",
      "hours": "9am - 5pm"
    },
    {
      "date": "20110115",
      "day": "Saturday",
      "hours": "closed"
    },
    {
      "date": "20110116",
      "day": "Sunday",
      "hours": "closed"
    },
    {
      "date": "20110117",
      "day": "Monday",
      "hours": "9am - 5pm"
    },
    {
      "date": "20110118",
      "day": "Tuesday",
      "hours": "9am - 5pm"
    }
  ],
  "isOpenNow": false,
  "hrsOpenToday": "9am - 5pm",
  "hoursOfOperationString": ""
}

The iPhone app retrieves information about archives in the same way, except that it uses the commands 'archive' and archivedetail'.

-------------------------
HOLLIS
-------------------------

The iPhone app runs simple searches using the 'search' command and a keywords parameter.

	http://mobile-dev.harvard.edu/api/?keywords=dogs&module=libraries&command=search

It runs advanced searches the same way, except with additional parameters, like so:

	http://m.harvard.edu/api/?keywords=cats&module=libraries&format=matManuscript&command=search

The possible parameters are:

	keywords (space-seprated list of keywords)
	title
	author
	location (library/archive location code)
	format (format code)
	pubDate (YYYY-YYYY, 4 digit year range)
	language (language code)
	
The iPhone app gets the location codes, format codes, and valid pubDate ranges are retrieved with this server request:

	http://m.harvard.edu/api/?module=libraries&command=searchcodes
	
The codes are returned as a dictionary with the keys 'formats', 'locations', and 'pubDates'. The values of those keys are dictionaries with codes and values like so:

.. code-block:: javascript

"formats": {
    "matBook": "Book",
    "matMagazine": "Journal \/ Serial",
    "matManuscript": "Archives \/ Ms.",
    "matSheetMusic": "Music Score",
    "matRecording": "Sound Recording",
    "matMovie": "Video \/ Film",
    "matMap": "Map",
    "matPhoto": "Image",
    "matComputerFile": "Computer file \/ Data",
    "matObjects": "Object"
  }

The search results from the search command are a dictionary containing details about the result set and an 'items' value, which is an array of dictionaries, each of which represents a search result. For example:

.. code-block:: javascript

{
  "q": "cats",
  "total": "218",
  "start": "1",
  "end": "25",
  "pagesize": "25",
  "items": [
    {
      "index": "1",
      "itemId": "012209090",
      "creator": "Landesman, Eyal.",
      "nonLatinCreator": "",
      "title": "Cats",
      "nonLatinTitle": "",
      "date": "",
      "format": {
        "formatDetail": "Archives \/ Manuscripts"
      },
      "edition": ""
    },
	...

  ]
}

That provides the information that the iPhone app provides in the view listing the search results. When a specific search result is tapped, the app requests the availability and details for the item using the itemavailabilitysummary and itemdetail commands, along with the itemId as a parameter. Example:

	http://mobile-dev.harvard.edu/api/?itemId=011330287&module=libraries&command=itemavailabilitysummary

	http://mobile-dev.harvard.edu/api/?itemId=011330287&module=libraries&command=itemdetail

The response to availability request will list the institutions at which the item is available and specifics about its availability, like this:

.. code-block:: javascript

{
  "id": "011330287",
  "institutions": [
    {
      "id": "0015",
      "type": "library",
      "name": "Cabot Science",
      "categories": [
        {
          "holdingStatus": "28-day loan",
          "available": 1,
          "requestable": 0,
          "unavailable": 0,
          "collection": 0,
          "total": 1
        }
      ]
    }
  ]
}

The response to the detail request will look like this:

.. code-block:: javascript

{
  "itemId": "011330287",
  "title": "Dogs : a natural history",
  "nonLatinTitle": "",
  "nonLatinCreator": "",
  "creator": "Page, Jake.",
  "creatorLink": "authorname:\"Page, Jake\"",
  "publisher": "New York : Smithsonian Books\/Collins,",
  "date": "2007",
  "format": {
    "type": "extent",
    "typeDetail": "xxii, 228 p.",
    "formatDetail": "Book"
  },
  "edition": "1st ed.",
  "identifier": [
    
  ],
  "numberofimages": 0,
  "worktype": "",
  "thumbnail": "",
  "cataloglink": "http:\/\/hollis.harvard.edu\/accessible.ashx?itemid=%7Clibrary%2Fm%2Faleph%7C011330287",
  "fullimagelink": ""
}

==============
Services Used
==============

The services used by the API are defined in /site/Harvard/config.ini. As of now, they are:

URL_LIBRARIES_OPTS = "http://webservices.lib.harvard.edu/rest/hollis/search/opts"
URL_LIBRARIES_INFO = "http://faulkner.hul.harvard.edu:9020/rest/lib/info"
URL_LIB_DETAIL_BASE = "http://faulkner.hul.harvard.edu:9020/rest/lib/library/"
URL_ARCHIVE_DETAIL_BASE = "http://faulkner.hul.harvard.edu:9020/rest/lib/archive/"
URL_LIBRARIES_SEARCH_BASE = "http://faulkner.hul.harvard.edu:9020/rest/hollis/search/dc/?"
URL_LIBRARIES_ITEM_RECORD_BASE = "http://faulkner.hul.harvard.edu:9020/rest/hollis/mobileRec/"
URL_LIBRARIES_AVAILABILITY_BASE = "http://faulkner.hul.harvard.edu:9020/rest/hollis/avail/"

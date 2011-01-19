<?php

require_once realpath(LIB_DIR.'/DiskCache.php');
require_once realpath(LIB_DIR.'/feeds/html2text.php');

define('MISC_CLASSES_SUFFIX', ' - other');

function compare_courseNumber($a, $b)
{
  return strnatcmp($a['name'], $b['name']);
}

function compare_schoolName($a, $b)
{
  return strcmp($a['school_name_short'], $b['school_name_short']);
}

class MeetingTime {
  const SUN = 1;
  const MON = 2;
  const TUES = 3;
  const WED = 4;
  const THURS = 5;
  const FRI = 6;
  const SAT = 7;
  
  private $days;
  private $startTime;
  private $endTime;
  private $location = NULL;

  function __construct($daysArr, $startTime, $endTime, $location) {
    $this->days = $daysArr;
    $this->startTime = $startTime;
    $this->endTime = $endTime;
    $this->location = $location;
  }

  static function cmp($a, $b) {
    if ($a->startTime == $b->startTime) {
      return 0;
    }
    return ($a > $b) ? 1 : -1;
  }

  public function isLocationKnown() {
    return !is_null($this->location);
  }
  
  public function daysText() {
    // For use when we have multiple days for the same lecture
    $shortVersions = array(MeetingTime::SUN => "Su", MeetingTime::MON => "M",
                           MeetingTime::TUES => "Tu", MeetingTime::WED => "W",
                           MeetingTime::THURS => "Th", MeetingTime::FRI => "F",
                           MeetingTime::SAT => "Sa");
    // For use when we have one day for a given lecture
    $longerVersions = array(MeetingTime::SUN => "Sun", MeetingTime::MON => "Mon",
                            MeetingTime::TUES => "Tue", MeetingTime::WED => "Wed",
                            MeetingTime::THURS => "Thu", MeetingTime::FRI => "Fri",
                            MeetingTime::SAT => "Sat");

    $textMapping = (count($this->days) > 1) ? $shortVersions : $longerVersions;
    $daysTextArr = array();
    foreach ($this->days as $day) {
      $daysTextArr[] = $textMapping[$day];
    }
    
    return implode(" ", $daysTextArr);
  }
  
  public function timeText() {
    // If they're both AM or PM, the start time doesn't need it's own "am"/"pm"
    if (strftime("%p", $this->startTime) == strftime("%p", $this->endTime)) {
      $startTimeFormat = "%l:%M";
    }
    else {
      $startTimeFormat = "%l:%M%p";
    }

    // strftime is adding a trailing space.  I have no idea why.  But we trim.
    $text = trim(strftime($startTimeFormat, $this->startTime)) . "-" .
            trim(strftime("%l:%M%p", $this->endTime));

    // I know, %P should return lowercase... but it's returning "A" or "P"
    return strtolower($text);
  }
  
  public function daysAndTimeText() {
      return $this->daysText() . " " . $this->timeText();
  }
  
  public function locationText() {
    return ($this->location == null) ? "TBA" : $this->location;
  }
}


/* Scenarios we've seen:
 * 
 * 1. Single time and location.  Days often come as one concatanated word...
 *    Ex: MondayWednesday 1:00 p.m. - 2:30 p.m.
 *
 * 2. Multiple times and locations:
 *    MondayTuesdayWednesdayThursday Monday Tuesday Wednesday Thursday 9:00 
 *    a.m. -10:00 a.m.; Monday Tuesday Wednesday Thursday 11:00 a.m. -12:00 
 *    p.m.; Monday Tuesday Wednesday Thursday 10:00 a.m. -11:00 a.m.
 * 
 */

class MeetingTimesParseException extends Exception { }

class MeetingTimes {
  // If we run into errors while parsing, we'll fall back to just echoing this.
  private $rawTimesText;
  private $rawLocationsText;

  private $parseSucceeded = false;
  private $meetingTimes = array();
  
  function __construct($timesText, $locationsText) {
    $this->rawTimesText = $timesText;
    $this->rawLocationsText = $locationsText;
    $this->parse();
  }
  
  public function all() {
    return $this->meetingTimes;
  }

  public function rawTimesText() { return $this->rawTimesText; }
  public function rawLocationsText() { return $this->rawLocationsText; }
  public function parseSucceeded() { return $this->parseSucceeded; }
  
  // Converts to something we can serialize in JSON, an array of time/location
  // pairs.
  public function toArray()
  {
    if (!$this->parseSucceeded())
      return array();
    
    $serialized = array();
    foreach ($this->all() as $meetingTime) {
      $meetingTimeEntry = array("days" => $meetingTime->daysText(),
                                "time" => $meetingTime->timeText());
      if ($meetingTime->isLocationKnown()) {
        $meetingTimeEntry["location"] = $meetingTime->locationText();
      }
      else {
        $meetingTimeEntry["location"] = "";
      }
      $serialized[] = $meetingTimeEntry;
    }
    
    return $serialized;
  }
  
  private function parse() {
    $rawTimesArr = explode(";", $this->rawTimesText);
    $rawLocationsArr = explode(",", $this->rawLocationsText);

    // Sometimes a comma is really one location, like "HBS, Cumnock Hall 230",
    // so if there's only one time and multiple locations, that it's really
    // one location that has a bunch of commas in it.  (Sometimes 2 or 3).
    if (count($rawTimesArr) == 1) {
      $rawLocationsArr = array($this->rawLocationsText);
    }

    if (count($rawTimesArr) != count($rawLocationsArr)) {
      return; // Something's gone south here, handle it semi-gracefully.
    }

    try {
      $i = 0;
      foreach ($rawTimesArr as $timesText) {
        $days = $this->parseDaysFromStr($timesText);
        $startTime = $this->parseStartTimeFromStr($timesText);
        $endTime = $this->parseEndTimeFromStr($timesText);
        $location = $this->parseLocationFromStr($rawLocationsArr[$i]);
      
        $this->meetingTimes[] = new MeetingTime($days, $startTime, $endTime, $location);
      }
      usort($this->meetingTimes, array("MeetingTime", "cmp"));
      $this->parseSucceeded = true;
    }
    catch (MeetingTimesParseException $e) {
      if (!is_array($rawTimesArr) || count($rawTimesArr) != 1 || trim($rawTimesArr[0]) != 'tbd') {
        // Don't warn on 'tbd' text used as placeholder before times are set.
        error_log($e->getMessage());
      }
    }
  }
  
  /*
   * Accepts: String like: MondayTuesdayWednesdayThursday Monday Tuesday 
   *                       Wednesday Thursday 9:00 a.m. - 10:00 a.m.;
   *          Or: MondayTuesdayWednesdayThursday 9:00 a.m. - 10:00 a.m.
   *
   * Returns: Sorted array of MeetingTime date constants like MeetingTime::MON. 
   *          Strips duplicates.
   */
  private function parseDaysFromStr($timeStr) {
    $abbrevs = array("Sun" => MeetingTime::SUN, "Mon" => MeetingTime::MON,
                     "Tues" => MeetingTime::TUES, "Wed" => MeetingTime::WED,
                     "Thurs" => MeetingTime::THURS, "Fri" => MeetingTime::FRI,
                     "Sat" => MeetingTime::SAT);
    $days = array();
    foreach ($abbrevs as $abbrev => $day) {
      if (stristr($timeStr, $abbrev)) {
        $days[] = $day;
      }
    }
    if (count($days) == 0) {
      throw new MeetingTimesParseException("No days found.");
    }
    sort($days);

    return $days;
  }
  
  private function parseTimeFromStr($timeStr, $index) {
    $timeParts = explode("-", $timeStr);
    if (count($timeParts) != 2) {
      throw new MeetingTimesParseException("Time format unrecognized");
    }
    return strtotime($timeParts[$index]);
  }
  
  private function parseStartTimeFromStr($timeStr) {
    return $this->parseTimeFromStr($timeStr, 0);
  }
  
  private function parseEndTimeFromStr($timeStr) {
    return $this->parseTimeFromStr($timeStr, 1);
  }

  private function parseLocationFromStr($locationStr) {
    if (is_null($locationStr) || 
        trim($locationStr) == "" ||
        strcasecmp("TBD", $locationStr) == 0 || 
        strcasecmp("TBA", $locationStr) == 0) {
      return NULL;
    }

    return trim($locationStr);
  }
}


class CourseData {
  private static $cache = null;
  
  private static function addTermQueryToArgs(&$args, $term=null) {
    if (!isset($term)) {
      $term = self::get_term();
    }
    $termParts = explode(' ', $term);
    if ($termParts > 1) {
      $semesterYr = '';
      switch (strtolower($termParts[0])) {
        case 'winter':
          $semesterYr = "Jan {$termParts[1]} (Winter Session)";
          break;
        case 'spring':
          $semesterYr = "Jan to May {$termParts[1]} (Spring Term)";
          break;
        case 'summer':
          $semesterYr = "Jun to Aug {$termParts[1]} (Summer Term)";
          break;
        case 'fall':
          $semesterYr = "Sep to Dec {$termParts[1]} (Fall Term)";
          break;
        
        default:
          return;
      }

      $args['fq_coordinated_semester_yr'] = 'coordinated_semester_yr:"'.$semesterYr.'"';
    }
  }
  
  private static function addSchoolQueryToArgs(&$args, $school) {
    $args['fq_school_nm'] = 'school_nm:"'.$school.'"';
  }
  
  private static function addCategoryQueryToArgs(&$args, $category=null) {
    if (isset($category) && strlen($category)) {
      $args['fq_dept_area_category'] = 'dept_area_category:"'.$category.'"';
    } else {
      $args['fq_dept_area_category'] = 'dept_area_category:[* TO ""]';
    }
  }

  private static function getCoursesCache() {
    if (!isset(self::$cache)) {
      self::$cache = new DiskCache(
        $GLOBALS['siteConfig']->getVar('COURSES_CACHE_DIR'), 
        $GLOBALS['siteConfig']->getVar('COURSES_CACHE_TIMEOUT'), TRUE);
      self::$cache->preserveFormat();
    }
    
    return self::$cache;
  }

  private static function query($cacheName, $baseUrlKey, $args) {
    $cache = self::getCoursesCache();
    $results = '';
    
    if (!$cache->isFresh($cacheName)) {
      $url = $GLOBALS['siteConfig']->getVar($baseUrlKey).http_build_query($args);
    
      $results = file_get_contents($url);
      //error_log("COURSES DEBUG: " . $url);
      if ($results) {
        $cache->write($results, $cacheName);
      } else {
        error_log("Failed to read contents from $url, reading expired cache");
      }
    }

    if (!$results) {
      $results = $cache->read($cacheName);
    }
    
    return $results;
  }
  
  private static function stripMiscClassesSuffix($school, $course) {
    // strip off any suffix present
    if (substr($course, -1*strlen(MISC_CLASSES_SUFFIX)) == MISC_CLASSES_SUFFIX) {
      $course = substr($course, 0, strlen($course) - strlen(MISC_CLASSES_SUFFIX));
    }
    
    // Sometimes the course is the short version of a school name.
    // if so, get the long name
    $data = self::get_schoolsAndCourses();
    
    foreach ($data as $schoolData) {
      if ($schoolData['school_name'] == $school) {
        if ($course == $schoolData['school_name_short']) {
          $course = $schoolData['school_name'];
        }
        break;
      }
    }
    
    return array($school, $course);
  }

  private static function clean_text($text) {
    $text = str_replace(chr(194), '', $text);
    $text = str_replace(chr(160), ' ', $text);
    return trim(preg_replace('/\s+/', ' ', $text));
  }

  private static function getTag($xml_obj, $tag) {
    $list = $xml_obj->getElementsByTagName($tag);
    if($list->length == 0) {
      throw new Exception("no elements of type $tag found");
    }
    /*
    if($list->length > 1) {
      throw new Exception("elements of type $tag not unique, {$list->length} found");
    }
    */
    return $list->item(0);
  }

  private static function getTagVal($xml_obj, $tag) {
    return self::getTag($xml_obj, $tag)->nodeValue;
  }

  private static function getTagVals($xml_obj, $tag) {
    $nodes = $xml_obj->getElementsByTagName($tag);
    $vals = array();
    foreach($nodes as $node) {
      $vals[] = $node->nodeValue;
    }
    return $vals;
  }

  private static function getStaff($staff_xml, $type) {
    $child = $staff_xml->getElementsByTagName($type);
    if($child->length == 1) {
      return self::getTagVals($child->item(0), 'fullName');
    } else {
      return array();
    }
  }
  
  private static function getInstructorsFromDescription($description) {
    // Need to split on ", and", ", " and " and " because the 
    // instructor string is in the following format:
    //      One instructor: "John Doe"
    //     Two instructors: "John Doe and Jane Doe"
    //   Three instructors: "John Doe, Jane Doe, and John Smith"
    //    Four instructors: "John Doe, Jane Doe, John Smith, and Jane Smith"
    if (strlen(trim($description))) {
      $description = str_replace('and ', ',', str_replace(', and ', ',', trim($description)));
      
      return array_map('trim', explode(',', $description));
    }
    return array();
  }

  public static function get_term_data() {
    $month = (int) date('m');
    AcademicCalendar::init();
    return array(
      "year" => date('y'),
      "season" => AcademicCalendar::get_term(),
      //"season" => ($month <= 7) ? 'sp' : 'fa'
    );
  }

  public static function get_term() {
    //$data = self::get_term_data();
    //return $data["season"] . $data["year"];
      return $GLOBALS['siteConfig']->getVar('COURSES_CURRENT_TERM');
  }

  public static function get_term_text() {
    $data = self::get_term_data();
    $seasons = array(
      'sp' => 'Spring',
      'fa' => 'Fall',
      'ia' => 'IAP',
      'su' => 'Summer',
      );
    return $seasons[ $data["season"] ] . " 20" . $data["year"];
  }

  public static function get_subject_details($subjectId) {
    $args = array('q' => 'id:'.$subjectId);
    
    self::addTermQueryToArgs($args);
    
    $xml = self::query("Course-{$subjectId}.xml", 'COURSES_BASE_URL', $args);
    if($xml == "") {
      // if failed to grab xml feed, then run the generic error handler
      throw new DataServerException('COULD NOT GET XML');
    }

    $xml_obj = simplexml_load_string($xml);

    $subject_array = array();
    $single_course = $xml_obj->courses->course;
    $subject_fields = array();
    $subject_fields['name'] = strval($single_course->course_number);
    $subject_fields['masterId'] = strval($single_course['id']);
    $subject_fields['title'] = strval($single_course->title);
    $subject_fields['description'] = HTML2TEXT(strval($single_course->description));

    $subject_fields['preReq'] = strval($single_course->prereq);
    $subject_fields['credits'] = strval($single_course->credits);
    $subject_fields['cross_reg'] = strval($single_course->crossreg);
    $subject_fields['exam_group'] = strval($single_course->exam_group);
    $subject_fields['department'] = strval($single_course->department);
    $subject_fields['school'] = strval($single_course->school_name);
    //$subject_fields['term'] = strval($single_course->term_description);
    $subject_fields['term'] = self::get_term();
    $subject_fields['url'] = strval($single_course->url);

    $classtime['title'] = 'Lecture';
    $classtime['location'] = strval($single_course->location);

    $classtime['time'] = strval($single_course->meeting_time);

    $classtime_array[] = $classtime;

    $subject_fields['times'] = $classtime_array;

    // Reimplementation using crazier parsing
    $subject_fields['meeting_times'] = new MeetingTimes($single_course->meeting_time,
                                                        $single_course->location);

    $ta_array = array();
    $staff['instructors'] = self::getInstructorsFromDescription($single_course->faculty_description);
    $staff['tas'] = $ta_array;
    $subject_fields['staff'] = $staff;

    $announ['unixtime'] = time();
    $announ['title'] = 'Announcement1';
    $announ['text'] = 'Details of Announcement1';
    $announ_array[] = $announ;
    $subject_fields['announcements'] = $announ_array;

    $subject_array = $subject_fields;
    $subjectDetails = $subject_array;
    
    return $subjectDetails;
  }

  public static function get_subjectsForCourse($course, $school) {
    list($school, $course) = self::stripMiscClassesSuffix($school, $course);
  
    $args = array(
      'start' => 0, // start must be first
    );
    self::addTermQueryToArgs($args);
    
    if (strlen($school)) {
      self::addSchoolQueryToArgs($args, $school);
    }
    
    if (strlen($course)) {
      if ($course == $school) {
        self::addCategoryQueryToArgs($args);
      } else {
        self::addCategoryQueryToArgs($args, $course);
        $args['ignored'] = 'butNeeded';
      }
    }

    $xml = self::query("$course-$school-0.xml", 'COURSES_BASE_URL', $args);
    if($xml == "") {
      // if failed to grab xml feed, then run the generic error handler
      throw new DataServerException('COULD NOT GET XML');
    }

    $xml_obj = simplexml_load_string($xml);
    $count = $xml_obj->courses['numFound']; // Number of Courses Found

    $iterations = ($count/25);

    $subject_array = array();
    for ($index = 0; $index < $iterations; $index = $index + 1) {
      if ($index > 0) {
        //printf(" Current = %d\n",$index*25);
        $args['start'] = $index * 25;
        
        $xml = self::query("$course-$school-$index.xml", 'COURSES_BASE_URL', $args);
        if($xml == "") {
          // if failed to grab xml feed, then run the generic error handler
          throw new DataServerException('COULD NOT GET XML');
        }
     
        $xml_obj = simplexml_load_string($xml);
        // $nbr = 1;
      }    
    
      foreach($xml_obj->courses->course as $single_course) {
        $subject_fields = array();
        
        /* Is this actually needed?
        $num = strval($single_course->course_number);
        if (ctype_alpha(str_replace(' ', '', $num)) || (substr($num, 0, 1) == '0')) {
          $num = '0'.$num;
        }*/
        
        $subject_fields['name'] = strval($single_course->course_number);
        $subject_fields['masterId'] = strval($single_course['id']);
        $subject_fields['title'] = strval($single_course->title);
        $subject_fields['term'] = self::get_term();
        
        $ta_array = array();
        $staff['instructors'] = self::getInstructorsFromDescription($single_course->faculty_description);
        $staff['tas'] = $ta_array;
        $subject_fields['staff'] = $staff;
        
        $subject_array[] = $subject_fields;
      }
    }

    usort($subject_array, 'compare_courseNumber');
    
    foreach($subject_array as $i => $subject) {
      if (substr($subject["name"], 0, 1) == '0') {
        $subject_array[$i]["name"] = substr($subject["name"], 1);
      }
    }
    return $subject_array;
  }


  // returns the Schools (Course-Group) to Departmetns (Courses) map
  public static function get_schoolsAndCourses() {
    // $filenm = $GLOBALS['siteConfig']->getVar('COURSES_CACHE_DIR'). '/SchoolsAndCourses' .'.xml';
    $cacheName = 'SchoolsAndCourses.txt';
    $cache = self::getCoursesCache();
    $results = '';
    
    if (!$cache->isFresh($cacheName)) {
      $args = array();
      self::addTermQueryToArgs($args);
      $args['ignored'] = 'butNeeded';
      
      $urlString = $GLOBALS['siteConfig']->getVar('COURSES_BASE_URL').http_build_query($args);
      self::condenseXMLFileForCoursesAndWrite($urlString, $cacheName);
    }
    $schoolsAndCourses = json_decode($cache->read($cacheName), true);
    if (is_array($schoolsAndCourses)) {
        usort($schoolsAndCourses, "compare_schoolName");
    } else {
        $schoolsAndCourses = array();
    }

    return $schoolsAndCourses;
  }


  private static function condenseXMLFileForCoursesAndWrite($xmlURLPath, $cacheName) {
    $xml = file_get_contents($xmlURLPath);
    if($xml == "") {
      // if failed to grab xml feed, then run the generic error handler
      throw new DataServerException('COULD NOT GET XML');
    }

    $xml_obj = simplexml_load_string($xml);
    
    $schoolsToCoursesMap = array();
    
    foreach($xml_obj->facets->facet as $fc) {
      if ($fc['name'] == 'school_nm') {
        foreach($fc->field as $field) {
          $args = array();
          self::addSchoolQueryToArgs($args, $field['name']);
          self::addTermQueryToArgs($args);
          $args['ignored'] = 'butNeeded';
      
          $urlString = $GLOBALS['siteConfig']->getVar('COURSES_BASE_URL').http_build_query($args);
          
          $courses_map_xml = file_get_contents($urlString);      
          if ($courses_map_xml == "") {
            // if failed to grab xml feed, then run the generic error handler
            throw new DataServerException('COULD NOT GET XML');
          }
      
          $courses_xml_obj = simplexml_load_string($courses_map_xml);
          
          $course_array = array();
          foreach($courses_xml_obj->facets->facet as $fcm) {
            if ($fcm['name'] == 'dept_area_category') {
              foreach($fcm->field as $fieldMap) {
                if (isset($fieldMap['name']) && $fieldMap['name']) {
                  $course_array[] = strval($fieldMap['name']);
                }
              }
            }
          }
          
          if (!count($course_array)) {
            // there are no courses - return one entry for the whole school
            $course_array = array(strval($field['short_name']));
            
          } else {
            // Add the main department to the end so we can see classes not in one of the courses
            $course_array[] = strval($field['short_name']).MISC_CLASSES_SUFFIX;
          }
    
          if (strval($field['name'])) {
            $schoolsToCoursesMap[] = array(
              'school_name'       => strval($field['name']),
              'school_name_short' => strval($field['short_name']),
              'courses'           => $course_array,
            );
          }
        }
      }
    }
    $cache = self::getCoursesCache();
    $cache->write(json_encode($schoolsToCoursesMap), $cacheName);

    return;
  }

  public static function search_subjects($terms, $school, $courseTitle) {    
    list($school, $courseTitle) = self::stripMiscClassesSuffix($school, $courseTitle);
    
    $args = array(
      'start' => 0,  // start must be first
    );
    
    self::addTermQueryToArgs($args);
    
    if (strlen($school)) {
      self::addSchoolQueryToArgs($args, $school);
    }
    
    if (strlen($courseTitle)) {
      if ($courseTitle == $school) {
        self::addCategoryQueryToArgs($args);
      } else {
        self::addCategoryQueryToArgs($args, $courseTitle);
      }
    }
    
    $args['q'] = '"'.str_replace(':', ' ', $terms).'"';
    $args['sort'] = 'score desc,course_title asc';
    
    $urlString = $GLOBALS['siteConfig']->getVar('COURSES_BASE_URL').http_build_query($args);
    $xml = file_get_contents($urlString);
    
    //error_log($urlString);
    //echo $xml;
    
    if($xml == "") {
    // if failed to grab xml feed, then run the generic error handler
      throw new DataServerException('COULD NOT GET XML');
    }
    
    $xml_obj = simplexml_load_string($xml);
    $count = $xml_obj->courses['numFound']; // Number of Courses Found
    
    /* ONLY IF search results from the MAIN courses page are greater than 100 */
    if (($count > 100) && ($school == '')) {
    
      foreach($xml_obj->facets->facet as $fc) {
    
        if ($fc['name'] == 'school_nm')
          foreach($fc->field as $field) {
            $schools[] = array(
              'name'       => strval($field['name']),
              'count'      => strval($field['count']),
              'name_short' => strval($field['short_name']),
            );
          }
      }
      $too_many_results['count'] = strval($count);
      $too_many_results['schools'] = $schools;
      return $too_many_results;
    }
    
    $iterations = ($count/25);
    
    $actual_count = $count;
    if ($iterations > 4) {
      $iterations = 4;
      $count = 100;
    }
    
    // printf("Total: %d\n",$count);
    // printf("Iterations: %d\n",$iterations);
    $subject_array = array();
    for ($index = 0; $index < $iterations; $index = $index+1) {
      if ($index > 0) {
        $args['start'] = $index * 25;
        //error_log(" Current = ".$index*25);
        
        $urlString = $GLOBALS['siteConfig']->getVar('COURSES_BASE_URL').http_build_query($args);
        $xml = file_get_contents($urlString);
        
        //error_log($urlString);
      
        if($xml == "") {
          // if failed to grab xml feed, then run the generic error handler
          throw new DataServerException('COULD NOT GET XML');
        }
      
        $xml_obj = simplexml_load_string($xml);
      }
      
      foreach($xml_obj->courses->course as $single_course) {
        $subject_fields = array(
          'name'     => strval($single_course->course_number),
          'school'   => strval($single_course->school_name),
          'masterId' => strval($single_course['id']),
          'title'    => strval($single_course->title),
          'term'     => self::get_term(),
        );
    
        $staff['instructors'] = self::getInstructorsFromDescription($single_course->faculty_description);
        $staff['tas'] = array();
        $subject_fields['staff'] = $staff;

        $temp = self::get_schoolsAndCourses();
        foreach($temp as $schoolsMapping) {
          if (!strcasecmp($schoolsMapping['school_name'], $subject_fields['school'])) {
            $subject_fields['short_name'] = $schoolsMapping['school_name_short'];
          }
        }
        if (!isset($subject_fields['short_name'])) {
          error_log("search_subjects(): no short name for {$subject_fields['school']}");
          $subject_fields['short_name'] = $subject_fields['school'];
        }
    
        $subject_array[] = $subject_fields;
      }
    }
    
    $courseToSubject ['count'] = strval($count);
    $courseToSubject['actual_count'] = strval($actual_count);
    $courseToSubject ['classes'] = $subject_array;

    return $courseToSubject;
  }
}

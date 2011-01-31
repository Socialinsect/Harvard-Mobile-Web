<?php

require_once realpath(LIB_DIR.'/Module.php');
require_once realpath(LIB_DIR.'/feeds/courses.php');

define('MYCOURSES_EXPIRE_TIME', 160 * 24 * 60 * 60); // Maybe use customize expire time?
define('MY_CLASSES_COOKIE', 'myclasses');


class CoursesModule extends Module {
  protected $id = 'courses';

  protected function setBreadcrumbTitle($title) {
    $config = $this->loadWebAppConfigFile("{$this->id}-abbreviations", true);

    if (isset($config['breadcrumbs'], $config['breadcrumbs']['from'], $config['breadcrumbs']['to'])) {
      $mappings = array_combine($config['breadcrumbs']['from'], $config['breadcrumbs']['to']);
      if (isset($mappings[$title])) {
        $title = $mappings[$title];
      }
    }
    parent::setBreadcrumbTitle($title);
  }

  private function getMyClasses() {
    // read the cookie, and create three groups                                                                                                                                                           
    // first group all the classes in the cookie                                                                                                                                                          
    // second group is the classes for the current semester                                                                                                                                               
    // third group is the classes from previous semesters 
    
    $term = CourseData::get_term();
    if(isset($_COOKIE[MY_CLASSES_COOKIE])) {
      $allTags = explode(',', $_COOKIE[MY_CLASSES_COOKIE]);
      natsort($allTags);
    } else {
      $allTags = array();
    }

    $currentTags = array();
    $currentIds = array();
    $oldIds = array();
    foreach($allTags as $classTag) {
      $parts = explode(' ', $classTag, 2);
      if(count($parts) > 1 && $parts[1] == $term) {
        $currentTags[] = $classTag;
        $currentIds[] = $parts[0];
      } else {
        $oldIds[] = $parts[0];
      }
    }
  
    return array(
      'allTags'     => $allTags,
      'currentTags' => $currentTags,
      'currentIds'  => $currentIds,
      'oldIds'      => $oldIds,
    );
  }
  
  private function removeOldMyClasses() {
    $myClasses = $this->getMyClasses();
    $this->setMyClasses($myClasses['currentTags']);
  }
  
  private function setMyClasses($classes) {
    setcookie(MY_CLASSES_COOKIE, implode(',', $classes), time() + MYCOURSES_EXPIRE_TIME, COOKIE_PATH);
  }
  
  private function coursesURL($school, $addBreadcrumb=true) {
    return $this->buildBreadcrumbURL('courses', array(
      'school' => $school,
    ), $addBreadcrumb);
  }
  
  private function getClassListItems($classes, $externalLink=false, $limit=null, $showTimes=false) {
    $listItems = array();
    
    $count = 0;
    foreach ($classes as $i => $class) {
      if (!strlen($class['masterId'])) { continue; }
      
      // Multiple classes with the same name in a row, show instructors to differentiate     
      $staffNamesIfNeeded = '';    
      if (($i > 0                   && $class['name'] == $classes[$i-1]['name']) || 
          ($i < (count($classes)-1) && $class['name'] == $classes[$i+1]['name'])) {
        $staffNamesIfNeeded = implode(', ', $class['staff']['instructors']);
        if (strlen($staffNamesIfNeeded)) {
          $staffNamesIfNeeded = ' ('.$staffNamesIfNeeded.')';
        }
      }
      
      $args = array(
        'class' => $class['masterId'],
      );
      
      $listItem = array(
        'title' => "<strong>{$class['name']}:</strong> {$class['title']}".$staffNamesIfNeeded,
        'url'   => $externalLink ? 
          $this->buildURLForModule($this->id, 'detail', $args) :
          $this->buildBreadcrumbURL('detail', $args),
      );
      
      if ($showTimes) {
        $meetingTimes = $class['meeting_times'];
        
        $times = array();
        if ($meetingTimes->parseSucceeded()) {
          foreach ($meetingTimes->all() as $meetingTime) {
            $times[] = $this->formatDetails($meetingTime->daysText()).' '.
              $this->formatDetails($meetingTime->timeText());
          }
          $listItem['subtitle'] = implode(', ', $times);
          
        } else {
          $listItem['subtitle'] = $class['meeting_times']->rawTimesText();
        }
      }
      
      $listItems[] = $listItem;
      
      $count++;
      if (isset($limit) && $count >= $limit) {
        break;
      }
    }        
    
    return $listItems;
  }

  private function formatDetails($string) {
    return str_replace(
      array('-',      '@'),
      array('-&shy;', '@&shy;'),
      $string);
  }

  private function courseURL($course, $courseShort, $school, $schoolShort, $addBreadcrumb=true) {
    return $this->buildBreadcrumbURL('course', array(
      'course'      => $course,
      'courseShort' => $courseShort,
      'school'      => $school,
      'schoolShort' => $schoolShort,
    ), $addBreadcrumb);
  }
  
  private function searchSchoolURL($filter, $school, $schoolShort, $addBreadcrumb=true) {
    return $this->buildBreadcrumbURL('search', array(
      'filter'      => $filter,
      'school'      => $school,
      'schoolShort' => $schoolShort,
      'fromMain'    => '1',
    ), $addBreadcrumb);
  }
  
  private function searchCourseURL($filter, $course, $courseShort, $school, $schoolShort, $addBreadcrumb=true) {
    return $this->buildBreadcrumbURL('search', array(
      'filter'      => $filter,
      'course'      => $course,
      'courseShort' => $courseShort,
      'school'      => $school,
      'schoolShort' => $schoolShort,
    ), $addBreadcrumb);
  }
  
  private function detailURL($class, $addBreadcrumb=true) {
    return $this->buildBreadcrumbURL('detail', array(
      'class' => $class,
    ), $addBreadcrumb);
  }
  
  private function personURL($person) {
    return self::buildURLForModule('people', 'search', array(
      'filter' => str_replace('.', '', preg_replace('/\s+/', ' ', $person)),
    ));
  }
  
  private function mapURLForClassTime($location) {
    return self::buildURLForModule('map', 'search', array(
      'loc'    => 'courses',
      'filter' => $location,
    ));
  }

  public function federatedSearch($searchTerms, $maxCount, &$results) {
    $data = CourseData::search_subjects($searchTerms, '', '');
    if ($data['count'] > 0 && isset($data['classes'])) {
      $results = $this->getClassListItems($data['classes'], true, $maxCount);
    }   
    
    return $data['count'];
  }

  protected function initializeForPage() {
    switch ($this->page) {
      case 'help':
        break;
      
      case 'pane':
        // List of bookmarked courses and schools
        $myClasses = $this->getMyClasses();
        $classes = array();
        foreach ($myClasses['currentIds'] as $index => $id) {
          $classes[] = CourseData::get_subject_details($id);
        }
        $this->assign('myClasses',        $this->getClassListItems($classes, true, null, true));
        $this->assign('myRemovedCourses', $myClasses['oldIds']);
        break;        
      
      
      case 'index':
        // List of bookmarked courses and schools
        $myClasses = $this->getMyClasses();
        $classes = array();
        foreach ($myClasses['currentIds'] as $index => $id) {
          $classes[] = CourseData::get_subject_details($id);
        }
        $this->assign('myClasses',        $this->getClassListItems($classes));
        $this->assign('myRemovedCourses', $myClasses['oldIds']);
        
        $schoolsInfo = CourseData::get_schoolsAndCourses();
        $schools = array();
        foreach ($schoolsInfo as $schoolInfo) {
          $courses   = $schoolInfo['courses'];
          $name      = $schoolInfo['school_name'];
          $shortName = $schoolInfo['school_name_short'];
        
          $school = array(
            'title' => $schoolInfo['school_name_short']
          );
          if (count($courses) < 2) {
            $school['url'] = $this->courseURL($name, $shortName, $name, $shortName);
              
          } else {
            $school['url'] = $this->coursesURL($name);
          }
          $schools[] = $school;
        }        
        $this->assign('schools', $schools);
        break;
        
      case 'courses':
        // A list of all the departments in a school
        $schoolName = $this->args['school'];
        
        $schoolsInfo = CourseData::get_schoolsAndCourses();
        
        $schoolNameShort = '';
        $coursesInfo = array();
        foreach($schoolsInfo as $schoolInfo) {
          if ($schoolInfo['school_name'] == $schoolName) {
            $coursesInfo = $schoolInfo['courses'];
            $schoolNameShort = $schoolInfo['school_name_short'];
            break;
          }
        }
        
        $this->setBreadcrumbTitle($schoolNameShort);

        $courses = array();
        foreach ($coursesInfo as $courseName) {
          $courses[] = array(
            'title' => $courseName,
            'url'   => $this->courseURL($courseName, $courseName, $schoolName, $schoolNameShort),
          );
        }

        $this->assign('schoolNameShort', $schoolNameShort);
        $this->assign('courses', $courses);
        $this->assign('extraSearchArgs', array(
          'school'      => $this->args['school'],
          'schoolShort' => $schoolNameShort,
        ));
        break;

      case 'course':
        // A list of classes in a department
        $courseName      = $this->getArg('course');
        $courseNameShort = $this->getArg('courseShort');
        $schoolName      = $this->getArg('school');
        $schoolNameShort = $this->getArg('schoolShort');
        
        $this->setBreadcrumbTitle($courseNameShort);

        $classes = CourseData::get_subjectsForCourse($courseName, $schoolName);

        $extraSearchArgs = array(
          'school'      => $schoolName,
          'schoolShort' => $schoolNameShort,
        );
        if ($courseName && $schoolName != $courseName) {
          $extraSearchArgs['course']      = $courseName;
          $extraSearchArgs['courseShort'] = $courseNameShort;
        }

        $this->assign('classes',         $this->getClassListItems($classes));
        $this->assign('courseNameShort', $courseNameShort);
        $this->assign('extraSearchArgs', $extraSearchArgs);
        break;
        
      case 'searchCourses':
        // A list of departments with search results
        $searchTerms = $this->getArg('filter');

        $data = CourseData::search_subjects($searchTerms, '', '');
        $count = $data['count'];
        $classes = isset($data['classes']) ? $data['classes'] : NULL;
    
      
        $schools = array();
        if ($data['count'] <= 100) {
          foreach ($data['classes'] as $class) {
            if (!in_array($class['school'], array_keys($schools))) {
              $schools[$class['school']] = array(
                'title' => "{$class['short_name']} (1)",
                'url'   => $this->searchSchoolURL($searchTerms, $class['school'], $class['short_name']),
                'count' => 1,
              );
            } else {
              $schools[$class['school']]['count']++;
              $schools[$class['school']]['title'] = "{$class['short_name']} ({$schools[$class['school']]['count']})";
            }
          }
        } else {
          // schoolData will only be available for searches 
          // from the top-level view where search results are > 100
          $schoolData = isset($data['schools']) ? $data['schools'] : NULL;
          foreach ($schoolData as $school) {
            $schools[$school['name']] = array(
              'title' => "{$school['name_short']} ({$school['count']})",
              'url'   => $this->searchSchoolURL($searchTerms, $school['name'], $school['name_short']),
              'count' => $school['count'],
            );
          }
        }
        $this->assign('searchTerms', $searchTerms);
        $this->assign('schools',     array_values($schools));
        break;
        
      case 'search':
        // search results for a department
        $searchTerms     = $this->getArg('filter');
        $fromSearchMain  = $this->getArg('fromMain');
        $courseName      = $this->getArg('course');
        $courseNameShort = $this->getArg('courseShort');
        $schoolName      = $this->getArg('school');
        $schoolNameShort = $this->getArg('schoolShort');

        $shortName = strlen($courseNameShort) ? $courseNameShort : $schoolNameShort;
  
        if (isset($fromSearchMain) && $fromSearchMain) {
          $this->setBreadcrumbTitle($shortName);
        }
        
        $data = CourseData::search_subjects($searchTerms, $schoolName, $courseName);
        $count = $data['count'];
        $classes = isset($data['classes']) ? $data['classes'] : array();

        $extraSearchArgs = array(
          'school'      => $schoolName,
          'schoolShort' => $schoolNameShort,
        );
        if ($courseName && $schoolName != $courseName) {
          $extraSearchArgs['course']      = $courseName;
          $extraSearchArgs['courseShort'] = $courseNameShort;
        }

        $this->assign('shortName',       $shortName);
        $this->assign('classes',         $this->getClassListItems($classes));
        $this->assign('searchTerms',     $searchTerms);
        $this->assign('extraSearchArgs', $extraSearchArgs);
        break;
        
      case 'detail':
        $classId = $this->args['class'];
        
        $classInfo = CourseData::get_subject_details($classId);
        $termId = CourseData::get_term();
        
        if (!$classInfo) {
          $this->assign('errorText', "Sorry, class '$courseID' not found for the $term term");
          break;
        }
        
        $myClasses = $this->getMyClasses();
        $myClassTags = $myClasses['allTags'];
        $classTag = "$classId $termId";
        $isInMyClasses = in_array($classTag, $myClassTags);

        // Add or remove from the myClasses list
        if (isset($this->args['action'])) {
          if ($this->args['action'] == 'add' && !$isInMyClasses) {
            $myClassTags[] = $classTag;
            
          } else if ($this->args['action'] == 'remove') {
            if ($isInMyClasses) {
              array_splice($myClassTags, array_search($classTag, $myClassTags), 1);
            }
            // Also remove any from other terms
            foreach ($myClassTags as $item) {
              if (strpos($item, $classId) !== false) {
                array_splice($myClassTags, array_search($item, $myClassTags), 1);
              }
            }
          }
          $this->setMyClasses($myClassTags);
          $this->redirectTo($this->page, array(
            'class'  => $classId,
          ));
        }
        $toggleMyClassesURL = $this->buildBreadcrumbURL($this->page, array(
          'class'  => $classId,
          'action' => $isInMyClasses ? 'remove' : 'add',
        ), false);
        
        // Info
        $meetingTimes = $classInfo['meeting_times'];
        
        $times = array();
        if ($meetingTimes->parseSucceeded()) {
          foreach ($meetingTimes->all() as $meetingTime) {
            $time = array(
              'days' => $this->formatDetails($meetingTime->daysText()),
              'time' => $this->formatDetails($meetingTime->timeText()),
            );
            
            if ($meetingTime->isLocationKnown()) {
              $time['location'] = $this->formatDetails($meetingTime->locationText());
              $time['url'] = $this->mapURLForClassTime($meetingTime->locationText());
            }
            $times[] = $time;
          }
        } else {
          $times[] = array(
            'days' => $this->formatDetails($meetingTimes->rawTimesText()),
            'time' => $this->formatDetails($meetingTimes->rawLocationsText()),
          );
        }
        
        $infoFields = $this->loadWebAppConfigFile('courses-detail', 'infoFields');
        $infoItems = array();
        foreach ($infoFields['info'] as $field => $header) {
          if (isset($classInfo[$field]) && strlen($classInfo[$field])) {
            $infoItems[] = array(
              'header'  => $this->formatDetails($header),
              'content' => $this->formatDetails($classInfo[$field]),
            );
          }
        }

        // Staff
        $staff = array();
        foreach ($classInfo['staff'] as $type => $staffList) {
          $staff[$type] = array();
          foreach ($classInfo['staff'][$type] as $person) {
            $staff[$type][] = array(
              'title' => $this->formatDetails($person),
              'url'   => $this->personURL($person),
            );
          }
        }
        
        $this->assign('term',               $termId);
        $this->assign('classId',            $classId);
        $this->assign('className',          $this->formatDetails($classInfo['name']));
        $this->assign('classTitle',         $this->formatDetails($classInfo['title']));
        $this->assign('classUrl',           self::argVal($classInfo, 'url', ''));
        $this->assign('times',              $times);
        $this->assign('infoItems',          $infoItems);
        $this->assign('staff',              $staff);
        $this->assign('isInMyClasses',      $isInMyClasses);
        $this->assign('toggleMyClassesURL', $toggleMyClassesURL);
        
        $this->enableTabs(array('info', 'staff'));
        
        $this->addInlineJavascript(
          'var MY_CLASSES_COOKIE = "'.MY_CLASSES_COOKIE.'";'.
          'var COOKIE_PATH = "'.COOKIE_PATH.'";'
        );
        break;
    }
  }
}

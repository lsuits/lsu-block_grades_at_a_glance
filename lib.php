<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings for component 'block_grades_at_a_glance'
 *
 * @package    block_grades_at_a_glance
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/grade/grade_item.php');
require_once($CFG->libdir . '/grade/grade_grade.php');
require_once($CFG->libdir . '/gradelib.php');

/**
 * Gets the grade for the specified user and specified course, then
 * formats the grade according to either the user's preference or
 * the admin default.
 *
 * @param int $courseid The id of the course to get grades for
 * @param int $userid The id of the user whose grades we want
 * @param int $gradeformat How the grade should be formatted
 *
 * @return string The formatted grade, ready for display.
 */
function gaag_get_grade_for_course($courseid, $userid, $gradeformat) {
    $finalgrade = '-';
    $coursetotalobj = grade_item::fetch_course_item($courseid);

    if ($coursetotalobj) {
        if ($coursetotalobj->hidden) {
            $finalgrade = get_string('hidden', 'block_grades_at_a_glance');
        } else {
            $gradeparams = array(
                'itemid' => $coursetotalobj->id,
                'userid' => $userid
            );

            $usergrade = new grade_grade($gradeparams);

            if ($usergrade->finalgrade) {
                $finalgrade = grade_format_gradevalue(
                    $usergrade->finalgrade,
                    $coursetotalobj, true, $gradeformat
                );
            }
        }
    }

    return $finalgrade;
}

/**
 * Trims the course's short name at the word "for" and returns
 * everything before the word "for".
 *
 * @param string $shortname The entire short name of the course.
 *
 * @return string The course's short name trimmed at the word "for"
 */
function gaag_get_shortname($shortname) {
    $split = preg_split('/\s+for\s+/', $shortname);

    return $split[0];
}

/**
 * Using the user's preferred sort order, extracts those courses from
 * $courses and puts them into $sortedcourses, up to the maximum as
 * defined by $maxcourses.
 *
 * @param array $courses Array of courses in which the user is enrolled as a student
 * @param int $maxcourses The maximum number of courses to display
 * @param array $sortedcourses Array of courses sorted by the user's preference
 *
 * @return array The sorted courses
 */
function gaag_get_sorted_courses($courses, $maxcourses, $sortedcourses) {
    $value = get_user_preferences('course_overview_course_sortorder');
    $sortorder = explode(',', $value);
    $counter = 0;

    foreach ($sortorder as $sortid) {
        if (($counter >= $maxcourses) && ($maxcourses != 0)) {
            break;
        }

        // Make sure user is still enroled.
        if (isset($courses[$sortid])) {
            $sortedcourses[$sortid] = $courses[$sortid];
            $counter++;
        }
    }

    return $sortedcourses;
}

/**
 * Adds a number of courses, up to a maximum of $maxcourses, to $sortedcourses
 * from $courses. If the course is already in $sortedcourses, it will not be
 * added a second time.
 *
 * @param array $courses Array of courses in which the user is enrolled as a student
 * @param int $maxcourses The maximum number of courses to display
 * @param array $sortedcourses Array of courses sorted by the user's preference, if any
 *
 * return array Array of courses
 */
function gaag_get_unsorted_courses($courses, $maxcourses, $sortedcourses) {
    $counter = count($sortedcourses);

    // Append unsorted courses if limit allows.
    foreach ($courses as $course) {
        if (($counter >= $maxcourses) && ($maxcourses != 0)) {
            break;
        }

        if (!array_key_exists($course->id, $sortedcourses)) {
            $sortedcourses[$course->id] = $course;
            $counter++;
        }
    }

    return $sortedcourses;
}

/**
 * Gets any remote courses the user is enrolled in as a student and adds them
 * to the $courses array.
 *
 * @param array $courses Array of courses the user is enrolled in as a student, if any
 *
 * @return array Array of courses
 */
function gaag_append_remote_courses($courses) {
    // Get remote courses.
    $remotecourses = array();

    if (is_enabled_auth('mnet')) {
        $remotecourses = get_my_remotecourses();

        // Remote courses will have -ve remoteid as key, so it can be differentiated from normal courses.
        foreach ($remotecourses as $val) {
            $remoteid = $val->remoteid * -1;
            $val->id = $remoteid;
            $courses[$remoteid] = $val;
        }
    }

    return $courses;
}

/**
 * Removes the site course from the $courses array.
 *
 * @param array $courses The array of courses
 *
 * @return array The array of courses, without the site course
 */
function gaag_remove_site_courses($courses) {
    $site = get_site();

    if (array_key_exists($site->id, $courses)) {
        unset($courses[$site->id]);
    }

    return $courses;
}

/**
 * Returns a multi-dimensional associative array containing an array of courses and the total number
 * of courses in which the user is enrolled as a student.
 *
 * @param int $maxcourses Maximum number of courses to display
 * @param int $courseoverviewsort Whether to sort courses using the user's preference from Course
 *      Overview block; 1 if sorted by Course Overview preference, 0 otherwise
 *
 * @return array Multi-dimensional associative array where the first element is an array of courses
 *      and the second element is an integer representing the total number of courses in which the
 *      user is enrolled
 */
function gaag_get_all_courses($maxcourses, $courseoverviewsort) {
    $sortedcourses = array();

    $courses = enrol_get_my_courses('showgrades', 'id DESC');

    $courses = gaag_remove_site_courses($courses);
    $courses = gaag_append_remote_courses($courses);

    // Get courses sorted by Course Overview sort order, if the user has selected (or admin has forced) this.
    if ($courseoverviewsort) {
        $sortedcourses = gaag_get_sorted_courses($courses, $maxcourses, $sortedcourses);
    }

    $sortedcourses = gaag_get_unsorted_courses($courses, $maxcourses, $sortedcourses);

    return array('sortedcourses' => $sortedcourses, 'totalcourses' => count($courses));
}

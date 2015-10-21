<?php

require_once($CFG->libdir . '/grade/grade_item.php');
require_once($CFG->libdir . '/grade/grade_grade.php');
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->dirroot . '/grade/report/lib.php');

// Class to get grade report functions and variables from parent class in grade/report/lib.php
// copied from quickedit

class grade_report_grades_at_a_glance extends grade_report {

    /**
     * The user.
     * @var object $user
     */
    public $user;

    /**
     * The user's courses
     * @var array $courses
     */
    public $courses;

    /**
     * show course/category totals if they contain hidden items
     */
    var $showtotalsifcontainhidden;

    /**
     * An array of course ids that the user is a student in.
     * @var array $studentcourseids
     */
    public $studentcourseids;

    /**
     * An array of courses that the user is a teacher in.
     * @var array $teachercourses
     */
    public $teachercourses;

    /**
     * Constructor. Sets local copies of user preferences and initialises grade_tree.
     * @param int $userid
     * @param string $context
     */
    public function __construct($userid, $courseid, $context) {
        global $CFG, $COURSE, $DB;
        parent::__construct($courseid, null, $context);

        // Get the user (for full name).
        $this->user = $DB->get_record('user', array('id' => $userid));

        // Load the user's courses.
        $this->courses = enrol_get_users_courses($this->user->id, false, 'id, shortname, showgrades');

        $this->showtotalsifcontainhidden = array();
        
        $this->studentcourseids = array();
        $this->teachercourses = array();
        $roleids = explode(',', get_config('moodle', 'gradebookroles'));

        if ($this->courses) {
            foreach ($this->courses as $course) {
                $this->showtotalsifcontainhidden[$course->id] = grade_get_setting($course->id, 'report_overview_showtotalsifcontainhidden', $CFG->grade_report_overview_showtotalsifcontainhidden);
                $coursecontext = context_course::instance($course->id);
                foreach ($roleids as $roleid) {
                    if (user_has_role_assignment($userid, $roleid, $coursecontext->id)) {
                        $this->studentcourseids[$course->id] = $course->id;
                        // We only need to check if one of the roleids has been assigned.
                        break;
                    }
                }
                if (has_capability('moodle/grade:viewall', $coursecontext, $userid)) {
                    $this->teachercourses[$course->id] = $course;
                }
            }
        }


    }
    function process_action($target, $action) {
    }

    function process_data($data) {
        return $this->screen->process($data);
    }

    function get_blank_hidden_total_and_adjust_bounds($courseid, $course_total_item, $finalgrade){

        return($this->blank_hidden_total_and_adjust_bounds($courseid, $course_total_item, $finalgrade));
    }
}



// Returns the formatted course total item value give a userid and a course id
// If a course has no course grade item (no grades at all) the system returns '-'
// If a user has no course grade, the system returns '-'
// If a user has grades and the instructor allows those grades to be viewed, the system returns the final grade as stored in the database
// If a user has grades and the instructor has hidden the course grade item, the system returns the string 'hidden'
// If a user has grades and the instructor has hidden some of the users grades and those hidden items impact the course grade based on the instructor's settings, the system recalculates the course grade appropriately
function gaag_get_grade_for_course($courseid, $userid) {
    $course_total_item = grade_item::fetch_course_item($courseid);
    $course_context = context_course::instance($courseid);
    $report = new grade_report_grades_at_a_glance($userid, $courseid, null, $course_context);
    if (!$course_total_item) {
        $totalgrade = '-';
    }
    $grade_grade_params = array(
        'itemid' => $course_total_item->id,
        'userid' => $userid
    );
    $user_grade_grade = new grade_grade($grade_grade_params);
    if (!$user_grade_grade->finalgrade) {
        $totalgrade = '-';
    } else {
        $finalgrade = $user_grade_grade->finalgrade;
        $get_report_hidden_grades_calculator = $report->get_blank_hidden_total_and_adjust_bounds($courseid, $course_total_item, $finalgrade);
        $totalgrade = grade_format_gradevalue(
            $get_report_hidden_grades_calculator['grade'],
            $course_total_item, true
        );
        if ($course_total_item->hidden OR $totalgrade == '-') {
            $totalgrade = get_string('hidden', 'block_grades_at_a_glance');
        }
        if (has_capability('moodle/grade:viewall', $course_context, $userid)) {
            $totalgrade = grade_format_gradevalue(
                $user_grade_grade->finalgrade,
                $course_total_item, true
            );
        }
    }
    return $totalgrade;
}

function gaag_get_shortname($shortname) {
    $split = preg_split('/\s+for\s+/', $shortname);

    return $split[0];
}


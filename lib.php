<?php

require_once($CFG->libdir . '/grade/grade_item.php');
require_once($CFG->libdir . '/grade/grade_grade.php');
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->dirroot . '/grade/report/lib.php');
require_once($CFG->dirroot . '/grade/lib.php');

// Class to get grade report functions and variables from parent class in grade/report/lib.php
// @todo a lot of code in the constructor could be taken out as a lot of this code is
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
     * A flexitable to hold the data.
     * @var object $table
     */
    public $table;

    /**
     * Show student ranks within each course.
     * @var array $showrank
     */
    public $showrank;

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
     * @param object $gpr grade plugin return tracking object
     * @param string $context
     */
    public function __construct($userid, $courseid, $gpr, $context) {
        global $CFG, $COURSE, $DB;
        parent::__construct($courseid, $gpr, $context);

        // Get the user (for full name).
        $this->user = $DB->get_record('user', array('id' => $userid));

        // Load the user's courses.
        $this->courses = enrol_get_users_courses($this->user->id, false, 'id, shortname, showgrades');

        $this->showrank = array();
        $this->showrank['any'] = false;

        $this->showtotalsifcontainhidden = array();
        
        $this->studentcourseids = array();
        $this->teachercourses = array();
        $roleids = explode(',', get_config('moodle', 'gradebookroles'));

        if ($this->courses) {
            foreach ($this->courses as $course) {
                $this->showrank[$course->id] = grade_get_setting($course->id, 'report_overview_showrank', !empty($CFG->grade_report_overview_showrank));
                if ($this->showrank[$course->id]) {
                    $this->showrank['any'] = true;
                }

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


        // base url for sorting by first/last name
//        $this->baseurl = $CFG->wwwroot.'/grade/overview/index.php?id='.$userid;
//        $this->pbarurl = $this->baseurl;

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
function gaag_get_grade_for_course($courseid, $userid) {

    $course_total_item = grade_item::fetch_course_item($courseid);

    if (!$course_total_item) {
        return '-';
    }

    if ($course_total_item->hidden) {
        return get_string('hidden', 'block_grades_at_a_glance');
    }

    $grade_grade_params = array(
        'itemid' => $course_total_item->id,
        'userid' => $userid
    );

    $user_grade_grade = new grade_grade($grade_grade_params);

    if (!$user_grade_grade->finalgrade) {
        $finalgrade = '-';
    } else {
        $finalgrade = grade_format_gradevalue(
            $user_grade_grade->finalgrade,
            $course_total_item, true
        );
    }

    $course_context = context_course::instance($courseid);

    $gpr = new grade_plugin_return(array(
        'type' => 'report',
        'plugin' => 'grades_at_a_glance',
        'courseid' => $courseid,
        'userid' => $userid
    ));

    $report = new grade_report_grades_at_a_glance($userid, $courseid, $gpr, $course_context);

    $get_report_hidden_grades_calculator = $report->get_blank_hidden_total_and_adjust_bounds($courseid, $course_total_item, $finalgrade);
    return $get_report_hidden_grades_calculator['grade'];
}

function gaag_get_shortname($shortname) {
    $split = preg_split('/\s+for\s+/', $shortname);

    return $split[0];
}


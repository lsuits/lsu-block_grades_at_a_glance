<?php

/**
 * Adam Zapletal
 * Philip Cali
 * Louisiana State University
 **/

require_once($CFG->dirroot . '/blocks/grades_at_a_glance/lib.php');

class block_grades_at_a_glance extends block_list {
    function init() {
        $this->title = get_string('pluginname', 'block_grades_at_a_glance');
    }

    function applicable_formats() {
        return array('site' => true, 'my' => true, 'course' => false);
    }

    function get_content() {
        if ($this->content !== null) {
            return $this->content;
        }

        global $CFG;
        global $USER;

        $this->content = new stdClass;

        $this->content->icons = array();
        $this->content->footer = '';

        $gradebook_roles = explode(',', $CFG->gradebookroles);

        $no_courses_str = get_string('no_courses', 'block_grades_at_a_glance');

        // Return early if this user is enrolled in zero courses
        if (!$courses = enrol_get_my_courses($fields = 'showgrades')) {
            $this->content->items = array($no_courses_str);

            return $this->content;
        }


        $link_common = '/grade/report/user/index.php';

        foreach ($courses as $course) {
            $course_context = context_course::instance($course->id);
            $roles_in_course = get_user_roles($course_context);

            $id = $course->id;

            $coursename = gaag_get_shortname($course->shortname);

            foreach ($roles_in_course as $role_in_course) {
                if (in_array($role_in_course->roleid, $gradebook_roles)) {
                    $url = new moodle_url($link_common, array('id' => $id));

                    $content = html_writer::link($url, $coursename);
                    $params = array('class' => 'gaag_course');

                    $left_part = html_writer::tag('span', $content, $params);

                    $content = gaag_get_grade_for_course($id, $USER->id);
                    $params = array('class' => 'gaag_grade');

                    $right_part = html_writer::tag('span', $content, $params);

                    $right_part = $course->showgrades == 1 ? html_writer::tag('span', $content, $params) : html_writer::tag('span', '-', $params);

                    $content = $left_part . $right_part;

                    $this->content->items[] = html_writer::tag('p', $content);
                    break;
                }
            }
        }

/*      Hide this block completely from instructors.
        // User is only in non-gradable roles in the courses they are enrolled in
        if (!count($this->content->items)) {
            $this->content->items = array($no_courses_str);
        }
*/

        return $this->content;
    }
}

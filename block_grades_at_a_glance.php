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
 * Adam Zapletal
 * Philip Cali
 * Louisiana State University
 **/

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/grades_at_a_glance/lib.php');

class block_grades_at_a_glance extends block_list {
    /**
     * Core function used to initialize the block.
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_grades_at_a_glance');
    }

    /**
     * Core function, specifies where the block can be used.
     * @return array
     */
    public function applicable_formats() {
        return array('site' => true, 'my' => true, 'course' => false);
    }

    /**
     * Used to generate the content for the block.
     * @return string
     */
    public function get_content() {
        if ($this->content !== null) {
            return $this->content;
        }

        global $CFG, $USER;

        // Load admin defaults.
        $blockconfig = get_config('block_grades_at_a_glance');

        // Set default values.
        $maxcourses = $blockconfig->defaultmaxcourses;
        $gradeformat = $blockconfig->defaultgradeformat;
        $sortorder = $blockconfig->defaultsortorder;

        if (empty($this->config)) {
            /*
             * If this is empty, we'll make it an object and create the properties we're expecting
             * it to have later, giving those properties the admin default values. This prevents
             * us having to do individual checks for the existence of these properties later.
             *
             * This only happens if the user hasn't configured their instance of this block, yet.
             */
            $this->config = new stdClass();
            $this->config->maxcourses = $maxcourses;
            $this->config->gradeformat = $gradeformat;
            $this->config->sortorder = $sortorder;
        }

        $this->content = new stdClass();

        $this->content->icons = array();
        $this->content->footer = '';

        $gradebookroles = explode(',', $CFG->gradebookroles);

        // If the user has configured their instance of this block, we'll load them now.
        $maxcourses = $this->config->maxcourses;
        $gradeformat = $this->config->gradeformat;
        $sortorder = $this->config->sortorder;

        $courses = gaag_get_all_courses($maxcourses, $sortorder);

        $noenrollments = get_string('no_courses', 'block_grades_at_a_glance');

        // Return early if this user is enrolled in zero courses.
        if (count($courses['sortedcourses']) == 0) {
            $this->content->items = array($noenrollments);

            return $this->content;
        }

        $linkcommon = '/grade/report/user/index.php';

        foreach ($courses['sortedcourses'] as $course) {
            $coursecontext = context_course::instance($course->id);
            $rolesincourse = get_user_roles($coursecontext);

            $courseid = $course->id;
            $coursename = gaag_get_shortname($course->shortname);
            $showgrades = $course->showgrades;

            foreach ($rolesincourse as $roleincourse) {
                if (in_array($roleincourse->roleid, $gradebookroles)) {
                    $item = '';
                    $content = '';

                    $url = new moodle_url($linkcommon, array('id' => $courseid));
                    $link = html_writer::link($url, $coursename);
                    $params = array('class' => 'gaag_course');

                    $leftpart = html_writer::tag('span', $link, $params);

                    $grade = gaag_get_grade_for_course($courseid, $USER->id, $gradeformat);
                    $params = array('class' => 'gaag_grade');

                    if ($showgrades == 1) {
                        $rightpart = html_writer::tag('span', $grade, $params);
                    } else {
                        $rightpart = html_writer::tag('span', '-', $params);
                    }

                    $content = $leftpart . $rightpart;
                    $item .= html_writer::tag('p', $content);

                    $this->content->items[] = $item;
                    break;
                }
            }
        }

        return $this->content;
    }

    /**
     * Allow the block to have a configuration page
     *
     * @return boolean
     */
    public function has_config() {
        return true;
    }
}

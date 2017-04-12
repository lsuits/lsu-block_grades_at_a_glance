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
 * Defines the form for editing Grades at a Glance block instances.
 *
 * @package    block_grades_at_a_glance
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/blocks/grades_at_a_glance/lib.php');

class block_grades_at_a_glance_edit_form extends block_edit_form {
    /**
     * The definition of the fields to use.
     *
     * @param MoodleQuickForm $mform
     */
    protected function specific_definition($mform) {
        global $DB, $USER;

        // Load admin defaults.
        $blockconfig = get_config('block_grades_at_a_glance');

        // Attempt to get the user's block configuration.
        $blockinstanceid = required_param('bui_editid', PARAM_INT);
        $sql = 'SELECT context.instanceid, instance.configdata
                FROM {context} context
                JOIN {block_instances} instance
                ON instance.parentcontextid=context.id
                WHERE instance.id=?';
        $blockinstancerecord = $DB->get_record_sql($sql, array($blockinstanceid));

        if ($blockinstancerecord->instanceid == $USER->id && !empty($blockinstancerecord->configdata)) {
            $configdata = unserialize(base64_decode($blockinstancerecord->configdata));
            $maxcourses = $configdata->maxcourses;
            $sortorder = $configdata->sortorder;
            $gradeformat = $configdata->gradeformat;
        } else {
            // If the user doesn't own this block instance or they haven't configured their instance,
            // then we'll just use the admin defaults.
            $maxcourses = $blockconfig->defaultmaxcourses;
            $sortorder = $blockconfig->defaultsortorder;
            $gradeformat = $blockconfig->defaultgradeformat;
        }

        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        $courses = gaag_get_all_courses($maxcourses, $sortorder);
        $numberofcourses = array('0' => get_string('all'));
        for ($counter = 1; $counter <= $courses['totalcourses']; $counter++) {
            $numberofcourses[$counter] = $counter;
        }

        $mform->addElement('select', 'config_maxcourses', get_string('maxcourses', 'block_grades_at_a_glance'),
            $numberofcourses);

        if ($blockconfig->defaultmaxcourses_locked) {
            $mform->freeze('config_maxcourses');
        }

        $gradeformatoptions = array(GRADE_DISPLAY_TYPE_REAL              => get_string('real', 'grades'),
                                    GRADE_DISPLAY_TYPE_REAL_PERCENTAGE   => get_string('realpercentage', 'grades'),
                                    GRADE_DISPLAY_TYPE_REAL_LETTER       => get_string('realletter', 'grades'),
                                    GRADE_DISPLAY_TYPE_PERCENTAGE        => get_string('percentage', 'grades'),
                                    GRADE_DISPLAY_TYPE_PERCENTAGE_REAL   => get_string('percentagereal', 'grades'),
                                    GRADE_DISPLAY_TYPE_PERCENTAGE_LETTER => get_string('percentageletter', 'grades'),
                                    GRADE_DISPLAY_TYPE_LETTER            => get_string('letter', 'grades'),
                                    GRADE_DISPLAY_TYPE_LETTER_REAL       => get_string('letterreal', 'grades'),
                                    GRADE_DISPLAY_TYPE_LETTER_PERCENTAGE => get_string('letterpercentage', 'grades'));
        $mform->addElement('select', 'config_gradeformat', get_string('gradeformat', 'block_grades_at_a_glance'),
            $gradeformatoptions);
        $mform->setDefault('config_gradeformat', $gradeformat);

        if ($blockconfig->defaultgradeformat_locked) {
            $mform->freeze('config_gradeformat');
        }

        $sortorderinput = $mform->createElement('advcheckbox', 'config_sortorder',
            get_string('sortorder', 'block_grades_at_a_glance'));

        if ($sortorder) {
            $sortorderinput->setChecked(true);
        }

        $mform->addElement($sortorderinput);

        if ($blockconfig->defaultsortorder_locked) {
            $mform->freeze('config_sortorder');
        }
    }
}

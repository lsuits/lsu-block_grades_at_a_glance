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
 * course_overview block settings
 *
 * @package    block_grades_at_a_glance
 * @copyright  2012 Adam Olley <adam.olley@netspot.com.au>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

// Default maximum courses to display.
$setting = new admin_setting_configtext('block_grades_at_a_glance/defaultmaxcourses',
    new lang_string('defaultmaxcourses', 'block_grades_at_a_glance'),
    new lang_string('defaultmaxcoursesdesc', 'block_grades_at_a_glance'), 10, PARAM_INT);
$setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
$settings->add($setting);

// Default grade format.
$gradeformatoptions = array(GRADE_DISPLAY_TYPE_REAL       => get_string('real', 'grades'),
                            GRADE_DISPLAY_TYPE_REAL_PERCENTAGE => get_string('realpercentage', 'grades'),
                            GRADE_DISPLAY_TYPE_REAL_LETTER => get_string('realletter', 'grades'),
                            GRADE_DISPLAY_TYPE_PERCENTAGE => get_string('percentage', 'grades'),
                            GRADE_DISPLAY_TYPE_PERCENTAGE_REAL => get_string('percentagereal', 'grades'),
                            GRADE_DISPLAY_TYPE_PERCENTAGE_LETTER => get_string('percentageletter', 'grades'),
                            GRADE_DISPLAY_TYPE_LETTER     => get_string('letter', 'grades'),
                            GRADE_DISPLAY_TYPE_LETTER_REAL => get_string('letterreal', 'grades'),
                            GRADE_DISPLAY_TYPE_LETTER_PERCENTAGE => get_string('letterpercentage', 'grades'));

$setting = new admin_setting_configselect('block_grades_at_a_glance/defaultgradeformat',
    new lang_string('defaultgradeformat', 'block_grades_at_a_glance'),
    new lang_string('defaultgradeformatdesc', 'block_grades_at_a_glance'), GRADE_DISPLAY_TYPE_LETTER_PERCENTAGE,
    $gradeformatoptions);
$setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
$settings->add($setting);

// Default use Course Overview sort order.
$setting = new admin_setting_configcheckbox('block_grades_at_a_glance/defaultsortorder',
    new lang_string('defaultsortorder', 'block_grades_at_a_glance'),
    new lang_string('defaultsortorderdesc', 'block_grades_at_a_glance'), 0, 1);
$setting->set_locked_flag_options(admin_setting_flag::ENABLED, false);
$settings->add($setting);

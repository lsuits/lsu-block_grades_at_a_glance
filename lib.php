<?php

require_once($CFG->libdir . '/grade/grade_item.php');
require_once($CFG->libdir . '/grade/grade_grade.php');
require_once($CFG->libdir . '/gradelib.php');

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

    return $finalgrade;
}

function gaag_get_shortname($shortname) {
    $split = preg_split('/\s+for\s+/', $shortname);

    return $split[0];
}

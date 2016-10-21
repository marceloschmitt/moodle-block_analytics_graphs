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


function block_analytics_graphs_subtract_student_arrays($estudantes, $acessaram) {
    $encontrou = array();
    foreach ($estudantes as $estudante) {
        $encontrou = false;
        foreach ($acessaram as $acessou) {
            if ($estudante['userid'] == $acessou ['userid']) {
                $encontrou = true;
                break;
            }
        }
        if (!$encontrou) {
            $resultado[] = $estudante;
        }
    }
    return $resultado;
}

function block_analytics_graphs_get_course_group_members($course) {
    $groupmembers = array();
    $groups = groups_get_all_groups($course);
    foreach ($groups as $group) {
        $members = groups_get_members($group->id);
        if (!empty($members)) {
            $groupmembers[$group->id]['name'] = $group->name;
            $numberofmembers = 0;
            foreach ($members as $member) {
                $groupmembers[$group->id]['members'][] = $member->id;
                $numberofmembers++;
            }
            $groupmembers[$group->id]['numberofmembers']  = $numberofmembers;
        }
    }
    return($groupmembers);
}


function block_analytics_graphs_get_students($course) {
    $students = array();
    $context = context_course::instance($course);
    $allstudents = get_enrolled_users($context, 'block/analytics_graphs:bemonitored', 0,
                    'u.id, u.firstname, u.lastname, u.email, u.suspended', 'firstname, lastname');
    foreach ($allstudents as $student) {
        if ($student->suspended == 0) {
            $students[] = $student;
        }
    }
    return($students);
}


function block_analytics_graphs_get_teachers($course) {
    $teachers = array();
    $context = context_course::instance($course);
    $allteachers = get_enrolled_users($context, 'block/analytics_graphs:viewpages', 0,
                    'u.id, u.firstname, u.lastname, u.email, u.suspended', 'firstname, lastname');
    foreach ($allteachers as $teacher) {
        if ($teacher->suspended == 0) {
            $teachers[] = $teacher;
        }
    }
    return($teachers);
}


function block_analytics_graphs_get_resource_url_access($course, $estudantes, $legacy) {
    global $COURSE;
    global $DB;
    foreach ($estudantes as $tupla) {
            $inclause[] = $tupla->id;
    }
    list($insql, $inparams) = $DB->get_in_or_equal($inclause);
    $resource = $DB->get_record('modules', array('name' => 'resource'), 'id');
    $url = $DB->get_record('modules', array('name' => 'url'), 'id');
    $page = $DB->get_record('modules', array('name' => 'page'), 'id');
    $startdate = $COURSE->startdate;
    
    /* Temp table to order */
    $params = array($course);
    $sql = "SELECT id, section, sequence
            FROM {course_sections} as cs
            WHERE course  = ? AND sequence <> ''
            ORDER BY section";
    $result = $DB->get_records_sql($sql, $params);

    $dbman = $DB->get_manager();
    $table = new xmldb_table('tmp_analytics_graphs');
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('section', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
    $table->add_field('module', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
    $table->add_field('sequence', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
    $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
    $dbman->create_temp_table($table);
    $sequence = 0;
    foreach($result as $tuple) {
        $modules = explode(',', $tuple->sequence);
        foreach($modules as $module) {
                               $record = new stdClass();
                    $record->section = $tuple->section;
                    $record->module = $module;
                    $record->sequence = $sequence++;
                    $DB->insert_record('tmp_analytics_graphs', $record, false);
            }
    }
           
                              
    $params = array_merge(array($startdate), $inparams, array($course, $resource->id, $url->id, $page->id));
    if (!$legacy) {
        $sql = "SELECT temp.id+(COALESCE(temp.userid,1)*1000000)as id, temp.id as ident, tag.section, m.name as tipo,
                    r.name as resource, u.name as url, p.name as page, temp.userid, usr.firstname,
                    usr.lastname, usr.email, temp.acessos, tag.sequence
                    FROM (
                        SELECT cm.id, log.userid, count(*) as acessos
                        FROM {course_modules} as cm
                        LEFT JOIN {logstore_standard_log} as log ON log.timecreated >= ?
                            AND log.userid $insql AND action = 'viewed' AND cm.id=log.contextinstanceid
                        WHERE cm.course = ? AND (cm.module=? OR cm.module=? OR cm.module=?)
                        GROUP BY cm.id, log.userid
                        ) as temp
                    LEFT JOIN {course_modules} as cm ON temp.id = cm.id
                    LEFT JOIN {modules} as m ON cm.module = m.id
                    LEFT JOIN {resource} as r ON cm.instance = r.id
                    LEFT JOIN {url} as u ON cm.instance = u.id
                    LEFT JOIN {page} as p ON cm.instance = p.id
                    LEFT JOIN {user} as usr ON usr.id = temp.userid
                    LEFT JOIN {tmp_analytics_graphs} as tag ON tag.module = cm.id
                    ORDER BY tag.sequence";
    } else {
        $sql = "SELECT temp.id+(COALESCE(temp.userid,1)*1000000)as id, temp.id as ident, cs.section, m.name as tipo,
                    r.name as resource, u.name as url, p.name as page, temp.userid, usr.firstname,
                    usr.lastname, usr.email, temp.acessos
                    FROM (
                        SELECT cm.id, log.userid, count(*) as acessos
                        FROM {course_modules} as cm
                        LEFT JOIN {log} as log ON log.time >= ?
                            AND log.userid $insql AND action = 'view' AND cm.id = log.cmid
                        WHERE cm.course = ? AND (cm.module=? OR cm.module=? OR cm.module=?)
                        GROUP BY cm.id, log.userid
                        ) as temp
                    LEFT JOIN {course_modules} as cm ON temp.id = cm.id
                    LEFT JOIN {course_sections} as cs ON cm.section = cs.id
                    LEFT JOIN {modules} as m ON cm.module = m.id
                    LEFT JOIN {resource} as r ON cm.instance = r.id
                    LEFT JOIN {url} as u ON cm.instance = u.id
                    LEFT JOIN {page} as p ON cm.instance = p.id
                    LEFT JOIN {user} as usr ON usr.id = temp.userid
                    ORDER BY cs.section, m.name, r.name, u.name, p.name";
    }
    $resultado = $DB->get_records_sql($sql, $params);
    $dbman->drop_table($table);
    return($resultado);
}


function block_analytics_graphs_get_assign_submission($course, $students) {
    global $DB;
    foreach ($students as $tuple) {
        $inclause[] = $tuple->id;
    }
    list($insql, $inparams) = $DB->get_in_or_equal($inclause);
    $assign = $DB->get_record('modules', array('name' => 'assign'), 'id');
    $params = array_merge(array($assign->id, $course), $inparams);
    $sql = "SELECT a.id+(COALESCE(s.id,1)*1000000)as id, a.id as assignment, name, duedate, cutoffdate,
                s.userid, usr.firstname, usr.lastname, usr.email, s.timemodified as timecreated
                FROM {assign} a
                LEFT JOIN {assign_submission} s on a.id = s.assignment AND s.status = 'submitted'
                LEFT JOIN {user} usr ON usr.id = s.userid
                LEFT JOIN {course_modules} cm on cm.instance = a.id AND cm.module = ?
                WHERE a.course = ? and nosubmissions = 0 AND (s.userid IS NULL OR s.userid $insql)
                    AND cm.visible = 1
                ORDER BY duedate, name, firstname";

     $resultado = $DB->get_records_sql($sql, $params);
        return($resultado);
}

function block_analytics_graphs_get_hotpot_submission($course, $students) {
    global $DB;
    foreach ($students as $tuple) {
        $inclause[] = $tuple->id;
    }
    list($insql, $inparams) = $DB->get_in_or_equal($inclause);
    $params = array_merge(array($course), $inparams);
    $sql = "SELECT temp.id+(COALESCE(temp.userid,1)*1000000) as id, temp.id as assignment, name,
                timeclose as duedate, timeclose as cutoffdate,
                temp.userid, usr.firstname, usr.lastname, usr.email, temp.timecreated
            FROM (
                SELECT h.id, ha.userid, MAX(ha.timefinish) as timecreated
                FROM {hotpot} h
                LEFT JOIN {hotpot_attempts} ha on h.id = ha.hotpotid AND ha.status = 4
                WHERE h.course = ? AND (ha.userid IS NULL OR ha.userid $insql)
                GROUP BY h.id, ha.userid

            ) temp
            LEFT JOIN {hotpot} h on h.id = temp.id
            LEFT JOIN {user} usr on usr.id = temp.userid
            ORDER BY duedate, name, firstname";

     $resultado = $DB->get_records_sql($sql, $params);
     return($resultado);
}


function block_analytics_graphs_get_quiz_submission($course, $students) {
    global $DB;
    foreach ($students as $tuple) {
        $inclause[] = $tuple->id;
    }
    list($insql, $inparams) = $DB->get_in_or_equal($inclause);
    $params = array_merge(array($course), $inparams);
    $sql = "SELECT temp.id+(COALESCE(temp.userid,1)*1000000) as id, temp.id as assignment, name,
                timeclose as duedate, timeclose as cutoffdate,
                temp.userid, usr.firstname, usr.lastname, usr.email, temp.timecreated
            FROM (
                SELECT q.id, qa.userid, MAX(qa.timefinish) as timecreated
                FROM {quiz} q
                LEFT JOIN {quiz_attempts} qa on q.id = qa.quiz AND qa.state = 'finished'
                WHERE q.course = ? AND (qa.userid IS NULL OR qa.userid $insql)
                GROUP BY q.id, qa.userid
            ) temp
            LEFT JOIN {quiz} q on q.id = temp.id
            LEFT JOIN {user} usr on usr.id = temp.userid
            ORDER BY duedate, name, firstname";

     $resultado = $DB->get_records_sql($sql, $params);
     return($resultado);
}



function block_analytics_graphs_get_number_of_days_access_by_week($course, $estudantes, $startdate, $legacy=0) {
    global $DB;
    $timezone = new DateTimeZone(core_date::get_server_timezone());
    $timezoneadjust   = $timezone->getOffset(new DateTime);
    foreach ($estudantes as $tupla) {
        $inclause[] = $tupla->id;
    }
    list($insql, $inparams) = $DB->get_in_or_equal($inclause);
    $params = array_merge(array($timezoneadjust, $timezoneadjust, $startdate, $course, $startdate), $inparams);
    if (!$legacy) {
        $sql = "SELECT temp2.userid+(week*1000000) as id, temp2.userid, firstname, lastname, email, week,
                number, numberofpageviews
                FROM (
                    SELECT temp.userid, week, COUNT(*) as number, SUM(numberofpageviews) as numberofpageviews
                    FROM (
                        SELECT MIN(log.id) as id, log.userid,
                            FLOOR((log.timecreated + ?)/ 86400)   as day,
                            FLOOR( (((log.timecreated  + ?) / 86400) - (?/86400))/7) as week,
                            COUNT(*) as numberofpageviews
                        FROM {logstore_standard_log} as log
                        WHERE courseid = ? AND action = 'viewed' AND target = 'course'
                            AND log.timecreated >= ? AND log.userid $insql
                        GROUP BY userid, day, week
                    ) as temp
                    GROUP BY week, temp.userid
                ) as temp2
                LEFT JOIN {user} usr ON usr.id = temp2.userid
                ORDER BY LOWER(firstname), LOWER(lastname),userid, week";
    } else {
        $sql = "SELECT temp2.userid+(week*1000000) as id, temp2.userid, firstname, lastname, email, week,
                number, numberofpageviews
                FROM (
                    SELECT temp.userid, week, COUNT(*) as number, SUM(numberofpageviews) as numberofpageviews
                    FROM (
                        SELECT MIN(log.id) as id, log.userid,
                            FLOOR((log.time + ?)/ 86400)   as day,
                            FLOOR( (((log.time  + ?) / 86400) - (?/86400))/7) as week,
                            COUNT(*) as numberofpageviews
                        FROM {log} as log
                        WHERE course = ? AND action = 'view' AND module = 'course'
                            AND log.time >= ? AND log.userid $insql
                        GROUP BY userid, day, week
                    ) as temp
                    GROUP BY week, temp.userid
                ) as temp2
                LEFT JOIN {user} usr ON usr.id = temp2.userid
                ORDER BY LOWER(firstname), LOWER(lastname),userid, week";
    }
    $resultado = $DB->get_records_sql($sql, $params);
    return($resultado);
}


function block_analytics_graphs_get_number_of_modules_access_by_week($course, $estudantes, $startdate, $legacy=0) {
    global $DB;
    $timezone = new DateTimeZone(core_date::get_server_timezone());
    $timezoneadjust   = $timezone->getOffset(new DateTime);
    foreach ($estudantes as $tupla) {
        $inclause[] = $tupla->id;
    }
    list($insql, $inparams) = $DB->get_in_or_equal($inclause);
    $params = array_merge(array($timezoneadjust, $startdate, $course, $startdate), $inparams);
    if (!$legacy) {
        $sql = "SELECT userid+(week*1000000), userid, firstname, lastname, email, week, number
                FROM (
                    SELECT  userid, week, COUNT(*) as number
                    FROM (
                        SELECT log.userid, objecttable, objectid,
                        FLOOR((((log.timecreated + ?) / 86400) - (?/86400))/7) as week
                        FROM {logstore_standard_log} log
                        WHERE courseid = ? AND action = 'viewed' AND target = 'course_module'
                        AND log.timecreated >= ? AND log.userid $insql
                        GROUP BY userid, week, objecttable, objectid
                    ) as temp
                    GROUP BY userid, week
                ) as temp2
                LEFT JOIN {user} usr ON usr.id = temp2.userid
                ORDER by LOWER(firstname), LOWER(lastname), userid, week";
    } else {
        $sql = "SELECT userid+(week*1000000), userid, firstname, lastname, email, week, number
                FROM (
                    SELECT  userid, week, COUNT(*) as number
                    FROM (
                        SELECT log.userid, module, cmid,
                        FLOOR((((log.time + ?) / 86400) - (?/86400))/7) as week
                        FROM {log} log
                        WHERE course = ? AND (action = 'view' OR action = action = 'view forum')
                            AND module <> 'assign' AND cmid <> 0 AND time >= ? AND log.userid $insql
                        GROUP BY userid, week, module, cmid
                    ) as temp
                    GROUP BY userid, week
                ) as temp2
                LEFT JOIN {user} usr ON usr.id = temp2.userid
                ORDER by LOWER(firstname), LOWER(lastname), userid, week";
    }
    $resultado = $DB->get_records_sql($sql, $params);
    return($resultado);
}

function block_analytics_graphs_get_number_of_modules_accessed($course, $estudantes, $startdate, $legacy=0) {
    global $DB;
    foreach ($estudantes as $tupla) {
        $inclause[] = $tupla->id;
    }
    list($insql, $inparams) = $DB->get_in_or_equal($inclause);
    $params = array_merge(array($course, $startdate), $inparams);
    if (!$legacy) {
        $sql = "SELECT userid, COUNT(*) as number
            FROM (
                SELECT log.userid, objecttable, objectid
                FROM {logstore_standard_log} as log
                LEFT JOIN {user} usr ON usr.id = log.userid
                WHERE courseid = ? AND action = 'viewed' AND target = 'course_module'
                    AND log.timecreated >= ? AND log.userid $insql
                GROUP BY log.userid, objecttable, objectid
            ) as temp
            GROUP BY userid
            ORDER by userid";
    } else {
        $sql = "SELECT userid, COUNT(*) as number
            FROM (
                SELECT log.userid, module, cmid
                FROM {log} as log
                LEFT JOIN {user} usr ON usr.id = log.userid
                WHERE course = ? AND (action = 'view' OR action = 'view forum')
                    AND module <> 'assign' AND cmid <> 0  AND log.time >= ? AND log.userid $insql
                GROUP BY log.userid, module, cmid
            ) as temp
            GROUP BY userid
            ORDER by userid";
    }
    $resultado = $DB->get_records_sql($sql, $params);
    return($resultado);
}


function block_analytics_graphs_get_user_resource_url_page_access($course, $student, $legacy) {
    global $COURSE;
    global $DB;

    $resource = $DB->get_record('modules', array('name' => 'resource'), 'id');
    $url = $DB->get_record('modules', array('name' => 'url'), 'id');
    $page = $DB->get_record('modules', array('name' => 'page'), 'id');
    $startdate = $COURSE->startdate;
    $params = array($startdate, $student, $course, $resource->id, $url->id, $page->id);
    if (!$legacy) {
        $sql = "SELECT temp.id, m.name as tipo,
                    r.name as resource, u.name as url, p.name as page, COALESCE(temp.userid,0) as userid,  temp.acessos
                    FROM (
                        SELECT cm.id, log.userid, count(*) as acessos
                        FROM {course_modules} cm
                        LEFT JOIN {logstore_standard_log} log ON log.timecreated >= ?
                            AND log.userid = ? AND action = 'viewed' AND cm.id=log.contextinstanceid
                        WHERE cm.course = ? AND (cm.module=? OR cm.module=? OR cm.module=?) AND cm.visible = 1
                        GROUP BY cm.id, log.userid
                        ) as temp
                    LEFT JOIN {course_modules} cm ON temp.id = cm.id
                    LEFT JOIN {modules} m ON cm.module = m.id
                    LEFT JOIN {resource} r ON cm.instance = r.id
                    LEFT JOIN {url} u ON cm.instance = u.id
                    LEFT JOIN {page} p ON cm.instance = p.id
                    ORDER BY m.name, r.name, u.name, p.name";
    } else {
        $sql = "SELECT temp.id, m.name as tipo,
                    r.name as resource, u.name as url, p.name as page, COALESCE(temp.userid,0) as userid,  temp.acessos
                    FROM (
                        SELECT cm.id, log.userid, count(*) as acessos
                        FROM {course_modules} cm
                        LEFT JOIN {log} log ON log.time >= ?
                            AND log.userid =? AND action = 'view' AND cm.id = log.cmid
                        WHERE cm.course = ? AND (cm.module=? OR cm.module=? OR cm.module=?) AND cm.visible = 1
                        GROUP BY cm.id, log.userid
                        ) as temp
                    LEFT JOIN {course_modules} cm ON temp.id = cm.id
                    LEFT JOIN {modules} m ON cm.module = m.id
                    LEFT JOIN {resource} r ON cm.instance = r.id
                    LEFT JOIN {url} u ON cm.instance = u.id
                    LEFT JOIN {page} p ON cm.instance = p.id
                    ORDER BY m.name, r.name, u.name, p.name";
    }
    $result = $DB->get_records_sql($sql, $params);
    return($result);

}


function block_analytics_graphs_get_user_assign_submission($course, $student) {
    global $DB;
    $assign = $DB->get_record('modules', array('name' => 'assign'), 'id');
    $params = array($student, $assign->id, $course);
    $sql = "SELECT  a.id, name, COALESCE(duedate, 0) as duedate, COALESCE(s.timemodified,0) as timecreated
                FROM {assign} a
                LEFT JOIN {assign_submission} s on a.id = s.assignment AND s.status = 'submitted' AND s.userid = ?
                LEFT JOIN {course_modules} cm on cm.instance = a.id AND cm.module = ?
                WHERE a.course = ? and nosubmissions = 0 AND cm.visible=1
                ORDER BY name";
     $result = $DB->get_records_sql($sql, $params);
     return($result);
}

/**
 * This function extends the navigation with the report items
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param stdClass $course The course to object for the report
 * @param context $context The context of the course
 */
function block_analytics_graphs_extend_navigation_course($navigation, $course, $context) {
    global $CFG;
    $reports = $navigation->find('coursereports', navigation_node::TYPE_CONTAINER);
    if (has_capability('block/analytics_graphs:viewpages', $context) && $reports) {
        $report_analytics_graphs = $reports->add(get_string('pluginname', 'block_analytics_graphs'));
        $url = new moodle_url($CFG->wwwroot.'/blocks/analytics_graphs/grades_chart.php', array('id'=>$course->id));
        $report_analytics_graphs->add(get_string('grades_chart', 'block_analytics_graphs'), $url,
            navigation_node::TYPE_SETTING, null, null, new pix_icon('i/report', ''));
        $url = new moodle_url($CFG->wwwroot.'/blocks/analytics_graphs/graphresourceurl.php', 
            array('id'=>$course->id, 'legacy'=>'0'));
        $report_analytics_graphs->add(get_string('access_to_contents', 'block_analytics_graphs'), $url,
            navigation_node::TYPE_SETTING, null, null, new pix_icon('i/report', ''));
        $url = new moodle_url($CFG->wwwroot.'/blocks/analytics_graphs/assign.php', array('id'=>$course->id));
        $report_analytics_graphs->add(get_string('submissions_assign', 'block_analytics_graphs'), $url,
            navigation_node::TYPE_SETTING, null, null, new pix_icon('i/report', ''));
        $url = new moodle_url($CFG->wwwroot.'/blocks/analytics_graphs/quiz.php', array('id'=>$course->id));
        $report_analytics_graphs->add(get_string('submissions_quiz', 'block_analytics_graphs'), $url, 
            navigation_node::TYPE_SETTING, null, null, new pix_icon('i/report', ''));
        $url = new moodle_url($CFG->wwwroot.'/blocks/analytics_graphs/hits.php', array('id'=>$course->id, 'legacy'=>'0'));
        $report_analytics_graphs->add(get_string('hits_distribution', 'block_analytics_graphs'), $url,         
            navigation_node::TYPE_SETTING, null, null, new pix_icon('i/report', ''));
    }
}

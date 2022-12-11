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

defined('MOODLE_INTERNAL') || die();

function block_analytics_graphs_subtract_student_arrays($estudantes, $acessaram) {
    $resultado = array();
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
    global $DB, $USER;
    $groupmembers = array();
    $groups = groups_get_all_groups($course->id);
    foreach ($groups as $group) {
        if (groups_group_visible($group->id, $course)) {
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
    }
    return($groupmembers);
}

function block_analytics_graphs_get_course_grouping_members($course) {
    global $DB, $USER;
    $groupingmembers = array();
    $groupings = groups_get_all_groupings($course->id);
    foreach ($groupings as $grouping) {
        $groups = groups_get_all_groups($course->id, 0, $grouping->id);
        $members = [];
        foreach ($groups as $group) {
            // Only get members from a group which is visible to current user.
            if (groups_group_visible($group->id, $course)) {
                $groupmembers = groups_get_members($group->id, 'u.id');
                $members = array_merge($members, array_keys($groupmembers));
            }
        }
        // Only return "non-empty" grouping.
        if (!empty($members)) {
            sort($members);
            $groupingmembers[$grouping->id]['name'] = $grouping->name;
            $groupingmembers[$grouping->id]['members'] = $members;
            $groupingmembers[$grouping->id]['numberofmembers']  = count($members);
        }
    }
    return($groupingmembers);
}


function block_analytics_graphs_get_students($course) {
    global $DB, $USER;
    $students = array();
    $context = context_course::instance($course->id);
    $allstudents = get_enrolled_users($context, 'block/analytics_graphs:bemonitored', 0,
                    'u.id, u.firstname, u.lastname, u.email, u.suspended', 'firstname, lastname');
    foreach ($allstudents as $student) {
        if ($student->suspended == 0) {
            if (groups_user_groups_visible($course, $student->id)) {
                $students[] = $student;
            }
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

function block_analytics_graphs_generate_graph_startup_module_entry ($iconhtml, $name, $value, $title) {

    return      "<div style='height: 24px;line-height: 24px;text-align: left;border: 1px solid lightgrey;" .
                "margin-bottom: 2px; margin-top: 8px'>" .
                "<div style='display: table;'>" .
                $iconhtml .
                "<div style='display: table-cell; vertical-align: middle;'>" .
                "<input type='checkbox' id='selectable' name='" . $name . "' value='" . $value . "'>" . $title . "</div>" .
                "</div></div>";
}

function block_analytics_graphs_get_course_used_modules ($courseid) {
    global $DB;

    $sql = "SELECT cm.module, md.name
            FROM {course_modules} cm
            LEFT JOIN {modules} md ON cm.module = md.id
            WHERE cm.course = ? AND md.name <> 'label'
            GROUP BY cm.module, md.name";
    $params = array($courseid);
    $result = $DB->get_records_sql($sql, $params);

    return $result;
}

function block_analytics_graphs_get_resource_url_access($course, $estudantes, $requestedtypes, $startdate, $hidden) {
    global $COURSE;
    global $DB;
    foreach ($estudantes as $tupla) {
        $inclause[] = $tupla->id;
    }
    list($insql, $inparams) = $DB->get_in_or_equal($inclause);

    $requestedmodules = array($course); // First parameter is courseid, later are modulesids to display.

    foreach ($requestedtypes as $module) { // Making params for the table.
        $temp = $resource = $DB->get_record('modules', array('name' => $module), 'id');
        array_push($requestedmodules, $temp->id);
    }

    /* Temp table to order */
    $params = array($course);
    $sql = "SELECT id, section, sequence
            FROM {course_sections}
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
    foreach ($result as $tuple) {
        $modules = explode(',', $tuple->sequence);
        foreach ($modules as $module) {
            $record = new stdClass();
            $record->section = $tuple->section;
            $record->module = $module;
            $record->sequence = $sequence++;
            $DB->insert_record('tmp_analytics_graphs', $record, false);
        }
    }

    $params = array_merge(array($startdate), $inparams, $requestedmodules);

    $sqla = "SELECT temp.id+(COALESCE(temp.userid,1)*1000000)as id, temp.id as ident, tag.section, m.name as tipo, cm.id as cmid, ";
    $sqlb = "temp.userid, usr.firstname, usr.lastname, usr.email, temp.acessos, tag.sequence
                    FROM (
                        SELECT cm.id, log.userid, count(*) as acessos
                        FROM {course_modules} cm
                        LEFT JOIN {logstore_standard_log} log ON log.timecreated >= ?
                            AND log.userid $insql AND (action = 'viewed' OR action = 'submission') AND cm.id=log.contextinstanceid
                        WHERE cm.course = ?";
    if ($hidden) {
        $sqlb .= " AND (";
    } else {
        $sqlb .= " AND cm.visible=1 AND (";
    }

    $sqlc = "cm.module=?";

    if (count($requestedmodules) >= 2) {
        for ($i = 2; $i < count($requestedmodules); $i++) {
            $sqlc .= " OR cm.module=?";
        }
    }

    $sqld = ")
                        GROUP BY cm.id, log.userid
                        ) as temp
                    LEFT JOIN {course_modules} cm ON temp.id = cm.id
                    LEFT JOIN {modules} m ON cm.module = m.id
                    ";
    $sqle = "   LEFT JOIN {user} usr ON usr.id = temp.userid
                    LEFT JOIN {tmp_analytics_graphs} tag ON tag.module = cm.id
                    ORDER BY tag.sequence";

    $sql = $sqla . $sqlb . $sqlc . $sqld . $sqle;

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

function block_analytics_graphs_get_turnitin_submission($course, $students) {
    global $DB;
    foreach ($students as $tuple) {
        $inclause[] = $tuple->id;
    }
    list($insql, $inparams) = $DB->get_in_or_equal($inclause);
    $params = array_merge(array($course), $inparams);
    $sql = "SELECT temp.id+(COALESCE(temp.userid,1)*1000000) as id, temp.id as assignment, CONCAT(t.name, '-', tp.partname) as name,
                dtdue as duedate, dtdue as cutoffdate,
                temp.userid, usr.firstname, usr.lastname, usr.email, temp.timecreated
            FROM (
                SELECT t.id, ts.userid, MAX(tp.dtdue) as timecreated
                FROM {turnitintooltwo} t
                LEFT JOIN {turnitintooltwo_submissions} ts on t.id = ts.turnitintooltwoid
        LEFT JOIN {turnitintooltwo_parts} tp on t.id = tp.turnitintooltwoid
                WHERE t.course = ? AND (ts.userid IS NULL OR ts.userid $insql)
                GROUP BY t.id, ts.userid
            ) temp
            LEFT JOIN {turnitintooltwo} t on t.id = temp.id
        LEFT JOIN {turnitintooltwo_parts} tp on t.id = tp.turnitintooltwoid
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



function block_analytics_graphs_get_number_of_days_access_by_week($course, $estudantes, $startdate) {
    global $DB;
    $timezone = new DateTimeZone(core_date::get_server_timezone());
    $timezoneadjust   = $timezone->getOffset(new DateTime);
    foreach ($estudantes as $tupla) {
        $inclause[] = $tupla->id;
    }
    list($insql, $inparams) = $DB->get_in_or_equal($inclause);
    $params = array_merge(array($timezoneadjust, $timezoneadjust, $startdate, $course, $startdate), $inparams);
    $sql = "SELECT temp2.userid+(week*1000000) as id, temp2.userid, firstname, lastname, email, week,
                number, numberofpageviews
                FROM (
                    SELECT temp.userid, week, COUNT(*) as number, SUM(numberofpageviews) as numberofpageviews
                    FROM (
                        SELECT MIN(log.id) as id, log.userid,
                            FLOOR((log.timecreated + ?)/ 86400)   as day,
                            FLOOR( (((log.timecreated  + ?) / 86400) - (?/86400))/7) as week,
                            COUNT(*) as numberofpageviews
                        FROM {logstore_standard_log} log
                        WHERE courseid = ? AND action = 'viewed' AND target = 'course'
                            AND log.timecreated >= ? AND log.userid $insql
                        GROUP BY userid, day, week
                    ) as temp
                    GROUP BY week, temp.userid
                ) as temp2
                LEFT JOIN {user} usr ON usr.id = temp2.userid
                ORDER BY LOWER(firstname), LOWER(lastname),userid, week";
    $resultado = $DB->get_records_sql($sql, $params);
    return($resultado);
}

function block_analytics_graphs_get_accesses_last_days($course, $estudantes, $daystoget) {
    global $DB;
    $date = strtotime(date('Y-m-d', strtotime('-'. $daystoget .' days')));
    $sql = "SELECT s.id, s.action, s.target, s.userid, s.courseid, s.timecreated, usr.firstname, usr.lastname
            FROM {logstore_standard_log} s
            LEFT JOIN {user} usr ON s.userid = usr.id
            WHERE s.courseid = " . $course . " AND s.timecreated >= " . $date . "
            AND (";
    $iterator = 0;
    foreach ($estudantes as $item) {
        if ($iterator == 0) {
            $sql .= " s.userid = " . $item->id;
        } else {
            $sql .= " OR s.userid = " . $item->id;
        }
        $iterator++;
    }
    $sql .= " )
             ORDER BY s.timecreated";
    $resultado = $DB->get_records_sql($sql);

    foreach ($resultado as $item) {
        $item->timecreated = date("His", $item->timecreated);
    }

    return($resultado);
}

function block_analytics_graphs_get_number_of_modules_access_by_week($course, $estudantes, $startdate) {
    global $DB;
    $timezone = new DateTimeZone(core_date::get_server_timezone());
    $timezoneadjust   = $timezone->getOffset(new DateTime);
    foreach ($estudantes as $tupla) {
        $inclause[] = $tupla->id;
    }
    list($insql, $inparams) = $DB->get_in_or_equal($inclause);
    $params = array_merge(array($timezoneadjust, $startdate, $course, $startdate), $inparams);
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
    $resultado = $DB->get_records_sql($sql, $params);
    return($resultado);
}

function block_analytics_graphs_get_number_of_modules_accessed($course, $estudantes, $startdate) {
    global $DB;
    foreach ($estudantes as $tupla) {
        $inclause[] = $tupla->id;
    }
    list($insql, $inparams) = $DB->get_in_or_equal($inclause);
    $params = array_merge(array($course, $startdate), $inparams);
    $sql = "SELECT userid, COUNT(*) as number
            FROM (
                SELECT log.userid, objecttable, objectid
                FROM {logstore_standard_log} log
                LEFT JOIN {user} usr ON usr.id = log.userid
                WHERE courseid = ? AND action = 'viewed' AND target = 'course_module'
                    AND log.timecreated >= ? AND log.userid $insql
                GROUP BY log.userid, objecttable, objectid
            ) as temp
            GROUP BY userid
            ORDER by userid";
    $resultado = $DB->get_records_sql($sql, $params);
    return($resultado);
}


function block_analytics_graphs_get_user_resource_url_page_access($course, $student) {
    global $COURSE;
    global $DB;

    $requestedmodules = block_analytics_graphs_get_course_used_modules($course);

    $startdate = $COURSE->startdate;

    $paramsdefault = array($startdate, $student, $course);
    $paramsids = array();
    $sqla = "SELECT temp.id, m.name as tipo, cm.id as cmid, ";
    $sqlb = "COALESCE(temp.userid,0) as userid,  temp.acessos
                    FROM (
                        SELECT cm.id, log.userid, count(*) as acessos
                        FROM {course_modules} cm
                        LEFT JOIN {logstore_standard_log} log ON log.timecreated >= ?
                            AND log.userid = ? AND action = 'viewed' AND cm.id=log.contextinstanceid
                        WHERE cm.course = ? AND (";

    $sqlc = "cm.module=?";

    if (count ($requestedmodules) >= 2) {
        for ($i = 2; $i <= count($requestedmodules); $i++) {
            $sqlc .= " OR cm.module=?";
        }
    }

    $sqld = ") AND cm.visible = 1
                        GROUP BY cm.id, log.userid
                        ) as temp
                    LEFT JOIN {course_modules} cm ON temp.id = cm.id
                    LEFT JOIN {modules} m ON cm.module = m.id
                    ";
    $sqle = "ORDER BY m.name";

    foreach ($requestedmodules as $module) {
        $temp = $DB->get_record('modules', array('name' => $module->name), 'id');
        array_push($paramsdefault, $temp->id);
    }

    $sql = $sqla . $sqlb . $sqlc . $sqld . $sqle;
    $params = array_merge($paramsdefault, $paramsids);
    $result = $DB->get_records_sql($sql, $paramsdefault);

    $modinfo = get_fast_modinfo($course);
    foreach($result as $object){
            $cm = $modinfo->get_cm($object->cmid);
            $object->cmid = $cm->name;
    }
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

function block_analytics_graphs_get_user_forum_state($course, $student) {
    global $DB;
    $forum = $DB->get_record('modules', array('name' => 'forum'), 'id');
    $params = array($student, $forum->id, $course);
    $sql = "SELECT b.id discussionid, a.name forumname, b.name discussionname, b.timemodified lastupdate
            FROM {forum} a
            LEFT JOIN {forum_discussions} b on a.id = b.forum
            WHERE a.course = " . $course . "
            ORDER BY a.name";
    $totaldiscussions = $DB->get_records_sql($sql, $params);

    $sql = "SELECT b.id discussionid, b.name discussionname, c.lastread lastread
            FROM {forum} a
            LEFT JOIN {forum_discussions} b on a.id = b.forum
            LEFT JOIN {forum_read} c on b.id = c.discussionid
            WHERE a.course = " . $course ." AND c.userid = " . $student;
    $totaldiscreadbyuser = $DB->get_records_sql($sql, $params);

    $sql = "SELECT b.id discussionid, b.name discussionname
            FROM {forum} a
            LEFT JOIN {forum_discussions} b on a.id = b.forum
            LEFT JOIN {forum_posts} c on b.id = c.discussion
            WHERE a.course = " . $course ." AND c.userid = " . $student;
    $totaldiscpostsbyuser = $DB->get_records_sql($sql, $params);

    $read = array(); // Generating arrays.
    $notread = array();
    $posted = array();
    $notposted = array();

    foreach ($totaldiscussions as $item) {
        $foundread = false;
        $foundpost = false;
        foreach ($totaldiscreadbyuser as $subitem) {
            if ($item->discussionid == $subitem->discussionid) {
                if (!empty($item->discussionname)) {
                    array_push($read, $item->forumname . ": " . $item->discussionname);
                }
                $foundread = true;
                break;
            }
        }
        if (!$foundread) {
            if (!empty($item->discussionname)) {
                array_push($notread, $item->forumname . ": " . $item->discussionname);
            }
        }

        foreach ($totaldiscpostsbyuser as $subitem) {
            if ($item->discussionid == $subitem->discussionid) {
                if (!empty($item->discussionname)) {
                    array_push($posted, $item->forumname . ": " . $item->discussionname);
                }
                $foundpost = true;
                break;
            }
        }
        if (!$foundpost) {
            if (!empty($item->discussionname)) {
                array_push($notposted, $item->forumname . ": " . $item->discussionname);
            }
        }
    }

    $result = array(); // Merging arrays.

    $i = 0;
    foreach ($read as $item) {
        $result['read'][$i++] = $item;
    }

    $i = 0;
    foreach ($notread as $item) {
        $result['notread'][$i++] = $item;
    }

    $i = 0;
    foreach ($posted as $item) {
        $result['posted'][$i++] = $item;
    }

    $i = 0;
    foreach ($notposted as $item) {
        $result['notposted'][$i++] = $item;
    }

    return($result);
}

function block_analytics_graphs_get_course_name($course) {
    global $DB;
    $sql = "SELECT
              a.fullname
            FROM
              {course} a
            WHERE
              a.id = " . $course;
    $result = $DB->get_records_sql($sql);

    $resultname = "";

    foreach ($result as $item) {
        if (!empty($item)) {
            $resultname = $item->fullname;
        }
    }

    return $resultname;
}

function block_analytics_graphs_get_logstore_loglife() {
    global $DB;
    $sql = "SELECT  a.id, a.plugin, a.name, a.value
                FROM {config_plugins} a
                WHERE a.name = 'loglifetime' AND a.plugin = 'logstore_standard'
                ORDER BY name";
    $result = $DB->get_records_sql($sql);
    return reset($result)->value;
}

function block_analytics_graphs_get_course_days_since_startdate($course) {
    global $DB;
    $sql = "SELECT  a.id, a.startdate
                FROM {course} a
                WHERE a.id = " . $course;
    $result = $DB->get_records_sql($sql);
    $startdate = reset($result)->startdate;
    $currentdate = time();
    return floor(($currentdate - $startdate) / (60 * 60 * 24));
}

function block_analytics_graphs_get_user_quiz_state($course, $student) {
    global $DB;
    $quiz = $DB->get_record('modules', array('name' => 'quiz'), 'id');
    $params = array($student, $quiz->id, $course);
    $sql = "SELECT  a.id, a.name, s.gradepass
                FROM {quiz} a
                LEFT JOIN {grade_items} s on a.id = s.iteminstance and s.itemmodule = 'quiz'
                WHERE a.course = " . $course . "
                ORDER BY name";
    $resultallquizes = $DB->get_records_sql($sql, $params);
    $allquizes = array();
    foreach ($resultallquizes as $item) {
        array_push($allquizes, $item->name);
    }
    $sql = "SELECT  a.id, a.name, s.grade
                FROM {quiz} a
                LEFT JOIN {quiz_grades} s on a.id = s.quiz
                WHERE s.userid = ? AND a.course = " . $course . "
                ORDER BY name";
    $resultstudentquizes = $DB->get_records_sql($sql, $params);

    $passed = array(); // generating arrays
    $failed = array();
    $noaccess = array();

    foreach ($resultstudentquizes as $item) {
        foreach ($resultallquizes as $subitem) {
            if ($item->id == $subitem->id) {
                if ($item->grade >= $subitem->gradepass) {
                    array_push($passed, $item->name);
                } else {
                    array_push($failed, $item->name);
                }
            }
        }
    }

    foreach ($resultallquizes as $item) {
        if (!in_array($item->name, $passed) && !in_array($item->name, $failed)) {
            array_push($noaccess, $item->name);
        }
    }

    $result = array(); // merging arrays

    $i = 0;
    foreach ($passed as $item) {
        $result['passed'][$i++] = $item;
    }

    $i = 0;
    foreach ($failed as $item) {
        $result['failed'][$i++] = $item;
    }

    $i = 0;
    foreach ($noaccess as $item) {
        $result['noaccess'][$i++] = $item;
    }

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
    global $DB;
    $reportanalyticsgraphs = $navigation->find('coursereports', navigation_node::TYPE_CONTAINER);
    if (has_capability('block/analytics_graphs:viewpages', $context) && $reportanalyticsgraphs) {
        $sql = "SELECT cm.module, md.name
            FROM {course_modules} cm
            LEFT JOIN {modules} md ON cm.module = md.id
            WHERE cm.course = ?
            GROUP BY cm.module, md.name";
        $params = array($course->id);
        $availablemodulestotal = $DB->get_records_sql($sql, $params);
        $availablemodules = array();
        foreach ($availablemodulestotal as $result) {
            array_push($availablemodules, $result->name);
        }

        $url = new moodle_url($CFG->wwwroot.'/blocks/analytics_graphs/grades_chart.php', array('id' => $course->id));
        $node = navigation_node::create(get_string('grades_chart', 'block_analytics_graphs'),
                $url, navigation_node::TYPE_SETTING, null, null, new pix_icon('i/report', ''));
        $reportanalyticsgraphs->add_node($node);

        $url = new moodle_url($CFG->wwwroot.'/blocks/analytics_graphs/graphresourcestartup.php', array('id' => $course->id));
        $node = navigation_node::create(get_string('access_to_contents', 'block_analytics_graphs'),
                $url, navigation_node::TYPE_SETTING, null, null, new pix_icon('i/report', ''));
        $reportanalyticsgraphs->add_node($node);

        $url = new moodle_url($CFG->wwwroot.'/blocks/analytics_graphs/timeaccesseschart.php',
                array('id' => $course->id, 'days' => '7'));
        $node = navigation_node::create(get_string('timeaccesschart_title', 'block_analytics_graphs'),
                $url, navigation_node::TYPE_SETTING, null, null, new pix_icon('i/report', ''));
        $reportanalyticsgraphs->add_node($node);

        if (in_array("assign", $availablemodules)) {
            $url = new moodle_url($CFG->wwwroot . '/blocks/analytics_graphs/assign.php', array('id' => $course->id));
            $node = navigation_node::create(get_string('submissions_assign', 'block_analytics_graphs'),
                            $url, navigation_node::TYPE_SETTING, null, null, new pix_icon('i/report', ''));
            $reportanalyticsgraphs->add_node($node);
        }

        if (in_array("hotpot", $availablemodules)) {
            $url = new moodle_url($CFG->wwwroot.'/blocks/analytics_graphs/hotpot.php', array('id' => $course->id));
            $node = navigation_node::create(get_string('submissions_hotpot', 'block_analytics_graphs'),
                            $url, navigation_node::TYPE_SETTING, null, null, new pix_icon('i/report', ''));
            $reportanalyticsgraphs->add_node($node);
        }

        if (in_array("turnitintooltwo", $availablemodules)) {
            $url = new moodle_url($CFG->wwwroot.'/blocks/analytics_graphs/turnitin.php', array('id' => $course->id));
            $node = navigation_node::create(get_string('submissions_turnitin', 'block_analytics_graphs'),
                            $url, navigation_node::TYPE_SETTING, null, null, new pix_icon('i/report', ''));
            $reportanalyticsgraphs->add_node($node);
        }

        $url = new moodle_url($CFG->wwwroot.'/blocks/analytics_graphs/hits.php', array('id' => $course->id,));
        $node = navigation_node::create(get_string('hits_distribution', 'block_analytics_graphs'),
                $url, navigation_node::TYPE_SETTING, null, null, new pix_icon('i/report', ''));
        $reportanalyticsgraphs->add_node($node);
    }
}

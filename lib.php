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

function block_analytics_graphs_generate_graph_startup_module_entry ($iconhtml, $name, $value, $title) {

    return      "<div style='height: 24px;line-height: 24px;text-align: left;border: 1px solid lightgrey;" .
                "margin-bottom: 2px; margin-top: 8px'>" .
                "<div style='display: table;'>" .
                $iconhtml .
                "<div style='display: table-cell; vertical-align: middle;'>" .
                "<input type='checkbox' id='selectable' name='" . $name . "' value='" . $value . "'>" . $title . "</div>" .
                "</div></div>";
}

function block_analytics_graphs_get_course_used_modules ($courseID) {
    global $DB;

    $sql = "SELECT cm.module, md.name 
            FROM {course_modules} as cm 
            LEFT JOIN {modules} as md ON cm.module = md.id 
            WHERE cm.course = ? 
            GROUP BY cm.module";
    $params = array($courseID);
    $result = $DB->get_records_sql($sql, $params);

    return $result;
}

function block_analytics_graphs_get_resource_url_access($course, $estudantes, $requestedTypes) {
    global $COURSE;
    global $DB;
    foreach ($estudantes as $tupla) {
        $inclause[] = $tupla->id;
    }
    list($insql, $inparams) = $DB->get_in_or_equal($inclause);

    $requestedModules = array($course); //first parameter is courseid, later are modulesids to display

    foreach ($requestedTypes as $module) { //making params for the table
        $temp = $resource = $DB->get_record('modules', array('name' => $module), 'id');
        array_push($requestedModules, $temp->id);
    }

    $startdate = $COURSE->startdate;

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

    $params = array_merge(array($startdate), $inparams, $requestedModules);

        $sqlA = "SELECT temp.id+(COALESCE(temp.userid,1)*1000000)as id, temp.id as ident, tag.section, m.name as tipo, ";
        $sqlB = "temp.userid, usr.firstname, usr.lastname, usr.email, temp.acessos, tag.sequence
                    FROM (
                        SELECT cm.id, log.userid, count(*) as acessos
                        FROM {course_modules} as cm
                        LEFT JOIN {logstore_standard_log} as log ON log.timecreated >= ?
                            AND log.userid $insql AND action = 'viewed' AND cm.id=log.contextinstanceid
                        WHERE cm.course = ? AND (";
        $sqlC = "cm.module=?";

        if (count ($requestedModules) >= 2) {
            for ($i = 2; $i < count($requestedModules); $i++) {
                $sqlC .= " OR cm.module=?";
            }
        }

        $sqlD =")
                        GROUP BY cm.id, log.userid
                        ) as temp
                    LEFT JOIN {course_modules} as cm ON temp.id = cm.id
                    LEFT JOIN {modules} as m ON cm.module = m.id
                    ";
        $sqlE = "   LEFT JOIN {user} as usr ON usr.id = temp.userid
                    LEFT JOIN {tmp_analytics_graphs} as tag ON tag.module = cm.id
                    ORDER BY tag.sequence";

        foreach ($requestedTypes as $type) {
            switch ($type) { //probably unnecessary, but here it is fine I think, at least for readability
                case "activequiz" :
                    $sqlA.= "avq.name as activequiz, ";
                    $sqlD.= "LEFT JOIN {activequiz} as avq ON cm.instance = avq.id
            ";
                    break;
                case "assign" :
                    $sqlA.= "asn.name as assign, ";
                    $sqlD.= "LEFT JOIN {assign} as asn ON cm.instance = asn.id
            ";
                    break;
                case "attendance" :
                    $sqlA.= "att.name as attendance, ";
                    $sqlD.= "LEFT JOIN {attendance} as att ON cm.instance = att.id
            ";
                    break;
                case "bigbluebuttonbn" :
                    $sqlA.= "bbn.name as bigbluebuttonbn, ";
                    $sqlD.= "LEFT JOIN {bigbluebuttonbn} as bbn ON cm.instance = bbn.id
            ";
                    break;
                case "booking" :
                    $sqlA.= "bkn.name as booking, ";
                    $sqlD.= "LEFT JOIN {booking} as bkn ON cm.instance = bkn.id
            ";
                    break;
                case "certificate" :
                    $sqlA.= "cft.name as certificate, ";
                    $sqlD.= "LEFT JOIN {certificate} as cft ON cm.instance = cft.id
            ";
                    break;
                case "chat" :
                    $sqlA.= "cht.name as chat, ";
                    $sqlD.= "LEFT JOIN {chat} as cht ON cm.instance = cht.id
            ";
                    break;
                case "checklist" :
                    $sqlA.= "ckl.name as checklist, ";
                    $sqlD.= "LEFT JOIN {checklist} as ckl ON cm.instance = ckl.id
            ";
                    break;
                case "choice" :
                    $sqlA.= "chc.name as choice, ";
                    $sqlD.= "LEFT JOIN {choice} as chc ON cm.instance = chc.id
            ";
                    break;
                case "icontent" :
                    $sqlA.= "ict.name as icontent, ";
                    $sqlD.= "LEFT JOIN {icontent} as ict ON cm.instance = ict.id
            ";
                    break;
                case "customcert" :
                    $sqlA.= "ctc.name as customcert, ";
                    $sqlD.= "LEFT JOIN {customcert} as ctc ON cm.instance = ctc.id
            ";
                    break;
                case "data" :
                    $sqlA.= "dt.name as data, ";
                    $sqlD.= "LEFT JOIN {data} as dt ON cm.instance = dt.id
            ";
                    break;
                case "dataform" :
                    $sqlA.= "dfm.name as dataform, ";
                    $sqlD.= "LEFT JOIN {dataform} as dfm ON cm.instance = dfm.id
            ";
                    break;
                case "lti" :
                    $sqlA.= "lt.name as lti, ";
                    $sqlD.= "LEFT JOIN {lti} as lt ON cm.instance = lt.id
            ";
                    break;
                case "feedback" :
                    $sqlA.= "fdb.name as feedback, ";
                    $sqlD.= "LEFT JOIN {feedback} as fdb ON cm.instance = fdb.id
            ";
                    break;
                case "forum" :
                    $sqlA.= "frm.name as forum, ";
                    $sqlD.= "LEFT JOIN {forum} as frm ON cm.instance = frm.id
            ";
                    break;
                case "game" :
                    $sqlA.= "gme.name as game, ";
                    $sqlD.= "LEFT JOIN {game} as gme ON cm.instance = gme.id
            ";
                    break;
                case "glossary" :
                    $sqlA.= "gls.name as glossary, ";
                    $sqlD.= "LEFT JOIN {glossary} as gls ON cm.instance = gls.id
            ";
                    break;
                case "choicegroup" :
                    $sqlA.= "cgr.name as choicegroup, ";
                    $sqlD.= "LEFT JOIN {choicegroup} as cgr ON cm.instance = cgr.id
            ";
                    break;
                case "groupselect" :
                    $sqlA.= "grs.name as groupselect, ";
                    $sqlD.= "LEFT JOIN {groupselect} as grs ON cm.instance = grs.id
            ";
                    break;
                case "hotpot" :
                    $sqlA.= "htp.name as hotpot, ";
                    $sqlD.= "LEFT JOIN {hotpot} as htp ON cm.instance = htp.id
            ";
                    break;
                case "hvp" :
                    $sqlA.= "hvp.name as hvp, ";
                    $sqlD.= "LEFT JOIN {hvp} as hvp ON cm.instance = hvp.id
            ";
                    break;
                case "lesson" :
                    $sqlA.= "lss.name as lesson, ";
                    $sqlD.= "LEFT JOIN {lesson} as lss ON cm.instance = lss.id
            ";
                    break;
                case "openmeetings" :
                    $sqlA.= "opm.name as openmeetings, ";
                    $sqlD.= "LEFT JOIN {openmeetings} as opm ON cm.instance = opm.id
            ";
                    break;
                case "questionnaire" :
                    $sqlA.= "qst.name as questionnaire, ";
                    $sqlD.= "LEFT JOIN {questionnaire} as qst ON cm.instance = qst.id
            ";
                    break;
                case "quiz" :
                    $sqlA.= "qz.name as quiz, ";
                    $sqlD.= "LEFT JOIN {quiz} as qz ON cm.instance = qz.id
            ";
                    break;
                case "quizgame" :
                    $sqlA.= "qzg.name as quizgame, ";
                    $sqlD.= "LEFT JOIN {quizgame} as qzg ON cm.instance = qzg.id
            ";
                    break;
                case "scheduler" :
                    $sqlA.= "sdr.name as scheduler, ";
                    $sqlD.= "LEFT JOIN {scheduler} as sdr ON cm.instance = sdr.id
            ";
                    break;
                case "scorm" :
                    $sqlA.= "scr.name as scorm, ";
                    $sqlD.= "LEFT JOIN {scorm} as scr ON cm.instance = scr.id
            ";
                    break;
                case "subcourse" :
                    $sqlA.= "sbc.name as subcourse, ";
                    $sqlD.= "LEFT JOIN {subcourse} as sbc ON cm.instance = sbc.id
            ";
                    break;
                case "survey" :
                    $sqlA.= "srv.name as survey, ";
                    $sqlD.= "LEFT JOIN {survey} as srv ON cm.instance = srv.id
            ";
                    break;
                case "vpl" :
                    $sqlA.= "vpl.name as vpl, ";
                    $sqlD.= "LEFT JOIN {vpl} as vpl ON cm.instance = vpl.id
            ";
                    break;
                case "wiki" :
                    $sqlA.= "wk.name as wiki, ";
                    $sqlD.= "LEFT JOIN {wiki} as wk ON cm.instance = wk.id
            ";
                    break;
                case "workshop" :
                    $sqlA.= "wrk.name as workshop, ";
                    $sqlD.= "LEFT JOIN {workshop} as wrk ON cm.instance = wrk.id
            ";
                    break;
                case "book" :
                    $sqlA.= "bk.name as book, ";
                    $sqlD.= "LEFT JOIN {book} as bk ON cm.instance = bk.id
            ";
                    break;
                case "resource" :
                    $sqlA.= "rsr.name as resource, ";
                    $sqlD.= "LEFT JOIN {resource} as rsr ON cm.instance = rsr.id
            ";
                    break;
                case "folder" :
                    $sqlA.= "fld.name as folder, ";
                    $sqlD.= "LEFT JOIN {folder} as fld ON cm.instance = fld.id
            ";
                    break;
                case "imscp" :
                    $sqlA.= "msc.name as imscp, ";
                    $sqlD.= "LEFT JOIN {imscp} as msc ON cm.instance = msc.id
            ";
                    break;
                case "label" :
                    $sqlA.= "lbl.name as label, ";
                    $sqlD.= "LEFT JOIN {label} as lbl ON cm.instance = lbl.id
            ";
                    break;
                case "lightboxgallery" :
                    $sqlA.= "lbg.name as lightboxgallery, ";
                    $sqlD.= "LEFT JOIN {lightboxgallery} as lbg ON cm.instance = lbg.id
            ";
                    break;
                case "page" :
                    $sqlA.= "pg.name as page, ";
                    $sqlD.= "LEFT JOIN {page} as pg ON cm.instance = pg.id
            ";
                    break;
                case "poster" :
                    $sqlA.= "pst.name as poster, ";
                    $sqlD.= "LEFT JOIN {poster} as pst ON cm.instance = pst.id
            ";
                    break;
                case "recordingsbn" :
                    $sqlA.= "rbn.name as recordingsbn, ";
                    $sqlD.= "LEFT JOIN {recordingsbn} as rbn ON cm.instance = rbn.id
            ";
                    break;
                case "url" :
                    $sqlA.= "rl.name as url, ";
                    $sqlD.= "LEFT JOIN {url} as rl ON cm.instance = rl.id
            ";
                    break;
            }
        }

        $sql = $sqlA . $sqlB . $sqlC . $sqlD . $sqlE;

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

//    $timearray = array();

//    for ($i = 0; $i < 24; $i++)
//    {
//        $hourbegin = $i * 10000;
//        $hourend = $i * 10000 + 9999;
//        $countedIds = array();
//        $numActiveStudents = 0;
//
//        foreach ($resultado as $item) {
//            if (!in_array($item->userid, $countedIds) && date("His", $item->timecreated) >= $hourbegin && date("His", $item->timecreated) <= $hourend) {
//                array_push($countedIds, $item->userid);
//                $numActiveStudents++;
//            }
//        }
//
//        $timearray[$i] = $numActiveStudents;
//    }

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


function block_analytics_graphs_get_user_resource_url_page_access($course, $student, $legacy=0) {
    global $COURSE;
    global $DB;

    $requestedModules = block_analytics_graphs_get_course_used_modules($course);

    //$resource = $DB->get_record('modules', array('name' => 'resource'), 'id');
    //$url = $DB->get_record('modules', array('name' => 'url'), 'id');
    //$page = $DB->get_record('modules', array('name' => 'page'), 'id');
   // $wiki = $DB->get_record('modules', array('name' => 'wiki'), 'id');
    $startdate = $COURSE->startdate;

    $paramsDefault = array($startdate, $student, $course);//array($startdate, $student, $course, $resource->id, $url->id, $page->id, $wiki->id);
    $paramsIds = array();
    $sqlA = "SELECT temp.id, m.name as tipo, ";
    $sqlB = "COALESCE(temp.userid,0) as userid,  temp.acessos
                    FROM (
                        SELECT cm.id, log.userid, count(*) as acessos
                        FROM {course_modules} cm
                        LEFT JOIN {logstore_standard_log} log ON log.timecreated >= ?
                            AND log.userid = ? AND action = 'viewed' AND cm.id=log.contextinstanceid
                        WHERE cm.course = ? AND (";


    $sqlC = "cm.module=?";

    if (count ($requestedModules) >= 2) {
        for ($i = 2; $i <= count($requestedModules); $i++) {
            $sqlC .= " OR cm.module=?";
        }
    }

    $sqlD = ") AND cm.visible = 1
                        GROUP BY cm.id, log.userid
                        ) as temp
                    LEFT JOIN {course_modules} cm ON temp.id = cm.id
                    LEFT JOIN {modules} m ON cm.module = m.id
                    ";
    $sqlE = "ORDER BY m.name";

    foreach ($requestedModules as $module) {
        $temp = $DB->get_record('modules', array('name' => $module->name), 'id');
        array_push($paramsDefault, $temp->id);
        switch ($module->name) {
            case "activequiz" :
                $sqlA.= "avq.name as activequiz, ";
                $sqlD.= "LEFT JOIN {activequiz} as avq ON cm.instance = avq.id
            ";
                break;
            case "assign" :
                $sqlA.= "asn.name as assign, ";
                $sqlD.= "LEFT JOIN {assign} as asn ON cm.instance = asn.id
            ";
                break;
            case "attendance" :
                $sqlA.= "att.name as attendance, ";
                $sqlD.= "LEFT JOIN {attendance} as att ON cm.instance = att.id
            ";
                break;
            case "bigbluebuttonbn" :
                $sqlA.= "bbn.name as bigbluebuttonbn, ";
                $sqlD.= "LEFT JOIN {bigbluebuttonbn} as bbn ON cm.instance = bbn.id
            ";
                break;
            case "booking" :
                $sqlA.= "bkn.name as booking, ";
                $sqlD.= "LEFT JOIN {booking} as bkn ON cm.instance = bkn.id
            ";
                break;
            case "certificate" :
                $sqlA.= "cft.name as certificate, ";
                $sqlD.= "LEFT JOIN {certificate} as cft ON cm.instance = cft.id
            ";
                break;
            case "chat" :
                $sqlA.= "cht.name as chat, ";
                $sqlD.= "LEFT JOIN {chat} as cht ON cm.instance = cht.id
            ";
                break;
            case "checklist" :
                $sqlA.= "ckl.name as checklist, ";
                $sqlD.= "LEFT JOIN {checklist} as ckl ON cm.instance = ckl.id
            ";
                break;
            case "choice" :
                $sqlA.= "chc.name as choice, ";
                $sqlD.= "LEFT JOIN {choice} as chc ON cm.instance = chc.id
            ";
                break;
            case "icontent" :
                $sqlA.= "ict.name as icontent, ";
                $sqlD.= "LEFT JOIN {icontent} as ict ON cm.instance = ict.id
            ";
                break;
            case "customcert" :
                $sqlA.= "ctc.name as customcert, ";
                $sqlD.= "LEFT JOIN {customcert} as ctc ON cm.instance = ctc.id
            ";
                break;
            case "data" :
                $sqlA.= "dt.name as data, ";
                $sqlD.= "LEFT JOIN {data} as dt ON cm.instance = dt.id
            ";
                break;
            case "dataform" :
                $sqlA.= "dfm.name as dataform, ";
                $sqlD.= "LEFT JOIN {dataform} as dfm ON cm.instance = dfm.id
            ";
                break;
            case "lti" :
                $sqlA.= "lt.name as lti, ";
                $sqlD.= "LEFT JOIN {lti} as lt ON cm.instance = lt.id
            ";
                break;
            case "feedback" :
                $sqlA.= "fdb.name as feedback, ";
                $sqlD.= "LEFT JOIN {feedback} as fdb ON cm.instance = fdb.id
            ";
                break;
            case "forum" :
                $sqlA.= "frm.name as forum, ";
                $sqlD.= "LEFT JOIN {forum} as frm ON cm.instance = frm.id
            ";
                break;
            case "game" :
                $sqlA.= "gme.name as game, ";
                $sqlD.= "LEFT JOIN {game} as gme ON cm.instance = gme.id
            ";
                break;
            case "glossary" :
                $sqlA.= "gls.name as glossary, ";
                $sqlD.= "LEFT JOIN {glossary} as gls ON cm.instance = gls.id
            ";
                break;
            case "choicegroup" :
                $sqlA.= "cgr.name as choicegroup, ";
                $sqlD.= "LEFT JOIN {choicegroup} as cgr ON cm.instance = cgr.id
            ";
                break;
            case "groupselect" :
                $sqlA.= "grs.name as groupselect, ";
                $sqlD.= "LEFT JOIN {groupselect} as grs ON cm.instance = grs.id
            ";
                break;
            case "hotpot" :
                $sqlA.= "htp.name as hotpot, ";
                $sqlD.= "LEFT JOIN {hotpot} as htp ON cm.instance = htp.id
            ";
                break;
            case "hvp" :
                $sqlA.= "hvp.name as hvp, ";
                $sqlD.= "LEFT JOIN {hvp} as hvp ON cm.instance = hvp.id
            ";
                break;
            case "lesson" :
                $sqlA.= "lss.name as lesson, ";
                $sqlD.= "LEFT JOIN {lesson} as lss ON cm.instance = lss.id
            ";
                break;
            case "openmeetings" :
                $sqlA.= "opm.name as openmeetings, ";
                $sqlD.= "LEFT JOIN {openmeetings} as opm ON cm.instance = opm.id
            ";
                break;
            case "questionnaire" :
                $sqlA.= "qst.name as questionnaire, ";
                $sqlD.= "LEFT JOIN {questionnaire} as qst ON cm.instance = qst.id
            ";
                break;
            case "quiz" :
                $sqlA.= "qz.name as quiz, ";
                $sqlD.= "LEFT JOIN {quiz} as qz ON cm.instance = qz.id
            ";
                break;
            case "quizgame" :
                $sqlA.= "qzg.name as quizgame, ";
                $sqlD.= "LEFT JOIN {quizgame} as qzg ON cm.instance = qzg.id
            ";
                break;
            case "scheduler" :
                $sqlA.= "sdr.name as scheduler, ";
                $sqlD.= "LEFT JOIN {scheduler} as sdr ON cm.instance = sdr.id
            ";
                break;
            case "scorm" :
                $sqlA.= "scr.name as scorm, ";
                $sqlD.= "LEFT JOIN {scorm} as scr ON cm.instance = scr.id
            ";
                break;
            case "subcourse" :
                $sqlA.= "sbc.name as subcourse, ";
                $sqlD.= "LEFT JOIN {subcourse} as sbc ON cm.instance = sbc.id
            ";
                break;
            case "survey" :
                $sqlA.= "srv.name as survey, ";
                $sqlD.= "LEFT JOIN {survey} as srv ON cm.instance = srv.id
            ";
                break;
            case "vpl" :
                $sqlA.= "vpl.name as vpl, ";
                $sqlD.= "LEFT JOIN {vpl} as vpl ON cm.instance = vpl.id
            ";
                break;
            case "wiki" :
                $sqlA.= "wk.name as wiki, ";
                $sqlD.= "LEFT JOIN {wiki} as wk ON cm.instance = wk.id
            ";
                break;
            case "workshop" :
                $sqlA.= "wrk.name as workshop, ";
                $sqlD.= "LEFT JOIN {workshop} as wrk ON cm.instance = wrk.id
            ";
                break;
            case "book" :
                $sqlA.= "bk.name as book, ";
                $sqlD.= "LEFT JOIN {book} as bk ON cm.instance = bk.id
            ";
                break;
            case "resource" :
                $sqlA.= "rsr.name as resource, ";
                $sqlD.= "LEFT JOIN {resource} as rsr ON cm.instance = rsr.id
            ";
                break;
            case "folder" :
                $sqlA.= "fld.name as folder, ";
                $sqlD.= "LEFT JOIN {folder} as fld ON cm.instance = fld.id
            ";
                break;
            case "imscp" :
                $sqlA.= "msc.name as imscp, ";
                $sqlD.= "LEFT JOIN {imscp} as msc ON cm.instance = msc.id
            ";
                break;
            case "label" :
                $sqlA.= "lbl.name as label, ";
                $sqlD.= "LEFT JOIN {label} as lbl ON cm.instance = lbl.id
            ";
                break;
            case "lightboxgallery" :
                $sqlA.= "lbg.name as lightboxgallery, ";
                $sqlD.= "LEFT JOIN {lightboxgallery} as lbg ON cm.instance = lbg.id
            ";
                break;
            case "page" :
                $sqlA.= "pg.name as page, ";
                $sqlD.= "LEFT JOIN {page} as pg ON cm.instance = pg.id
            ";
                break;
            case "poster" :
                $sqlA.= "pst.name as poster, ";
                $sqlD.= "LEFT JOIN {poster} as pst ON cm.instance = pst.id
            ";
                break;
            case "recordingsbn" :
                $sqlA.= "rbn.name as recordingsbn, ";
                $sqlD.= "LEFT JOIN {recordingsbn} as rbn ON cm.instance = rbn.id
            ";
                break;
            case "url" :
                $sqlA.= "rl.name as url, ";
                $sqlD.= "LEFT JOIN {url} as rl ON cm.instance = rl.id
            ";
                break;
        }
    }

    //$params = "SETHERE";
    $sql = $sqlA . $sqlB . $sqlC . $sqlD . $sqlE;
    $params = array_merge($paramsDefault, $paramsIds);
    //return $paramsDefault;
    $result = $DB->get_records_sql($sql, $paramsDefault);
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
    $totalDiscussions = $DB->get_records_sql($sql, $params);

    $sql = "SELECT b.id discussionid, b.name discussionname, c.lastread lastread
            FROM {forum} a
            LEFT JOIN {forum_discussions} b on a.id = b.forum
            LEFT JOIN {forum_read} c on b.id = c.discussionid
            WHERE a.course = " . $course ." AND c.userid = " . $student;
    $totalDiscReadByUser = $DB->get_records_sql($sql, $params);

    $sql = "SELECT b.id discussionid, b.name discussionname
            FROM {forum} a
            LEFT JOIN {forum_discussions} b on a.id = b.forum
            LEFT JOIN {forum_posts} c on b.id = c.discussion
            WHERE a.course = " . $course ." AND c.userid = " . $student;
    $totalDiscPostsByUser = $DB->get_records_sql($sql, $params);

    $read = array(); //generating arrays
    $notread = array();
    $posted = array();
    $notposted = array();

    foreach ($totalDiscussions as $item) {
        $foundRead = false;
        $foundPost = false;
        foreach ($totalDiscReadByUser as $subitem) {
            if ($item->discussionid == $subitem->discussionid) {
                if (!empty($item->discussionname)) {
                    array_push($read, $item->forumname . ": " . $item->discussionname);
                }
                $foundRead = true;
                break;
            }
        }
        if (!$foundRead) {
            if (!empty($item->discussionname)) {
                array_push($notread, $item->forumname . ": " . $item->discussionname);
            }
        }

        foreach ($totalDiscPostsByUser as $subitem) {
            if ($item->discussionid == $subitem->discussionid) {
                if (!empty($item->discussionname)) {
                    array_push($posted, $item->forumname . ": " . $item->discussionname);
                }
                $foundPost = true;
                break;
            }
        }
        if (!$foundPost) {
            if (!empty($item->discussionname)) {
                array_push($notposted, $item->forumname . ": " . $item->discussionname);
            }
        }
    }

    $result = array(); //merging arrays

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
              `mdl_course` a
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
    $resultAllQuizes = $DB->get_records_sql($sql, $params);
    $allQuizes = array();
    foreach ($resultAllQuizes as $item) {
        array_push($allQuizes, $item->name);
    }
    $sql = "SELECT  a.id, a.name, s.grade 
                FROM {quiz} a
                LEFT JOIN {quiz_grades} s on a.id = s.quiz
                WHERE s.userid = ? AND a.course = " . $course . "
                ORDER BY name";
    $resultStudentQuizes = $DB->get_records_sql($sql, $params);

    $passed = array(); //generating arrays
    $failed = array();
    $noaccess = array();

    foreach ($resultStudentQuizes as $item) {
        foreach ($resultAllQuizes as $subitem) {
            if ($item->id == $subitem->id) {
                if ($item->grade >= $subitem->gradepass) {
                    array_push($passed, $item->name);
                } else {
                    array_push($failed, $item->name);
                }
            }
        }
    }

    foreach ($resultAllQuizes as $item) {
        if (!in_array($item->name, $passed) && !in_array($item->name, $failed)) {
            array_push($noaccess, $item->name);
        }
    }

    $result = array(); //merging arrays

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
    $reports = $navigation->find('coursereports', navigation_node::TYPE_CONTAINER);
    if (has_capability('block/analytics_graphs:viewpages', $context) && $reports) {

        $sql = "SELECT cm.module, md.name 
            FROM {course_modules} as cm 
            LEFT JOIN {modules} as md ON cm.module = md.id 
            WHERE cm.course = ? 
            GROUP BY cm.module";
        $params = array($course->id);
        $availableModulesTotal = $DB->get_records_sql($sql, $params);
        $availableModules = array();
        foreach ( $availableModulesTotal as $result ) {
            array_push($availableModules, $result->name);
        }

        $reportanalyticsgraphs = $reports->add(get_string('pluginname', 'block_analytics_graphs'));

        $url = new moodle_url($CFG->wwwroot.'/blocks/analytics_graphs/grades_chart.php',
            array('id' => $course->id));
        $reportanalyticsgraphs->add(get_string('grades_chart', 'block_analytics_graphs'), $url,
            navigation_node::TYPE_SETTING, null, null, new pix_icon('i/report', ''));

        $url = new moodle_url($CFG->wwwroot.'/blocks/analytics_graphs/graphresourcestartup.php',
            array('id' => $course->id, 'legacy' => '0'));
        $reportanalyticsgraphs->add(get_string('access_to_contents', 'block_analytics_graphs'), $url,
            navigation_node::TYPE_SETTING, null, null, new pix_icon('i/report', ''));

        $url = new moodle_url($CFG->wwwroot.'/blocks/analytics_graphs/timeaccesseschart.php',
            array('id' => $course->id, 'days' => '7'));
        $reportanalyticsgraphs->add(get_string('timeaccesschart_title', 'block_analytics_graphs'), $url,
            navigation_node::TYPE_SETTING, null, null, new pix_icon('i/report', ''));

        if (in_array("assign", $availableModules)) {
            $url = new moodle_url($CFG->wwwroot . '/blocks/analytics_graphs/assign.php',
                array('id' => $course->id));
            $reportanalyticsgraphs->add(get_string('submissions_assign', 'block_analytics_graphs'), $url,
                navigation_node::TYPE_SETTING, null, null, new pix_icon('i/report', ''));
        }

        if (in_array("quiz", $availableModules)) {
            $url = new moodle_url($CFG->wwwroot . '/blocks/analytics_graphs/quiz.php', array('id' => $course->id));
            $reportanalyticsgraphs->add(get_string('submissions_quiz', 'block_analytics_graphs'), $url,
                navigation_node::TYPE_SETTING, null, null, new pix_icon('i/report', ''));
        }

        if (in_array("hotpot", $availableModules)) {
            $url = new moodle_url($CFG->wwwroot.'/blocks/analytics_graphs/hotpot.php', array('id' => $course->id));
            $reportanalyticsgraphs->add(get_string('submissions_hotpot', 'block_analytics_graphs'), $url,
                navigation_node::TYPE_SETTING, null, null, new pix_icon('i/report', ''));
        }

        $url = new moodle_url($CFG->wwwroot.'/blocks/analytics_graphs/hits.php', array('id' => $course->id,
            'legacy' => '0'));
        $reportanalyticsgraphs->add(get_string('hits_distribution', 'block_analytics_graphs'), $url,
            navigation_node::TYPE_SETTING, null, null, new pix_icon('i/report', ''));
    }
}

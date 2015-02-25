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


function block_analytics_graphs_get_students($course) {
    $context = get_context_instance(CONTEXT_COURSE, $course);
    $students = get_role_users(array(5), $context, false, '', 'firstname', null, '', '', '', 'u.suspended = :xsuspended',
                                                                                                    array('xsuspended'=>0));
    return($students);
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
    $startdate = $COURSE->startdate;

    $params = array_merge(array($startdate), $inparams, array($course, $resource->id, $url->id));

    if ($legacy) {
            $sql = "select cm.id+(COALESCE(log.id,1)*1000000), cm.id as ident, cs.section,
            m.name as tipo, r.name as resource, u.name as url,
            log.userid, usr.firstname, usr.lastname, usr.email, count(*) as acessos
            FROM {course_modules}  as cm
            LEFT JOIN {course_sections} as cs ON cm.section = cs.id
            LEFT JOIN {modules} as m ON cm.module = m.id
            LEFT JOIN {resource} as r ON cm.instance = r.id
            LEFT JOIN {url} as u ON cm.instance = u.id
            LEFT JOIN {log} as log ON log.time >= ? AND cm.id=log.cmid AND log.userid $insql
            LEFT JOIN {user} as usr ON usr.id = log.userid
            WHERE cm.course = ? and (cm.module=? OR cm.module=?)
            GROUP BY ident,userid
            ORDER BY cs.section,tipo,resource,url,usr.firstname";
    } else {
            $sql = "select cm.id+(COALESCE(log.id,1)*1000000)as id, cm.id as ident, cs.section,
            m.name as tipo, r.name as resource, u.name as url,
            log.userid, usr.firstname, usr.lastname, usr.email, count(*) as acessos
            FROM {course_modules}  as cm
            LEFT JOIN {course_sections} as cs ON cm.section = cs.id
            LEFT JOIN {modules} as m ON cm.module = m.id
            LEFT JOIN {resource} as r ON cm.instance = r.id
            LEFT JOIN {url} as u ON cm.instance = u.id
            LEFT JOIN {logstore_standard_log} as log ON log.timecreated >= ? AND
                                cm.id=log.contextinstanceid  AND log.userid $insql
            LEFT JOIN {user} as usr ON usr.id = log.userid
            WHERE cm.course = ? AND (cm.module=? OR cm.module=?)
            GROUP BY ident, userid
            ORDER BY cs.section, tipo,resource,url,usr.firstname";
    }
    $resultado = $DB->get_records_sql($sql, $params);
    return($resultado);
}

function block_analytics_graphs_get_assign_submission($course) {
    global $DB;

    $params = array($course);
    $sql = "SELECT a.id+(COALESCE(s.id,1)*1000000)as id, a.id as assignment, name, duedate, cutoffdate,
                s.userid, usr.firstname, usr.lastname, usr.email, s.timecreated
                FROM {assign} a
                LEFT JOIN {assign_submission} s on a.id = s.assignment
                LEFT JOIN {user} usr ON usr.id = s.userid
                WHERE course = ? and nosubmissions = 0 ORDER BY duedate, name, firstname";

     $resultado = $DB->get_records_sql($sql, $params);
        return($resultado);
}

function block_analytics_graphs_get_number_of_days_access_by_week($course, $estudantes, $startdate, $legacy=0) {
    global $DB;
    $timezone_adjust = get_user_timezone_offset() * 3600;
    foreach ($estudantes as $tupla) {
        $inclause[] = $tupla->id;
    }
    list($insql, $inparams) = $DB->get_in_or_equal($inclause);
    $params = array_merge(array($timezone, $timezone, $startdate, $course, $startdate), $inparams);

    $sql = "SELECT id, userid, firstname, lastname, email, week, COUNT(*) as number,
            SUM(numberofpageviews) as numberofpageviews
                FROM (
                    SELECT log.id, log.userid, firstname, lastname, email,
                    FLOOR((log.timecreated + ?) / 86400)   as day,
                    FLOOR( (((log.timecreated  + ?) / 86400) - (?/86400))/7) as week,
                    COUNT(*) as numberofpageviews
                    FROM {logstore_standard_log} as log
                    LEFT JOIN {user} usr ON usr.id = log.userid
                    WHERE courseid = ? AND action = 'viewed' AND target = 'course' AND log.timecreated >= ? AND log.userid $insql
                    GROUP BY userid, day
                    ) as temp
                GROUP BY userid, week
                ORDER BY LOWER(firstname), LOWER(lastname),userid, week";

    $resultado = $DB->get_records_sql($sql, $params);
    return($resultado);
}


function block_analytics_graphs_get_number_of_modules_access_by_week($course, $estudantes, $startdate, $legacy=0) {
    global $DB;a
    $timezone_adjust = get_user_timezone_offset() * 3600;
    foreach ($estudantes as $tupla) {
        $inclause[] = $tupla->id;
    }
    list($insql, $inparams) = $DB->get_in_or_equal($inclause);
    $params = array_merge(array($timezone, $timezone, $startdate, $course, $startdate), $inparams);
    if (!$legacy) {
        $sql = "SELECT id, userid, firstname, lastname, email, week, COUNT(*) as number
        FROM (
            SELECT log.id, log.userid, firstname, lastname, email, objecttable, objectid,
            FLOOR((log.timecreated + ?) / 86400)   as day,
            FLOOR( (((log.timecreated  + ?) / 86400) - (?/86400))/7) as week,
            FROM {logstore_standard_log} log
            LEFT JOIN {user} usr ON usr.id = log.userid
            WHERE courseid = ? AND action = 'viewed' AND target = 'course_module' AND log.timecreated >= ? AND log.userid $insql
            GROUP BY userid, week, objecttable, objectid
            ) as temp
        GROUP BY userid, week
        ORDER by LOWER(firstname), LOWER(lastname), userid, week";
    } else {
        $sql = "SELECT id, userid, firstname, lastname, email, week, COUNT(*) as number
                FROM (
                        SELECT log.id, log.userid, firstname, lastname, email, module, cmid,
                        FLOOR((log.timecreated + ?) / 86400)   as day,
                        FLOOR( (((log.timecreated  + ?) / 86400) - (?/86400))/7) as week,
                        FROM {log} as log
                        LEFT JOIN {user} as usr ON usr.id = log.userid
                        WHERE course = ? AND action = 'view' AND cmid <> 0 AND module <> 'assign'
                            AND time >= ? AND log.userid $insql
                        GROUP BY userid, week, module, cmid
                        ) as temp
                GROUP BY userid, week
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
    $sql = "SELECT userid, COUNT(*) as number
        FROM (
            SELECT log.userid, objecttable, objectid
            FROM {logstore_standard_log} as log
            LEFT JOIN {user} usr ON usr.id = log.userid
            WHERE courseid = ? AND action = 'viewed' AND target = 'course_module' AND log.timecreated >= ? AND log.userid $insql
            GROUP BY userid, objecttable, objectid
            ) as temp
        GROUP BY userid
        ORDER by userid";

        $resultado = $DB->get_records_sql($sql, $params);
        return($resultado);
}

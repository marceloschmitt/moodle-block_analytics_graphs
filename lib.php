<?php

function diferencaVetores($estudantes,$acessaram)
{
	foreach($estudantes AS $estudante)
	{	$encontrou = false;
		foreach($acessaram AS $acessou)
			if($estudante['userid']  == $acessou ['userid'])
			{	$encontrou = true;
				break;
			}
		if(!$encontrou)
			$resultado[] = $estudante;
	}
	return $resultado;	

}


function getStudents($course)
{
	$context = get_context_instance(CONTEXT_COURSE, $course);
	$estudantes = get_role_users(array(5), $context,false,'','firstname');
	return($estudantes);
}


function getResourceAndUrlAccess($course,$estudantes,$legacy)
{
	global $COURSE;
	global $DB;

	foreach ($estudantes AS $tupla) 
        	$in_clause[] = $tupla->id;
	list($insql, $inparams) = $DB->get_in_or_equal($in_clause);
	$resource = $DB->get_record('modules', array('name'=>'resource'),'id');
	$url = $DB->get_record('modules', array('name'=>'url'),'id');
	$startdate = $COURSE->startdate;

	$params = array_merge(array($startdate),$inparams,array($course, $resource->id, $url->id));

	if($legacy)
        	$sql = "select (@cnt := @cnt + 1) AS id, cm.id AS ident, cs.section, m.name AS tipo, r.name AS resource, u.name AS url, 
        	log.userid, user.firstname, user.lastname, user.email, count(*) AS acessos 
        	FROM {course_modules}  AS cm
        	LEFT JOIN {course_sections} AS cs ON cm.section = cs.id 
        	LEFT JOIN {modules} AS m ON cm.module = m.id
        	LEFT JOIN {resource} AS r ON cm.instance = r.id 
        	LEFT JOIN {url} AS u ON cm.instance = u.id
        	LEFT JOIN {log} AS log ON log.time >= ? AND cm.id=log.cmid AND log.userid $insql
        	LEFT JOIN {user} AS user ON user.id = log.userid
        	CROSS JOIN (SELECT @cnt := 0) AS dummy
        	WHERE cm.course = ? and (cm.module=? OR cm.module=?)
        	GROUP BY ident,userid
        	ORDER BY cs.section,tipo,resource,url,user.firstname";
	else
        	$sql = "select (@cnt := @cnt + 1) AS id, cm.id AS ident, cs.section, m.name AS tipo, r.name AS resource, u.name AS url, 
        	log.userid, user.firstname, user.lastname, user.email, count(*) AS acessos
        	FROM {course_modules}  AS cm
        	LEFT JOIN {course_sections} AS cs ON cm.section = cs.id 
        	LEFT JOIN {modules} AS m ON cm.module = m.id
        	LEFT JOIN {resource} AS r ON cm.instance = r.id 
        	LEFT JOIN {url} AS u ON cm.instance = u.id
        	LEFT JOIN {logstore_standard_log} AS log ON log.timecreated >= ? AND cm.id=log.contextinstanceid  AND log.userid $insql
        	LEFT JOIN {user} AS user ON user.id = log.userid
        	CROSS JOIN (SELECT @cnt := 0) AS dummy
        	WHERE cm.course = ? AND (cm.module=? OR cm.module=?) 
        	GROUP BY ident, userid
        	ORDER BY cs.section, tipo,resource,url,user.firstname";

	$resultado = $DB->get_records_sql($sql, $params);
	return($resultado);
}

function getAssignSubmission($course)
{
global $DB;
	$params = array($course);
	$sql = "SELECT (@cnt := @cnt + 1) AS id, a.id AS assignment, name, duedate, cutoffdate,
                s.userid, user.firstname, user.lastname, user.email, s.timecreated
                FROM {assign} AS a
                LEFT JOIN {assign_submission} as s on a.id = s.assignment
                LEFT JOIN {user} as user ON user.id = s.userid
                CROSS JOIN (SELECT @cnt := 0) AS dummy
                WHERE course = ? and nosubmissions = 0 ORDER BY duedate, name, firstname";

 	$resultado = $DB->get_records_sql($sql, $params);
        return($resultado);


}

function getCourseDayAcessByWeek($course,$estudantes,$startdate,$legacy=0)
{
global $DB;
        foreach ($estudantes AS $tupla)
                $in_clause[] = $tupla->id;
        list($insql, $inparams) = $DB->get_in_or_equal($in_clause);
        $params = array_merge(array($startdate,$course), $inparams);

        $sql = "SELECT id, userid, firstname, lastname, email, week, COUNT(*) as number, SUM(numberofpageviews) AS numberofpageviews
                FROM (
                        SELECT log.id, log.userid, firstname, lastname, email, 
                        DATE_FORMAT(FROM_UNIXTIME(log.timecreated),'%Y-%m-%d') as day, 
                        TIMESTAMPDIFF(WEEK,FROM_UNIXTIME(?),FROM_UNIXTIME(log.timecreated)) as week,
			COUNT(*) AS numberofpageviews
                        FROM {logstore_standard_log} AS log
                        LEFT JOIN {user} AS user ON user.id = log.userid
                        WHERE courseid = ? AND action = 'viewed' AND target = 'course'  AND log.userid $insql
                        GROUP BY userid, day 
                        ) as temp
                GROUP BY userid, week
                ORDER BY LOWER(firstname), LOWER(lastname),userid, week";

        $resultado = $DB->get_records_sql($sql,$params);
        return($resultado);
}


function getCourseModuleDayAcessByWeek($course,$estudantes,$startdate,$legacy=0)
{
global $DB;
        foreach ($estudantes AS $tupla)
                $in_clause[] = $tupla->id;
        list($insql, $inparams) = $DB->get_in_or_equal($in_clause);
        $params = array_merge(array($startdate,$course,$startdate),$inparams);
	if(!$legacy)
	{
		$sql = "SELECT id, userid, firstname, lastname, email, week, COUNT(*) as number
		FROM (
			SELECT log.id, log.userid, firstname, lastname, email, objecttable, objectid,
			DATE_FORMAT(FROM_UNIXTIME(log.timecreated),'%Y-%m-%d') as day,
			TIMESTAMPDIFF(WEEK,FROM_UNIXTIME(?),FROM_UNIXTIME(log.timecreated)) as week
			FROM {logstore_standard_log} AS log 
			LEFT JOIN {user} AS user ON user.id = log.userid
			WHERE courseid = ? AND action = 'viewed' AND target = 'course_module' AND log.timecreated >= ? AND log.userid $insql
			GROUP BY userid, week, objecttable, objectid
			) as temp
		GROUP BY userid, week
		ORDER by LOWER(firstname), LOWER(lastname), userid, week";
	}
	else
	{
                $sql = "SELECT id, userid, firstname, lastname, email, week, COUNT(*) as number
                FROM (
                        SELECT log.id, log.userid, firstname, lastname, email, module, cmid,
                        DATE_FORMAT(FROM_UNIXTIME(log.time),'%Y-%m-%d') as day,
                        TIMESTAMPDIFF(WEEK,FROM_UNIXTIME(?),FROM_UNIXTIME(log.time)) as week
                        FROM {log} AS log 
                        LEFT JOIN {user} AS user ON user.id = log.userid
                        WHERE course = ? AND action = 'view' AND cmid <> 0 AND module <> 'assign' AND time >= ? AND log.userid $insql
                        GROUP BY userid, week, module, cmid
                        ) as temp
                GROUP BY userid, week
                ORDER by LOWER(firstname), LOWER(lastname), userid, week";
        }
	
      
	$resultado = $DB->get_records_sql($sql,$params);
        return($resultado);
}

function getCourseNumberOfModulesAccessed($course,$estudantes,$startdate,$legacy=0)
{
global $DB;
        foreach ($estudantes AS $tupla)
                $in_clause[] = $tupla->id;
        list($insql, $inparams) = $DB->get_in_or_equal($in_clause);
        $params = array_merge(array($course),$inparams);
	$sql = "SELECT userid, COUNT(*) as number
        FROM (
                SELECT log.userid, objecttable, objectid
                FROM {logstore_standard_log} AS log
                LEFT JOIN {user} AS user ON user.id = log.userid
                WHERE courseid = ? AND action = 'viewed' AND target = 'course_module'  AND log.userid $insql
                GROUP BY userid, objecttable, objectid
                ) as temp
        GROUP BY userid
        ORDER by userid";

        $resultado = $DB->get_records_sql($sql,$params);
        return($resultado);
}



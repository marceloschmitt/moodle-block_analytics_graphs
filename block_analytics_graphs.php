<?php
class block_analytics_graphs extends block_base {
    public function init() {
        $this->title = get_string('analytics_graphs', 'block_analytics_graphs');
    }
    // The PHP tag and the curly bracket for the class definition 
    // will only be closed after there is another function added in the next section.


public function get_content() {
global $CFG;

 	$course = $this->page->course;
        $context = context_course::instance($course->id);
        $canview = has_capability('block/analytics_graphs:viewpages', $context);
    	if (!$canview)
            return;

    if ($this->content !== null) {

      return $this->content;
    }
 
    $this->content         =  new stdClass;
	if(floatval($CFG->release) >= 2.7)
		 $this->content->text   = get_string('graficos','block_analytics_graphs')
                        . "<li> <a href= {$CFG->wwwroot}/blocks/analytics_graphs/graphResourceUrl.php?id={$course->id}&legacy=0
                                target=_blank>" . get_string('grafico02','block_analytics_graphs') . "</a>"
                        . "<li> <a href= {$CFG->wwwroot}/blocks/analytics_graphs/assign.php?id={$course->id}&legacy=0
                                target=_blank>" . get_string('grafico03','block_analytics_graphs') . "</a>"
                        . "<li> <a href= {$CFG->wwwroot}/blocks/analytics_graphs/hits.php?id={$course->id}&legacy=0
                                target=_blank>" . get_string('grafico04','block_analytics_graphs') . "</a>";

    	$this->content->text  = $this->content->text . '<br><br>Legacy (prior to 2.7)'
			.  "<li> <a href= {$CFG->wwwroot}/blocks/analytics_graphs/graphResourceUrl.php?id={$course->id}&legacy=1 
				target=_blank>" . get_string('grafico02','block_analytics_graphs') . "</a>"
			. "<li> <a href= {$CFG->wwwroot}/blocks/analytics_graphs/hits.php?id={$course->id}&legacy=1
                                target=_blank>" . get_string('grafico04','block_analytics_graphs') . "</a>";
   $this->content->footer = '---';
 
    return $this->content;
  }
 }  // Here's the closing bracket for the class definition

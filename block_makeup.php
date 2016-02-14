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
 * Newblock block caps.
 *
 * @package    block_newblock
 * @copyright  Daniel Neis <danielneis@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class block_makeup extends block_base {

    function init() {
        $this->title = get_string('pluginname', 'block_makeup');
    }

    function get_content() {
        global $CFG, $OUTPUT, $DB, $USER;

        if ($this->content !== null) {
            return $this->content;
        }

        if (empty($this->instance)) {
            $this->content = '';
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';

        // user/index.php expect course context, so get one if page has module context.
        $currentcontext = $this->page->context->get_course_context(false);

        if (! empty($this->config->text)) {
            $this->content->text = $this->config->text;
        }

        $this->content = '';
        if (empty($currentcontext)) {
            return $this->content;
        }
        if ($this->page->course->id == SITEID) {
            $this->context->text .= "site context";
        }

        if (! empty($this->config->text)) {
            $this->content->text .= $this->config->text;
        }
        
        $makeuprole     = $DB->get_record("role", array("name"=>"Makeup_OK"));
        $makeuprole     = $DB->get_record("role", array("name"=>"Makeup_OK"));
        $shedulermodule = $DB->get_record("modules", array("name"=>"scheduler"));
        
        
        $groupshtml    = "";
        $teachershtml  = "";
        $shedulershtml = "";
        if ($groupsarr = $DB->get_records("groups", array("courseid"=>$this->page->course->id))){
          foreach($groupsarr as $groupsarr_)
            $groupshtml .= '<option value="'.$groupsarr_->id.'">'.$groupsarr_->name.'</option>';
        }
        
        $teachersarray = array();
        
        $accessarray = array();
        
        if ($access = $DB->get_records_sql("SELECT ra.userid FROM 
mdl_role_assignments ra , mdl_role r
WHERE (r.archetype = 'manager' OR r.archetype = 'coursecreator' OR r.archetype = 'editingteacher' OR r.archetype = 'teacher') AND ra.roleid = r.id")){
          foreach($access as $acces) {
            $accessarray[] = $acces->userid;
          }
        }
        
        if ($teachers = $DB->get_records_sql("SELECT
u.id AS userid, c.id AS courseid, c.fullname, u.username, u.firstname, u.lastname, u.email

FROM 
mdl_role_assignments ra 
JOIN mdl_user u ON u.id = ra.userid
JOIN mdl_role r ON r.id = ra.roleid
JOIN mdl_context cxt ON cxt.id = ra.contextid
JOIN mdl_course c ON c.id = cxt.instanceid

WHERE ra.userid = u.id

AND ra.contextid = cxt.id
AND cxt.instanceid = ".$this->page->course->id."
AND r.id = 4

ORDER BY c.fullname")){
          foreach($teachers as $teacher) {
            $teachersarray[] = $teacher->userid;
            $teachershtml .= '<option value="'.$teacher->userid.'">'.$teacher->firstname.'</option>';
          }
        }
        
        
        if(!in_array($USER->id, $accessarray)) 
          return false;
        
        
        if ($shedulers = $DB->get_records("scheduler", array("course"=>$this->page->course->id))){
          foreach($shedulers as $sheduler)
            if (stristr($sheduler->name, "make"))
              $shedulershtml .= '<option value="'.$sheduler->id.'">'.$sheduler->name.'</option>';
        }
        
        
        $students = get_enrolled_users($this->context);
        
        
        if ($students = $DB->get_records_sql("SELECT
u.id AS userid, c.id AS courseid, c.fullname, u.username, u.firstname, u.lastname, u.email

FROM 
mdl_role_assignments ra 
JOIN mdl_user u ON u.id = ra.userid
JOIN mdl_role r ON r.id = ra.roleid
JOIN mdl_context cxt ON cxt.id = ra.contextid
JOIN mdl_course c ON c.id = cxt.instanceid

WHERE ra.userid = u.id

AND ra.contextid = cxt.id
AND cxt.instanceid = ".$this->page->course->id."
AND r.id = 5

ORDER BY u.username"))
        
          foreach($students as $student) 
            if (!$DB->get_record("role_assignments",array("userid"=>$student->userid,"roleid"=>$makeuprole->id)) && !in_array($student->userid, $teachersarray))
              $studentshtml .= '<option value="'.$student->userid.'">'.$student->username.' ('.$student->firstname.' '.$student->lastname.')</option>';
        
        
        $this->content->text = '<div>
        <style>
        .region-content{
          min-height:2000px
        }
        </style>
        <script type="text/javascript" src="http://yandex.st/jquery/1.7.1/jquery.min.js"></script>
        <script src="'.$CFG->wwwroot.'/blocks/makeup/js/jquery.searchabledropdown-1.0.8.min.js"></script>
        <script>
        $(document).ready(function() {
          $("#makeup-student-select").searchable();
        });
        </script>
        
        <form action="'.$CFG->wwwroot.'/blocks/makeup/submit.php?cid='.$this->page->course->id.'" method="post">
        <div style="white-space: nowrap;">Students:<br /><select name="uid" id="makeup-student-select" style="width:190px;background-color:#33CC66;">'.$studentshtml.'</select></div>
        <div style="white-space: nowrap;">Scheduler:<br /><select name="shid" style="width:190px">'.$shedulershtml.'</select></div>
        <div><input type="submit" name="" value="Grant" style="margin: 10px;padding: 4px;" /></div>
        </form>
        </div>';

        return $this->content;
    }

    // my moodle can only have SITEID and it's redundant here, so take it away
    public function applicable_formats() {
        return array('all' => false,
                     'site' => true,
                     'site-index' => true,
                     'course-view' => true, 
                     'course-view-social' => false,
                     'mod' => true, 
                     'mod-quiz' => false);
    }

    public function instance_allow_multiple() {
          return true;
    }

    function has_config() {return true;}

    public function cron() {
            mtrace( "Hey, my cron script is running" );
             
                 // do something
                  
                      return true;
    }
}

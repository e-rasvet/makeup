<?php

/**
 * Export attendance sessions
 *
 * @package    mod
 * @subpackage attforblock
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 

require_once(dirname(__FILE__).'/../../config.php');


$cid            = required_param('cid', PARAM_INT);
$uid            = optional_param('uid', 0, PARAM_INT);
$shid           = optional_param('shid', 0, PARAM_INT);

$course         = $DB->get_record('course', array('id' => $cid), '*', MUST_EXIST);


$makeuprole     = $DB->get_record("role", array("name"=>"Makeup_OK"));
$shedulermodule = $DB->get_record("modules", array("name"=>"scheduler"));
$cm             = $DB->get_record("course_modules", array("module"=>$shedulermodule->id, "instance"=>$shid));
$context        = $DB->get_record("context", array("instanceid"=>$cm->id, "contextlevel"=>70));


$add               = new stdClass;
$add->roleid       = $makeuprole->id;
$add->contextid    = $context->id;
$add->userid       = $uid;
$add->timemodified = time();
$add->modifierid   = 2;
$add->component    = '';
$add->itemid       = 0;
$add->sortorder    = 0;


$DB->insert_record("role_assignments", $add);

$url = 'http://moodlearn.com/moodle/course/view.php?id='.$cid;

echo '
<br />
<br />
<br />
<br />
<br />
<br />
<center>
<h2>role added</h2>
</center>
<script>
    window.setTimeout(function(){
        window.location.href = "'.$url.'";
    }, 2000);
</script>';


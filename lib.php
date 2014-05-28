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
 * Local Redirect
 *
 * This local plugin that adds a 'friendly url' version of Google analytics
 * to Moodle
 *
 * @package    local
 * @subpackage local_googleanalytics
 * @copyright  2013 Bas Brands, www.basbrands.nl
 * @author 	   Bas Brands and Gavin Henrick.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function ga_trackurl() {
    global $CFG, $DB, $PAGE, $COURSE, $OUTPUT;

    $pageinfo = get_context_info_array($PAGE->context->id);

    $trackurl = array();

    // Logging full URLs if not in a course site.
    if ($COURSE->id == SITEID) {
        return $PAGE->url->out_as_local_url();;
    }

    // Adds course category name.
    if (isset($pageinfo[1]->category)) {
        if ($category = $DB->get_record('course_categories', array('id'=>$pageinfo[1]->category))) {
            $trackurl[] = urlencode($category->name);
        }
    }

    // Adds course shortname if configured.
    if (get_config('local_googleanalytics', 'courseshortname') && 
            isset($pageinfo[1]->shortname)) {
        $trackurl[] = urlencode($pageinfo[1]->shortname);
    } else if (isset($pageinfo[1]->fullname)) {
        // Adds course full name.
        $trackurl[] = urlencode($pageinfo[1]->fullname);
    }

    // Adds activity name.
    if (isset($pageinfo[2]->name)) {
        $trackurl[] = urlencode($pageinfo[2]->name);
    }

    return implode('/', $trackurl);
}

function ga_key() {
    $configs = get_config('local_googleanalytics');
    if (!$configs->enabled || empty($configs->gakey)) {
        return false;
    } else {
        return $configs->gakey;
    }
}

// Don't run lib code during cron runs.
if (!defined("CLI_SCRIPT") || (defined("CLI_SCRIPT") && !CLI_SCRIPT)) {
    $gakey = ga_key();
    if ($gakey) {
        $trackurl = ga_trackurl();
        $CFG->additionalhtmlfooter .= "
        <script type='text/javascript' name='localga'>
          var _gaq = _gaq || [];
          _gaq.push(['_setAccount', '".$gakey."']);
          _gaq.push(['_trackPageview','/".$trackurl."']);

          (function() {
            var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
            ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
            var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
          })();
        </script>";
        if (debugging()) {
            $CFG->additionalhtmlfooter .= "<span class='badge badge-success'>/".$trackurl."</span>";
        }
    }
}

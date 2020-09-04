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
 * @package    local_komettranslator
 * @copyright  2020 Zentrum für Lernmanagement (www.lernmanagement.at)
 * @author    Robert Schrenk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Review competency frameworks before they are enabled.
 */

namespace local_komettranslator;

require('../../config.php');
require_login();

$idnumber = required_param('idnumber', PARAM_TEXT);

$PAGE->set_url(new \moodle_url('/local/komettranslator/review.php', array('idnumber' => $idnumber)));
$PAGE->set_context(\context_system::instance());
$PAGE->set_heading(get_string('competencyframeworks:review', 'local_komettranslator'));
$PAGE->set_title(get_string('competencyframeworks:review', 'local_komettranslator'));

echo $OUTPUT->header();
if (!is_siteadmin()) {
    echo $OUTPUT->render_from_template('local_komettranslator/alert', array(
        'type' => 'danger',
        'content' => get_string('access_denied', 'local_komettranslator'),
        'url' => new \moodle_url('/my', array()),
    ));
    echo $OUTPUT->footer();
    die();
}

$exacomp = \local_komettranslator\locallib::load_from_xmlurl(false);
$descriptors = \local_komettranslator\locallib::load_descriptors($exacomp);


$framework = array();
foreach ($exacomp->edulevels[0] as $xmledulevel) {
    foreach ($xmledulevel->schooltypes[0] as $xmlschooltype) {
        foreach ($xmlschooltype->subjects[0] as $xmlsubject) {
            $xmlidnumber = $xmlsubject['source'] . '_' . $xmlsubject['id'];
            if ($idnumber != $xmlidnumber) {
                continue;
            }
            $framework = array(
                'title' => $xmlsubject->title->__toString(),
                'topics' => array()
            );
            foreach ($xmlsubject->topics[0] as $xmltopic) {
                $topic = array(
                    'idnumber' => $xmltopic['source'] . '_' . $xmltopic['id'],
                    'shortname' => $xmltopic->title->__toString(),
                    'descriptors' => array(),
                );
                $dlist = array();
                foreach ($xmltopic->descriptors[0] as $xmldescriptor) {
                    $descriptoridnumber = $xmldescriptor['source'] . '_' . $xmldescriptor['id'];
                    if (empty($descriptors[$descriptoridnumber])) {
                        //echo "ERROR: MISSING DESCRIPTOR FOR $descriptoridnumber<br />";
                        continue;
                    }
                    $dlist[$descriptors[$descriptoridnumber]['sorting']] = array(
                        'idnumber' => $descriptoridnumber,
                        'title' => $descriptors[$descriptoridnumber]['title'],
                    );
                }
                sort($dlist);
                //print_r($dlist);
                foreach ($dlist as $el) {
                    $topic['descriptors'][] = $el;
                }
                $framework['topics'][] = $topic;
            }
        }
    }
}

echo $OUTPUT->render_from_template('local_komettranslator/review', $framework);
echo $OUTPUT->footer();

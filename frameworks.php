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
 * Pre-select competency frameworks for synchronization.
 */

namespace local_komettranslator;

require('../../config.php');

$enable = optional_param('enable', '', PARAM_TEXT);
$disable = optional_param('disable', '', PARAM_TEXT);

require_login();
$PAGE->set_url(new \moodle_url('/local/komettranslator/frameworks.php', array()));
$PAGE->set_context(\context_system::instance());
$PAGE->set_heading(get_string('competencyframeworks', 'local_komettranslator'));
$PAGE->set_title(get_string('competencyframeworks', 'local_komettranslator'));

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

// Reference XML for development: https://eeducation.at/uploads/data.xml
// exacomp
// - edulevels --> transfer to competency frameworks
// - descriptors --> are referenced within competenca frameworks
// - examples --> contain examples for learning material to certain descriptors

$exacomp = \local_komettranslator\locallib::load_from_xmlurl(false);
$frameworks = \local_komettranslator\locallib::load_frameworks($exacomp, true, false);

echo $OUTPUT->render_from_template('local_komettranslator/frameworks', array('frameworks' => $frameworks, 'wwwroot' => $CFG->wwwroot));
echo $OUTPUT->footer();

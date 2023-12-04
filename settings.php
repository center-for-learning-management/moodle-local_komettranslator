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

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_komettranslator_settings', get_string('pluginname:settings', 'local_komettranslator'));
    $ADMIN->add('localplugins', new admin_category('local_komettranslator', get_string('pluginname', 'local_komettranslator')));
    $ADMIN->add('local_komettranslator', $settings);
    $settings->add(new admin_setting_configtext('local_komettranslator/xmlurl', get_string('xmlurl', 'local_komettranslator'), get_string('xmlurl:description', 'local_komettranslator'), "", PARAM_URL));
    $settings->add(new admin_setting_configcheckbox('local_komettranslator/xmlurlsslverify', get_string('xmlurl:verifypeer', 'local_komettranslator'), get_string('xmlurl:verifypeer:description', 'local_komettranslator'), 1));

    $ADMIN->add(
        'local_komettranslator',
        new admin_externalpage(
            'local_komettranslator_frameworks',
            get_string('competencyframeworks', 'local_komettranslator'),
            $CFG->wwwroot . '/local/komettranslator/frameworks.php'
        )
    );
}

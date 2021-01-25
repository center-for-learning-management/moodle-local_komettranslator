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

$string['pluginname'] = 'KOMET Translator';
$string['pluginname:settings'] = 'KOMET Translator';
$string['privacy:metadata'] = 'This plugin does not store any personal data';

$string['access_denied'] = 'Zugriff nicht gestattet';

$string['cron:title'] = 'KOMET Translator Cron';
$string['competency:notcreated'] = 'No competency could be found or created for <strong>{$a->shortname} / {$a->idnumber}</strong>!';
$string['competencyframework:enabled'] = 'Enabled synchronization for framework <strong>{$a->shortname}</strong>!';
$string['competencyframework:disabled'] = 'Disabled synchronization for framework <strong>{$a->shortname}</strong>!';
$string['competencyframework:notcreated'] = 'No competency framework could be found or created for <strong>{$a->shortname} / {$a->idnumber}</strong>!';
$string['competencyframework:processing'] = 'Processing competency framework <strong>{$a->shortname} / {$a->idnumber}</strong>!';
$string['competencyframeworks'] = 'Competency Frameworks';
$string['competencyframeworks:review'] = 'Review Competency Frameworks';

$string['descriptors'] = 'Descriptors';

$string['runsync'] = 'Run sync';

$string['topic'] = 'Topic';

$string['xmlurl'] = 'XML URL';
$string['xmlurl:description'] = 'Please specify the URL of the public XML file.';
$string['xmlurl:loading'] = 'Loading XML Structure from {$a->xmlurl}';
$string['xmlurl:missing'] = 'No XML-URL configured. Please specify in website administration!';
$string['xmlurl:verifypeer'] = 'Verify SSL';
$string['xmlurl:verifypeer:description'] = 'By default SSL Certificates must be validated. In certain cases you want to disable this option.';
$string['xmlurl:verifypeer:warning'] = 'Attention, according to your configuration, SSL Certificates are not validated!';

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
 * @copyright  2020 Center for Learning Management (https://www.lernmanagement.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_komettranslator;

defined('MOODLE_INTERNAL') || die;

class locallib {
    /**
     * Read all descriptors as array.
     * @param exacomp SimpleXMLElement
     * @param selection array of idnumbers to restrict result to.
     * @return array
     */
    public static function load_descriptors($exacomp, $selection = array()) {
        $descriptors = array();
        foreach ($exacomp->descriptors[0] as $xmldescriptor) {
            $descriptoridnumber = $xmldescriptor['source'] . '_' . $xmldescriptor['id'];
            if (count($selection) == 0 || in_array($descriptoridnumber, $selection)) {
                $descriptors[$descriptoridnumber] = array(
                    'idnumber' => $descriptoridnumber,
                    'sorting' => intval($xmldescriptor->sorting),
                    'title' => $xmldescriptor->title->__toString(),
                    'description' => $xmldescriptor->description->__toString(),
                );
            }

            if (isset($xmldescriptor->children)) {
                foreach ($xmldescriptor->children[0] as $xmlchilddescriptor) {
                    $descriptoridnumber = $xmlchilddescriptor['source'] . '_' . $xmlchilddescriptor['id'];
                    if (count($selection) == 0 || in_array($descriptoridnumber, $selection)) {
                        $descriptors[$descriptoridnumber] = array(
                            'idnumber' => $descriptoridnumber,
                            'sorting' => intval($xmlchilddescriptor->sorting),
                            'title' => $xmlchilddescriptor->title->__toString(),
                            'description' => $xmldescriptor->description->__toString(),
                        );
                    }
                }
            }
        }
        return $descriptors;
    }
    /**
     * Load the framework structure and perform enabling and disabling.
     * @param exacomp SimpleXMLElement from exacomp
     * @param displayoutput whether or not to display normal output messages.
     * @param displaywarnings whether or not to display warnings.
     * @return array
     */
    public static function load_from_xml($exacomp, $displayoutput = true, $displaywarnings = true) {
        global $OUTPUT;
        $frameworks = array();
        $imploder = ' >> ';
        foreach ($exacomp->edulevels[0] as $xmledulevel) {
            $edulevel = array(
                'idnumber' => $xmledulevel['source'] . '_' . $xmledulevel['id'],
                'shortname' => $xmledulevel->title
            );
            foreach ($xmledulevel->schooltypes[0] as $xmlschooltype) {
                $schooltype = array(
                    'idnumber' => $xmlschooltype['source'] . '_' . $xmlschooltype['id'],
                    'shortname' => $xmlschooltype->title
                );
                foreach ($xmlschooltype->subjects[0] as $xmlsubject) {
                    $subject = array(
                        'idnumber' => $xmlsubject['source'] . '_' . $xmlsubject['id'],
                        'shortname' => $xmlsubject->title->__toString()
                    );
                    $idnumber = $xmlsubject['source'] . '_' . $xmlsubject['id'];
                    $shortname = implode($imploder,
                                    array(
                                        $edulevel['shortname'],
                                        $schooltype['shortname'],
                                        $subject['shortname']
                                    )
                                );

                    if (optional_param('enable', '', PARAM_TEXT) == $idnumber) {
                        set_config('isactive_' . $idnumber, 1, 'local_komettranslator');
                        if ($displayoutput) {
                            echo $OUTPUT->render_from_template('local_komettranslator/alert', array(
                                'type' => 'success',
                                'content' => get_string('competencyframework:enabled', 'local_komettranslator', array('shortname' => $shortname)),
                            ));
                        }
                    }
                    if (optional_param('disable', '', PARAM_TEXT) == $idnumber) {
                        unset_config('isactive_' . $idnumber, 'local_komettranslator');
                        if ($displayoutput) {
                            echo $OUTPUT->render_from_template('local_komettranslator/alert', array(
                                'type' => 'success',
                                'content' => get_string('competencyframework:disabled', 'local_komettranslator', array('shortname' => $shortname)),
                            ));
                        }
                    }
                    $frameworks[] = array(
                        'idnumber' => $idnumber,
                        'isactive' => get_config('local_komettranslator', 'isactive_' . $idnumber),
                        'shortname' => $shortname,
                    );
                }
            }
        }
        return $frameworks;
    }
    /**
     * Load the xml-file from xmlurl.
     * @param displaywarnings whether or not to display warnings.
     * @return SimpleXMLElement
     */
    public static function load_from_xmlurl($displaywarnings = true) {
        global $OUTPUT;
        $xmlurl = get_config('local_komettranslator', 'xmlurl');
        if (empty($xmlurl)) {
            echo $OUTPUT->render_from_template('local_komettranslator/alert', array(
                'type' => 'danger',
                'content' => get_string('xmlurl:missing', 'local_komettranslator'),
                'url' => new \moodle_url('/admin/settings.php', array('section' => 'local_komettranslator_settings')),
            ));
            echo $OUTPUT->footer();
            die();
        }

        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_AUTOREFERER, TRUE );
        curl_setopt( $ch, CURLOPT_HEADER, 0 );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $ch, CURLOPT_URL, $xmlurl );
        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, TRUE );

        $sslverify = get_config('local_komettranslator', 'xmlurlsslverify');
        if (empty($sslverify)) {
            if ($displaywarnings) {
                echo $OUTPUT->render_from_template('local_komettranslator/alert', array(
                    'type' => 'danger',
                    'content' => get_string('xmlurl:verifypeer:warning', 'local_komettranslator'),
                ));
            }

            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }

        if ($displaywarnings) {
            echo $OUTPUT->render_from_template('local_komettranslator/alert', array(
                'type' => 'info',
                'content' => get_string('xmlurl:loading', 'local_komettranslator', array('xmlurl' => $xmlurl)),
            ));
        }

        $xmlstr = curl_exec($ch);
        curl_close($ch);

        return new \SimpleXMLElement($xmlstr);
    }
    /**
     * Load the framework structure and perform enabling and disabling.
     * @param exacomp SimpleXMLElement from exacomp
     * @param subjectidnumber combination of source and id of subject.
     * @return array
     */
    public static function load_topics($exacomp, $subjectidnumber) {
        global $OUTPUT;

        $topics = array();

        foreach ($exacomp->edulevels[0] as $xmledulevel) {
            foreach ($xmledulevel->schooltypes[0] as $xmlschooltype) {
                foreach ($xmlschooltype->subjects[0] as $xmlsubject) {
                    $idnumber = $xmlsubject['source'] . '_' . $xmlsubject['id'];
                    if ($idnumber != $subjectidnumber) {
                        continue;
                    }
                    foreach ($xmlsubject->topics[0] as $xmltopic) {
                        $idnumber = $xmltopic['source'] . '_' . $xmltopic['id'];
                        $selection = array();
                        $descriptors = array();
                        foreach ($xmltopic->descriptors[0] as $xmldescriptor) {
                            $selection[] = $xmldescriptor['source'] . '_' . $xmldescriptor['id'];
                        }
                        // If there are no descriptors in that topic we would load all...
                        if (count($selection) > 0) {
                            $descriptors = array_values(self::load_descriptors($exacomp, $selection));
                        }

                        $topics[] = array(
                            'idnumber' => $idnumber,
                            'shortname' => $xmltopic->title->__toString(),
                            'description' => '' . $xmltopic->description->__toString(),
                            'sorting' => intval($xmltopic->sorting),
                            'descriptors' => $descriptors,
                        );
                    }
                }
            }
        }
        return $topics;
    }
    /**
     * Run a full sync.
     * @param displayoutput whether or not to display normal output messages.
     * @param displaywarnings whether or not to display warnings.
     */
    public static function runsync($displayoutput = true, $displaywarnings = true) {
        global $CFG, $DB, $OUTPUT, $USER;

        require_once($CFG->dirroot . '/competency/classes/api.php');

        $exacomp = self::load_from_xmlurl($displaywarnings);
        $frameworks = self::load_from_xml($exacomp, $displayoutput, $displaywarnings);
        $descriptors = self::load_descriptors($exacomp);

        // Now loop through frameworks. If they are enabled, sync meta-data and descriptors.
        foreach ($frameworks as $_framework) {
            if (empty($_framework['isactive'])) {
                continue;
            }
            $fr = $DB->get_record('competency_framework', array('idnumber' => $_framework['idnumber']));

            if (!empty($fr->id)) {
                $fr->shortname = $_framework['shortname'];
                $fr->timemodified = time();
                $fr->usermodified = $USER->id;
                // @TODO Scale configuration and taxonomies
                \core_competency\api::update_framework($fr);
            } else {
                $sysctx = \context_system::instance();
                $oframework = (object) array(
                    'contextid' => $sysctx->id,
                    'description' => $_framework['shortname'],
                    'idnumber' => $_framework['idnumber'],
                    'shortname' => mb_strimwidth($_framework['shortname'], 0, 100, "..."),
                    'scaleid' => 2,
                    'scaleconfiguration' => '[{"scaleid":"2"},{"id":1,"scaledefault":1,"proficient":1},{"id":2,"scaledefault":0,"proficient":1}]',
                    'taxonomies' => 'competency,competency,competency,competency',
                    'visible' => 1,
                    'timecreated' => time(),
                    'timemodified' => time(),
                    'usermodified' => $USER->id,
                );
                // @TODO Scale configuration and taxonomies
                $framework = \core_competency\api::create_framework($oframework);
                $fr = $DB->get_record('competency_framework', array('idnumber' => $_framework['idnumber']));
            }
            // Ensure that now a framework exists.
            if (!empty($fr->id)) {
                echo $OUTPUT->render_from_template('local_komettranslator/alert', array(
                    'type' => 'success',
                    'content' => get_string('competencyframework:processing', 'local_komettranslator', array('shortname' => $fr->shortname, 'idnumber' => $fr->idnumber)),
                ));
                $topics = self::load_topics($exacomp, $fr->idnumber);
                foreach ($topics as $topic) {
                    $ptopic = $DB->get_record('competency', array('idnumber' => $topic['idnumber']));
                    if (!empty($ptopic->id)) {
                        $ptopic->shortname = mb_strimwidth($topic['shortname'], 0, 100, "...");
                        $ptopic->description = (!empty($topic['description']) ? $topic['description'] : $topic['shortname']);
                        $ptopic->sortorder = $topic['sorting'];
                        $ptopic->timemodified = time();
                        \core_competency\api::update_competency($ptopic);
                    } else {
                        $otopic = (object) array(
                            'shortname' => mb_strimwidth($topic['shortname'], 0, 100, "..."),
                            'description' => (!empty($topic['description']) ? $topic['description'] : $topic['shortname']),
                            'idnumber' => $topic['idnumber'],
                            'competencyframeworkid' => $fr->id,
                            'parentid' => 0,
                            'path' => $fr->contextid . '/' . $fr->id,
                            'sortorder' => $topic['sorting'],
                            'timecreated' => time(),
                            'timemodified' => time(),
                            'usermodified' => $USER->id,
                        );
                        \core_competency\api::create_competency($otopic);
                        $ptopic = $DB->get_record('competency', array('idnumber' => $topic['idnumber']));
                    }
                    if (!empty($ptopic->id)) {
                        // Parent competency exists, proceed with descriptors.
                        foreach ($topic['descriptors'] as $sorting => $topic) {
                            $comp = $DB->get_record('competency', array('idnumber' => $topic['idnumber']));
                            if (!empty($comp->id)) {
                                $comp->shortname = mb_strimwidth($topic['title'], 0, 100, "...");
                                $comp->description = (!empty($topic['description']) ? $topic['description'] : $topic['title']);
                                $comp->sortorder = $sorting;
                                $comp->timemodified = time();
                                \core_competency\api::update_competency($comp);
                            } else {
                                $ocomp = (object) array(
                                    'shortname' => mb_strimwidth($topic['title'], 0, 100, "..."),
                                    'description' => (!empty($topic['description']) ? $topic['description'] : $topic['title']),
                                    'idnumber' => $topic['idnumber'],
                                    'competencyframeworkid' => $fr->id,
                                    'parentid' => $ptopic->id,
                                    'path' => $fr->contextid . '/' . $fr->id . '/' . $ptopic->id,
                                    'sortorder' => $sorting,
                                    'timecreated' => time(),
                                    'timemodified' => time(),
                                    'usermodified' => $USER->id,
                                );
                                \core_competency\api::create_competency($ocomp);
                                $comp = $DB->get_record('competency', array('idnumber' => $topic['idnumber']));
                            }
                        }
                        if (empty($comp->id)) {
                            echo $OUTPUT->render_from_template('local_komettranslator/alert', array(
                                'type' => 'danger',
                                'content' => get_string('competency:notcreated', 'local_komettranslator', array('shortname' => $ptopic->shortname, 'idnumber' => $ptopic->idnumber)),
                            ));
                        }
                    } else {
                        echo $OUTPUT->render_from_template('local_komettranslator/alert', array(
                            'type' => 'danger',
                            'content' => get_string('competency:notcreated', 'local_komettranslator', array('shortname' => $ptopic->shortname, 'idnumber' => $ptopic->idnumber)),
                        ));
                    }
                }

            } elseif ($displaywarnings) {
                echo $OUTPUT->render_from_template('local_komettranslator/alert', array(
                    'type' => 'danger',
                    'content' => get_string('competencyframework:notcreated', 'local_komettranslator', array('shortname' => $fr->shortname, 'idnumber' => $fr->idnumber)),
                ));
            }

        }

    }
}

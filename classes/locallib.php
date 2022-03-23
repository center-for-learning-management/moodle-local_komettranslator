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
            $topicidnumber = $xmldescriptor['source'] . '_' . $xmldescriptor['id'];
            $topicidnumber_array = array(
                'sourceid' => $xmldescriptor['source']->__toString(),
                'id' => $xmldescriptor['id']->__toString(),
            );
            if (empty($selection) || in_array($topicidnumber, $selection)) {
                $descriptors[$topicidnumber] = array(
                    'idnumber' => $topicidnumber,
                    'idnumber_array' => $topicidnumber_array,
                    'type' => 'topic',
                    'sorting' => intval($xmldescriptor->sorting),
                    'title' => $xmldescriptor->title->__toString(),
                    'description' => $xmldescriptor->description->__toString(),
                    'childdescriptors' => array(),
                );
                if (isset($xmldescriptor->children)) {
                    foreach ($xmldescriptor->children[0] as $xmlchilddescriptor) {
                        $descriptoridnumber = $xmlchilddescriptor['source'] . '_' . $xmlchilddescriptor['id'];
                        $descriptoridnumber_array = array(
                            'sourceid' => $xmlchilddescriptor['source']->__toString(),
                            'id' => $xmlchilddescriptor['id']->__toString(),
                        );

                        $descriptors[$topicidnumber]['childdescriptors'][] = array(
                            'idnumber' => $descriptoridnumber,
                            'idnumber_array' => $descriptoridnumber_array,
                            'type' => 'competency',
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
    public static function load_frameworks($exacomp, $displayoutput = true, $displaywarnings = true) {
        global $OUTPUT;
        $frameworks = array();
        $imploder = ' >> ';
        foreach ($exacomp->edulevels[0] as $xmledulevel) {
            $edulevel = array(
                'idnumber' => $xmledulevel['source'] . '_' . $xmledulevel['id'],
                'idnumber_array' => array(
                    'sourceid' => $xmledulevel['source']->__toString(),
                    'id' => $xmledulevel['id']->__toString(),
                ),
                'shortname' => $xmledulevel->title
            );
            foreach ($xmledulevel->schooltypes[0] as $xmlschooltype) {
                $schooltype = array(
                    'idnumber' => $xmlschooltype['source'] . '_' . $xmlschooltype['id'],
                    'idnumber_array' => array(
                        'sourceid' => $xmlschooltype['source']->__toString(),
                        'id' => $xmlschooltype['id']->__toString(),
                    ),
                    'shortname' => $xmlschooltype->title
                );
                foreach ($xmlschooltype->subjects[0] as $xmlsubject) {
                    $subject = array(
                        'idnumber' => $xmlsubject['source'] . '_' . $xmlsubject['id'],
                        'idnumber_array' => array(
                            'sourceid' => $xmlsubject['source']->__toString(),
                            'id' => $xmlsubject['id']->__toString(),
                        ),
                        'shortname' => $xmlsubject->title->__toString() . (!empty($xmlsubject->class) ? ' (' . $xmlsubject->class . ')' : '')
                    );
                    $idnumber = $xmlsubject['source'] . '_' . $xmlsubject['id'];
                    $idnumber_array = array(
                        'sourceid' => $xmlsubject['source']->__toString(),
                        'id' => $xmlsubject['id']->__toString(),
                    );
                    $idnumber_all_array = array(
                        $edulevel['idnumber_array'],
                        $schooltype['idnumber_array'],
                        $subject['idnumber_array']
                    );
                    $shortname_array = array(
                        $edulevel['shortname'],
                        $schooltype['shortname'],
                        $subject['shortname']
                    );
                    $shortname = implode($imploder, $shortname_array);

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
                        'idnumber_array' => $idnumber_array,
                        'idnumber_all_array' => $idnumber_all_array,
                        'isactive' => get_config('local_komettranslator', 'isactive_' . $idnumber),
                        'shortname' => $shortname,
                        'shortname_array' => $shortname_array,
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
     * @param mapping holds sourceid and itemid of subject.
     * @return array
     */
    public static function load_topics($exacomp, $mapping) {
        global $OUTPUT;

        $subjectidnumber = $mapping->sourceid . '_' . $mapping->itemid;

        $topics = array();

        foreach ($exacomp->edulevels[0] as $xmledulevel) {
            foreach ($xmledulevel->schooltypes[0] as $xmlschooltype) {
                foreach ($xmlschooltype->subjects[0] as $xmlsubject) {
                    $idnumber = $xmlsubject['source'] . '_' . $xmlsubject['id'];
                    $idnumber_array = array(
                        'sourceid' => $xmlsubject['source']->__toString(),
                        'id' => $xmlsubject['id']->__toString(),
                    );
                    if ($idnumber != $subjectidnumber) {
                        continue;
                    }
                    foreach ($xmlsubject->topics[0] as $xmltopic) {
                        $idnumber = $xmltopic['source'] . '_' . $xmltopic['id'];
                        $idnumber_array = array(
                            'sourceid' => $xmltopic['source']->__toString(),
                            'id' => $xmltopic['id']->__toString(),
                        );
                        $selection = array();
                        $descriptors = array();
                        foreach ($xmltopic->descriptors[0] as $xmldescriptor) {
                            $selection[] = $xmldescriptor['source'] . '_' . $xmldescriptor['id'];
                        }
                        if (count($selection) > 0) {
                            $descriptors = array_values(self::load_descriptors($exacomp, $selection));
                        }

                        $topics[] = array(
                            'idnumber' => $idnumber,
                            'idnumber_array' => $idnumber_array,
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
     * Gets, sets or unsets a mapping.
     * @param type topic, descriptor, subject or framework
     * @param sourceid of komet
     * @param itemid of komet
     * @param internalid the internal id of framework or competency, 0 if we only want to get it
     * @param remove whether or not to remove this mapping.
    **/
    public static function mapping($type, $sourceid, $itemid, $internalid = 0, $remove = false) {
        global $DB;
        if ($remove) {
            return $DB->delete_records('local_komettranslator', array('type' => $type, 'sourceid' => $sourceid, 'itemid' => $itemid));
        } else {
            $mapping = $DB->get_record('local_komettranslator', array('type' => $type, 'sourceid' => $sourceid, 'itemid' => $itemid));
            if (empty($mapping->id)) {
                if (!empty($internalid)) {
                    $mapping = (object) array(
                        'type' => $type,
                        'sourceid' => $sourceid,
                        'itemid' => $itemid,
                        'internalid' => $internalid,
                        'timecreated' => time(),
                        'timemodified' => time(),
                    );
                    $mapping->id = $DB->insert_record('local_komettranslator', $mapping);
                }
            } else {
                if (!empty($internalid)) {
                    $mapping->internalid = $internalid;
                }
                $mapping->timemodified = time();
                $DB->update_record('local_komettranslator', $mapping);
            }
            return $mapping;
        }
    }
    /**
     * Get mapping based on internalid of table competency or competency_framework.
     */
    public static function mapping_internal($type, $internalid) {
        global $DB;
        return $DB->get_record('local_komettranslator', array('type' => $type, 'internalid' => $internalid));
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
        $frameworks = self::load_frameworks($exacomp, $displayoutput, $displaywarnings);
        $descriptors = self::load_descriptors($exacomp);

        // Now loop through frameworks. If they are enabled, sync meta-data and descriptors.
        foreach ($frameworks as $_framework) {
            if (empty($_framework['isactive'])) {
                continue;
            }

            $PARENTID = 0;
            $shortnames = $_framework['shortname_array'];
            $idnumbers = $_framework['idnumber_all_array'];

            for ($i = 0; $i < count($shortnames); $i++) {
                $shortname = '' . $shortnames[$i];
                $idnumber = $idnumbers[$i];
                $sourceid = $idnumber['sourceid'];
                $id = $idnumber['id'];
                $dbidnumber = md5($sourceid . '_' . $id);

                if ($i == 0) {
                    // This is created as framework within Moodle
                    //echo "Search mapping for framework $shortname<br />";
                    $fr = $DB->get_record('competency_framework', array('idnumber' => $dbidnumber));

                    if (!empty($fr->id)) {
                        $fr->idnumber = $dbidnumber;
                        $fr->shortname = mb_strimwidth($shortname, 0, 100, "...");
                        $fr->timemodified = time();
                        $fr->usermodified = $USER->id;
                        // @TODO Scale configuration and taxonomies
                        \core_competency\api::update_framework($fr);
                        // idnumber is not updated automatically, therefore we do this directly.
                        $DB->set_field('competency_framework', 'idnumber', $fr->idnumber, array('id' => $fr->id));
                    } else {
                        $sysctx = \context_system::instance();
                        $oframework = (object) array(
                            'contextid' => $sysctx->id,
                            'description' => $shortname,
                            'idnumber' => $dbidnumber,
                            'shortname' => mb_strimwidth($shortname, 0, 100, "..."),
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
                        $framework = $DB->get_record('competency_framework', array('idnumber' => $dbidnumber));
                        $fr = $DB->get_record('competency_framework', array('id' => $framework->id));
                    }
                    self::mapping('framework', $sourceid, $id, $fr->id);
                } else {
                    //echo "Search mapping for subject $shortname<br />";
                    $node = $DB->get_record('competency', array('idnumber' => $dbidnumber));

                    if (!empty($node->id)) {
                        $node->parentid = $PARENTID;
                        $node->shortname = mb_strimwidth($shortname, 0, 100, "...");
                        $node->description = $shortname;
                        $node->timemodified = time();
                        \core_competency\api::update_competency($node);
                        // idnumber is not updated automatically, therefore we do this directly.
                        $DB->set_field('competency', 'idnumber', $dbidnumber, array('id' => $node->id));
                    } else {
                        $onode = (object) array(
                            'shortname' => mb_strimwidth($shortname, 0, 100, "..."),
                            'description' => $shortname,
                            'idnumber' => $dbidnumber,
                            'competencyframeworkid' => $fr->id,
                            'parentid' => $PARENTID,
                            'sortorder' => 0,
                            'timecreated' => time(),
                            'timemodified' => time(),
                            'usermodified' => $USER->id,
                        );
                        $competency = \core_competency\api::create_competency($onode);
                        $competency = $DB->get_record('competency', array('idnumber' => $dbidnumber));
                        $node = $DB->get_record('competency', array('id' => $competency->id));
                    }
                    $mapping = self::mapping('subject', $sourceid, $id, $node->id);
                    $PARENTID = $node->id;
                }
            }

            if ($displayoutput) {
                echo $OUTPUT->render_from_template('local_komettranslator/alert', array(
                    'type' => 'success',
                    'content' => get_string('competencyframework:processing', 'local_komettranslator', array('shortname' => $shortname, 'idnumber' => $dbidnumber)),
                ));
            }

            $topics = self::load_topics($exacomp, $mapping);

            foreach ($topics as $topic) {
                $sourceid = $topic['idnumber_array']['sourceid'];
                $id = $topic['idnumber_array']['id'];
                $dbidnumber = md5($sourceid . '_' . $id);

                $ptopic = $DB->get_record('competency', array('idnumber' => $dbidnumber));

                if (!empty($ptopic->id)) {
                    $ptopic->idnumber = $dbidnumber;
                    $ptopic->parentid = $node->id;
                    $ptopic->shortname = mb_strimwidth($topic['shortname'], 0, 100, "...");
                    $ptopic->description = (!empty($topic['description']) ? $topic['description'] : $topic['shortname']);
                    $ptopic->sortorder = $topic['sorting'];
                    $ptopic->timemodified = time();
                    \core_competency\api::update_competency($ptopic);
                    // idnumber is not updated automatically, therefore we do this directly.
                    $DB->set_field('competency', 'idnumber', $ptopic->idnumber, array('id' => $ptopic->id));
                } else {
                    $otopic = (object) array(
                        'shortname' => mb_strimwidth($topic['shortname'], 0, 100, "..."),
                        'description' => (!empty($topic['description']) ? $topic['description'] : $topic['shortname']),
                        'idnumber' => $dbidnumber,
                        'competencyframeworkid' => $fr->id,
                        'parentid' => $node->id,
                        'sortorder' => $topic['sorting'],
                        'timecreated' => time(),
                        'timemodified' => time(),
                        'usermodified' => $USER->id,
                    );
                    $competency = \core_competency\api::create_competency($otopic);
                    $ptopic = $DB->get_record('competency', array('idnumber' => $dbidnumber));
                }
                if (empty($ptopic->id)) {
                    echo $OUTPUT->render_from_template('local_komettranslator/alert', array(
                        'type' => 'danger',
                        'content' => get_string('competency:notcreated', 'local_komettranslator', array('shortname' => $topic['shortname'], 'idnumber' => $dbidnumber)),
                    ));
                } else {
                    self::mapping('topic', $sourceid, $id, $ptopic->id);
                }


                if (!empty($ptopic->id)) {
                    // Parent competency exists, proceed with descriptors.
                    foreach ($topic['descriptors'] as $sorting => $descriptor) {
                        $sourceid = $descriptor['idnumber_array']['sourceid'];
                        $id = $descriptor['idnumber_array']['id'];
                        $dbidnumber = md5($sourceid . '_' . $id);

                        $comp = $DB->get_record('competency', array('idnumber' => $dbidnumber));
                        if (!empty($comp->id)) {
                            $comp->competencyframeworkid = $fr->id;
                            $comp->idnumber = $dbidnumber;
                            $comp->parentid = $ptopic->id;
                            $comp->shortname = mb_strimwidth($descriptor['title'], 0, 100, "...");
                            $comp->description = (!empty($descriptor['description']) ? $descriptor['description'] : $descriptor['title']);
                            $comp->sortorder = $sorting;
                            $comp->timemodified = time();
                            \core_competency\api::update_competency($comp);
                            // idnumber is not updated automatically, therefore we do this directly.
                            $DB->set_field('competency', 'idnumber', $comp->idnumber, array('id' => $comp->id));
                        } else {
                            $ocomp = (object) array(
                                'shortname' => mb_strimwidth($descriptor['title'], 0, 100, "..."),
                                'description' => (!empty($descriptor['description']) ? $descriptor['description'] : $descriptor['title']),
                                'idnumber' => $dbidnumber,
                                'competencyframeworkid' => $fr->id,
                                'parentid' => $ptopic->id,
                                'sortorder' => $sorting,
                                'timecreated' => time(),
                                'timemodified' => time(),
                                'usermodified' => $USER->id,
                            );
                            $competency = \core_competency\api::create_competency($ocomp);
                            $comp = $DB->get_record('competency', array('idnumber' => $dbidnumber));
                        }

                        if (empty($comp->id)) {
                            if ($displaywarnings) {
                                echo $OUTPUT->render_from_template('local_komettranslator/alert', array(
                                    'type' => 'danger',
                                    'content' => get_string('competency:notcreated', 'local_komettranslator', array('shortname' => $descriptor['title'], 'idnumber' => $dbidnumber)),
                                ));
                            }
                        } else {
                            self::mapping('descriptor', $sourceid, $id, $comp->id);
                        }

                        if (!empty($descriptor['childdescriptors'])) {
                            foreach ($descriptor['childdescriptors'] as $sorting => $childdescriptor) {
                                $sourceid = $childdescriptor['idnumber_array']['sourceid'];
                                $id = $childdescriptor['idnumber_array']['id'];
                                $dbidnumber = md5($sourceid . '_' . $id);

                                $childcomp = $DB->get_record('competency', array('idnumber' => $dbidnumber));

                                if (!empty($childcomp->id)) {
                                    $childcomp->competencyframeworkid = $fr->id;
                                    $childcomp->idnumber = $dbidnumber;
                                    $childcomp->parentid = $comp->id;
                                    $childcomp->shortname = mb_strimwidth($childdescriptor['title'], 0, 100, "...");
                                    $childcomp->description = (!empty($childdescriptor['description']) ? $childdescriptor['description'] : $childdescriptor['title']);
                                    $childcomp->sortorder = $sorting;
                                    $childcomp->timemodified = time();
                                    \core_competency\api::update_competency($childcomp);
                                    // idnumber is not updated automatically, therefore we do this directly.
                                    //echo "compare $comp->parentid to $ptopic->id<br />";
                                    $DB->set_field('competency', 'idnumber', $childcomp->idnumber, array('id' => $childcomp->id));
                                    $DB->set_field('competency', 'parentid', $comp->id, array('id' => $childcomp->id));
                                } else {
                                    $ocomp = (object) array(
                                        'shortname' => mb_strimwidth($childdescriptor['title'], 0, 100, "..."),
                                        'description' => (!empty($childdescriptor['description']) ? $childdescriptor['description'] : $childdescriptor['title']),
                                        'idnumber' => $dbidnumber,
                                        'competencyframeworkid' => $fr->id,
                                        'parentid' => $comp->id,
                                        //'path' => $fr->contextid . '/' . $fr->id . '/' . $PARENTID,
                                        'sortorder' => $sorting,
                                        'timecreated' => time(),
                                        'timemodified' => time(),
                                        'usermodified' => $USER->id,
                                    );
                                    $competency = \core_competency\api::create_competency($ocomp);
                                    $childcomp = $DB->get_record('competency', array('idnumber' => $dbidnumber));
                                }
                                if (empty($childcomp->id)) {
                                    if ($displaywarnings) {
                                        echo $OUTPUT->render_from_template('local_komettranslator/alert', array(
                                            'type' => 'danger',
                                            'content' => get_string('competency:notcreated', 'local_komettranslator', array('shortname' => $childdescriptor['title'], 'idnumber' => $dbidnumber)),
                                        ));
                                    }
                                } else {
                                    self::mapping('descriptor', $sourceid, $id, $childcomp->id);
                                }
                            }
                        }
                    }
                } else if ($displaywarnings) {
                    echo $OUTPUT->render_from_template('local_komettranslator/alert', array(
                        'type' => 'danger',
                        'content' => get_string('competency:notcreated', 'local_komettranslator', array('shortname' => $ptopic->shortname, 'idnumber' => $ptopic->idnumber)),
                    ));
                }
            }
        }
    }
}

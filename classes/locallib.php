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

use core_competency\api;
use core_competency\competency;
use core_competency\competency_framework;

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
                'sourceid' => (string)$xmldescriptor['source'],
                'id' => (string)$xmldescriptor['id'],
            );
            if (empty($selection) || in_array($topicidnumber, $selection)) {
                $descriptors[$topicidnumber] = array(
                    'idnumber' => $topicidnumber,
                    'idnumber_array' => $topicidnumber_array,
                    'type' => 'topic',
                    'sorting' => intval($xmldescriptor->sorting),
                    'title' => (string)$xmldescriptor->title,
                    'description' => (string)$xmldescriptor->description,
                    'niveauid' => (string)$xmldescriptor->niveauid['id'],
                    'skillid' => (string)$xmldescriptor->skillid['id'],
                    'childdescriptors' => array(),
                );
                if (isset($xmldescriptor->children)) {
                    foreach ($xmldescriptor->children[0] as $xmlchilddescriptor) {
                        $descriptoridnumber = $xmlchilddescriptor['source'] . '_' . $xmlchilddescriptor['id'];
                        $descriptoridnumber_array = array(
                            'sourceid' => (string)$xmlchilddescriptor['source'],
                            'id' => (string)$xmlchilddescriptor['id'],
                        );

                        $descriptors[$topicidnumber]['childdescriptors'][] = array(
                            'idnumber' => $descriptoridnumber,
                            'idnumber_array' => $descriptoridnumber_array,
                            'type' => 'competency',
                            'sorting' => intval($xmlchilddescriptor->sorting),
                            'title' => (string)$xmlchilddescriptor->title,
                            'description' => (string)$xmlchilddescriptor->description,
                            'niveauid' => (string)$xmlchilddescriptor->niveauid['id'],
                            'skillid' => (string)$xmlchilddescriptor->skillid['id'],
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
                'shortname' => $xmledulevel->title,
            );
            foreach ($xmledulevel->schooltypes[0] as $xmlschooltype) {
                $schooltype = array(
                    'idnumber' => $xmlschooltype['source'] . '_' . $xmlschooltype['id'],
                    'idnumber_array' => array(
                        'sourceid' => $xmlschooltype['source']->__toString(),
                        'id' => $xmlschooltype['id']->__toString(),
                    ),
                    'shortname' => $xmlschooltype->title,
                );
                foreach ($xmlschooltype->subjects[0] as $xmlsubject) {
                    $subject = array(
                        'idnumber' => $xmlsubject['source'] . '_' . $xmlsubject['id'],
                        'idnumber_array' => array(
                            'sourceid' => $xmlsubject['source']->__toString(),
                            'id' => $xmlsubject['id']->__toString(),
                        ),
                        'shortname' => $xmlsubject->title->__toString() . (!empty($xmlsubject->class) ? ' (' . $xmlsubject->class . ')' : ''),
                    );
                    $idnumber = $xmlsubject['source'] . '_' . $xmlsubject['id'];
                    $idnumber_array = array(
                        'sourceid' => $xmlsubject['source']->__toString(),
                        'id' => $xmlsubject['id']->__toString(),
                    );
                    $idnumber_all_array = array(
                        $edulevel['idnumber_array'],
                        $schooltype['idnumber_array'],
                        $subject['idnumber_array'],
                    );
                    $shortname_array = array(
                        $edulevel['shortname'],
                        $schooltype['shortname'],
                        $subject['shortname'],
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

        if (file_exists(__DIR__ . '/../data.xml')) {
            // load local data.xml(doesn't need the webservice)
            // needed for test.eduvidual.at, because of a wrong config https://komet.eeducation.at/uploads/data.xml is not reachable
            if ($displaywarnings) {
                echo $OUTPUT->render_from_template('local_komettranslator/alert', array(
                    'type' => 'info',
                    'content' => get_string('xmlurl:loading', 'local_komettranslator', array('xmlurl' => __DIR__ . '/../data.xml' . ' (Local Dev!)')),
                ));
            }

            $c = file_get_contents(__DIR__ . '/../data.xml');
            return new \SimpleXMLElement($c);
        }

        global $CFG;
        require_once("$CFG->dirroot/lib/filelib.php");

        if ($displaywarnings) {
            echo $OUTPUT->render_from_template('local_komettranslator/alert', array(
                'type' => 'info',
                'content' => get_string('xmlurl:loading', 'local_komettranslator', array('xmlurl' => $xmlurl)),
            ));
        }

        $sslverify = get_config('local_komettranslator', 'xmlurlsslverify');
        if (empty($sslverify)) {
            if ($displaywarnings) {
                echo $OUTPUT->render_from_template('local_komettranslator/alert', array(
                    'type' => 'danger',
                    'content' => get_string('xmlurl:verifypeer:warning', 'local_komettranslator'),
                ));
            }
        }
        $sslskipverify = empty($sslverify) ? true : false;

        $response = download_file_content($xmlurl, null, null, true, 300, 20, $sslskipverify);
        if (!empty($response->error) || $response->status != '200' || empty($response->results)) {
            throw new \moodle_exception($response->error . " (url: {$xmlurl})");
        }

        return new \SimpleXMLElement($response->results);
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
        }

        $mapping = $DB->get_record('local_komettranslator', array('type' => $type, 'sourceid' => $sourceid, 'itemid' => $itemid));
        if (!$mapping) {
            if ($internalid) {
                $mapping = (object)array(
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
            if ($internalid) {
                $mapping->internalid = $internalid;
            }
            $mapping->timemodified = time();
            $DB->update_record('local_komettranslator', $mapping);
        }

        return $mapping;
    }

    /**
     * Get mapping based on internalid of table competency or competency_framework.
     */
    public static function mapping_internal($type, $internalid) {
        global $DB;
        return $DB->get_record('local_komettranslator', array('type' => $type, 'internalid' => $internalid));
    }

    private static function update_competency($comp, $data) {
        global $DB;

        // disabled this comparison, no performance boost and doesn't work like this anymore
        // because if the parent moves to a different path, the path of children also needs to be updated
        // although the parentid is the same

        // $old_comp = clone $comp;
        // foreach ($data as $key => $value) {
        //     // convert to string, because db row is always string
        //     $comp->$key = $value === null ? null : (string)$value;
        // }

        // if (json_encode($old_comp) === json_encode($comp)) {
        //     return;
        // }

        // echo 'update!! ';

        // var_dump([$old_comp, $comp]);
        // echo "\n" . json_encode($old_comp) . "\n" . json_encode($comp);
        // exit;

        $comp->timemodified = time();

        try {
            \core_competency\api::update_competency($comp);
        } catch (\Exception $e) {
            echo 'Skipping Update, Error: ' . $e->getMessage() . "\n";
            return;
        }

        // use $data instead of $comp, because $comp properties are overwritten in update_competency()

        // idnumber is not updated automatically, therefore we do this directly.
        $DB->set_field('competency', 'idnumber', $data->idnumber, array('id' => $comp->id));

        if (!empty($data->sortorder) && $comp->sortorder != $data->sortorder) {
            $DB->set_field('competency', 'sortorder', $data->sortorder, array('id' => $comp->id));
        }

        if (!isset($data->parentid)) {
            throw new \moodle_exception('parentid not set!');
        }

        if ($data->parentid) {
            $parent = $DB->get_record('competency', array('id' => $data->parentid));
            if (!$parent) {
                throw new \moodle_exception("parent not found for {$data->id}, parentid: {$data->parentid}");
            }

            // fix https://github.com/center-for-learning-management/eduvidual-src/issues/1668
            // also set path attribute, it has to end with the parentid
            $path = $parent->path . $data->parentid . '/';
        } else {
            $path = '/0/';
        }

        if (($comp->parentid != $data->parentid || $comp->path != $path)) {
            // update the parent, because it is not updated in api::update_competency()

            $DB->update_record('competency', [
                'id' => $comp->id,
                'parentid' => $data->parentid,
                'path' => $path,
            ]);
        }

        if (!empty($data->competencyframeworkid) && $comp->competencyframeworkid != $data->competencyframeworkid) {
            $DB->set_field('competency', 'competencyframeworkid', $data->competencyframeworkid, array('id' => $comp->id));
        }
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
        // $descriptors = self::load_descriptors($exacomp);

        $niveaus = [];
        foreach ($exacomp->niveaus->niveau as $niveau) {
            $niveaus[(string)$niveau['id']] = (string)$niveau->title;
        }

        $format_name_and_description = function($data, $xmlComp) use ($niveaus) {
            if (!$xmlComp['niveauid'] || empty($niveaus[$xmlComp['niveauid']])) {
                $niveau_short = '';
                $niveau_long = '';
            } else {
                $niveau_short = ' (' . mb_strimwidth($niveaus[$xmlComp['niveauid']], 0, 40, "...") . ')';
                $niveau_long = ' (' . $niveaus[$xmlComp['niveauid']] . ')';
            }

            $data->shortname = mb_strimwidth($data->shortname, 0, 100 - strlen($niveau_short), "...") . $niveau_short;
            $data->description .= $niveau_long;
        };

        static::delete_old_mappings();

        $oldMappings = [
            'framework' => $DB->get_records('local_komettranslator', ['type' => 'framework']),
            'subject' => $DB->get_records('local_komettranslator', ['type' => 'subject']),
            'topic' => $DB->get_records('local_komettranslator', ['type' => 'topic']),
            'descriptor' => $DB->get_records('local_komettranslator', ['type' => 'descriptor']),
        ];

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
                        $oframework = (object)array(
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
                    $mapping = self::mapping('framework', $sourceid, $id, $fr->id);
                    unset($oldMappings['framework'][$mapping->id]);
                } else {
                    //echo "Search mapping for subject $shortname<br />";
                    $node = $DB->get_record('competency', array('idnumber' => $dbidnumber));

                    if (!empty($node->id)) {
                        $data = (object)[];
                        $data->competencyframeworkid = $fr->id;
                        $data->idnumber = $dbidnumber;
                        $data->parentid = $PARENTID;
                        $data->shortname = mb_strimwidth($shortname, 0, 100, "...");
                        $data->description = $shortname;
                        static::update_competency($node, $data);
                    } else {
                        $onode = (object)array(
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
                    unset($oldMappings['subject'][$mapping->id]);
                    $PARENTID = $node->id;
                }
            }

            if ($displayoutput) {
                echo $OUTPUT->render_from_template('local_komettranslator/alert', array(
                    'type' => 'success',
                    'content' => get_string('competencyframework:processing', 'local_komettranslator', array('shortname' => $shortname, 'idnumber' => $dbidnumber)),
                ));
            }

            // ist da wo ein Fehler?
            $topics = self::load_topics($exacomp, $mapping);

            foreach ($topics as $topic) {
                $sourceid = $topic['idnumber_array']['sourceid'];
                $id = $topic['idnumber_array']['id'];
                $dbidnumber = md5($sourceid . '_' . $id);

                $ptopic = $DB->get_record('competency', array('idnumber' => $dbidnumber));

                if ($ptopic) {
                    $data = (object)[];
                    $data->competencyframeworkid = $fr->id;
                    $data->idnumber = $dbidnumber;
                    $data->parentid = $node->id;
                    $data->shortname = mb_strimwidth($topic['shortname'], 0, 100, "...");
                    $data->description = (!empty($topic['description']) ? $topic['description'] : $topic['shortname']);
                    $data->sortorder = $topic['sorting'];
                    static::update_competency($ptopic, $data);
                } else {
                    $otopic = (object)array(
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

                if (!$ptopic) {
                    throw new \moodle_exception('topic not created in db?!?');
                    // echo $OUTPUT->render_from_template('local_komettranslator/alert', array(
                    //     'type' => 'danger',
                    //     'content' => get_string('competency:notcreated', 'local_komettranslator', array('shortname' => $topic['shortname'], 'idnumber' => $dbidnumber)),
                    // ));
                }


                $mapping = self::mapping('topic', $sourceid, $id, $ptopic->id);
                unset($oldMappings['topic'][$mapping->id]);

                // Parent competency exists, proceed with descriptors.
                foreach ($topic['descriptors'] as $sorting => $descriptor) {
                    $sourceid = $descriptor['idnumber_array']['sourceid'];
                    $id = $descriptor['idnumber_array']['id'];
                    $dbidnumber = md5($sourceid . '_' . $id);

                    $comp = $DB->get_record('competency', array('idnumber' => $dbidnumber));
                    if ($comp) {
                        $data = (object)[];
                        $data->competencyframeworkid = $fr->id;
                        $data->idnumber = $dbidnumber;
                        $data->parentid = $ptopic->id;
                        $data->shortname = $descriptor['title'];
                        $data->description = (!empty($descriptor['description']) ? $descriptor['description'] : $descriptor['title']);
                        $data->sortorder = $sorting;
                        $format_name_and_description($data, $descriptor);
                        static::update_competency($comp, $data);
                    } else {
                        $data = (object)array(
                            'shortname' => $descriptor['title'],
                            'description' => (!empty($descriptor['description']) ? $descriptor['description'] : $descriptor['title']),
                            'idnumber' => $dbidnumber,
                            'competencyframeworkid' => $fr->id,
                            'parentid' => $ptopic->id,
                            'sortorder' => $sorting,
                            'timecreated' => time(),
                            'timemodified' => time(),
                            'usermodified' => $USER->id,
                        );
                        $format_name_and_description($data, $descriptor);

                        \core_competency\api::create_competency($data);
                        $comp = $DB->get_record('competency', array('idnumber' => $dbidnumber));
                    }

                    if (!$comp) {
                        throw new \moodle_exception('descriptor not created in db?!?');
                        // if ($displaywarnings) {
                        //     echo $OUTPUT->render_from_template('local_komettranslator/alert', array(
                        //         'type' => 'danger',
                        //         'content' => get_string('competency:notcreated', 'local_komettranslator', array('shortname' => $descriptor['title'], 'idnumber' => $dbidnumber)),
                        //     ));
                        // }
                    } else {
                        $mapping = self::mapping('descriptor', $sourceid, $id, $comp->id);
                        unset($oldMappings['descriptor'][$mapping->id]);
                    }

                    if (!empty($descriptor['childdescriptors'])) {
                        foreach ($descriptor['childdescriptors'] as $sorting => $childdescriptor) {
                            $sourceid = $childdescriptor['idnumber_array']['sourceid'];
                            $id = $childdescriptor['idnumber_array']['id'];
                            $dbidnumber = md5($sourceid . '_' . $id);

                            $childcomp = $DB->get_record('competency', array('idnumber' => $dbidnumber));

                            if ($childcomp) {
                                $data = (object)[];
                                $data->competencyframeworkid = $fr->id;
                                $data->idnumber = $dbidnumber;
                                $data->parentid = $comp->id;
                                $data->shortname = $childdescriptor['title'];
                                $data->description = (!empty($childdescriptor['description']) ? $childdescriptor['description'] : $childdescriptor['title']);
                                $data->sortorder = $sorting;
                                $format_name_and_description($data, $childdescriptor);
                                static::update_competency($childcomp, $data);
                            } else {
                                $data = (object)array(
                                    'shortname' => mb_strimwidth($childdescriptor['title'], 0, 100, "..."),
                                    'description' => (!empty($childdescriptor['description']) ? $childdescriptor['description'] : $childdescriptor['title']),
                                    'idnumber' => $dbidnumber,
                                    'competencyframeworkid' => $fr->id,
                                    'parentid' => $comp->id,
                                    'sortorder' => $sorting,
                                    'timecreated' => time(),
                                    'timemodified' => time(),
                                    'usermodified' => $USER->id,
                                );
                                $format_name_and_description($data, $childdescriptor);
                                \core_competency\api::create_competency($data);
                                $childcomp = $DB->get_record('competency', array('idnumber' => $dbidnumber));
                            }
                            if (!$childcomp) {
                                throw new \moodle_exception('descriptor not created in db?!?');
                                // if ($displaywarnings) {
                                //     echo $OUTPUT->render_from_template('local_komettranslator/alert', array(
                                //         'type' => 'danger',
                                //         'content' => get_string('competency:notcreated', 'local_komettranslator', array('shortname' => $childdescriptor['title'], 'idnumber' => $dbidnumber)),
                                //     ));
                                // }
                            } else {
                                $mapping = self::mapping('descriptor', $sourceid, $id, $childcomp->id);
                                unset($oldMappings['descriptor'][$mapping->id]);
                            }
                        }
                    }
                }
            }
        }

        $oldMappings = array_filter($oldMappings);

        $trashFramework = static::getTrashFramework();

        $skipMoveIds = [];
        if ($oldMappings) {
            foreach ($oldMappings as $type => $oldMappingsOfType) {
                if ($displayoutput) {
                    echo "<h3>Löschen von {$type} (" . count($oldMappingsOfType) . "):</h3>";
                }
                foreach ($oldMappingsOfType as $oldMapping) {
                    if ($type == 'framework') {
                        $fr = $DB->get_record('competency_framework', array('id' => $oldMapping->internalid));
                        if ($displayoutput) {
                            echo 'Zu löschen: ' . $fr->shortname . '<br/>';
                        }
                        if ($fr) {
                            echo 'Deleting a Frameworks not implemented';
                        }
                    } else {
                        $competency = new competency($oldMapping->internalid);
                        if ($displayoutput) {
                            echo 'Zu löschen: ' . $competency->get('shortname') . '<br/>';
                        }

                        $competencyids = array(intval($competency->get('id')));
                        $competencyids = array_merge(competency::get_descendants_ids($competency), $competencyids);
                        $delete = competency::can_all_be_deleted($competencyids);

                        if ($delete) {
                            echo '&nbsp;&nbsp;&nbsp;==> alles löschen<br/>';

                            \core_competency\api::delete_competency($competency->get('id'));
                        } elseif ($competency->get('competencyframeworkid') == $trashFramework->get('id')) {
                            echo '&nbsp;&nbsp;&nbsp;==> schon im Müllkorb<br/>';
                            $skipMoveIds = array_merge($skipMoveIds, competency::get_descendants_ids($competency));
                        } elseif (!in_array($oldMapping->internalid, $skipMoveIds)) {
                            echo '&nbsp;&nbsp;&nbsp;==> verschieben<br/>';

                            // $trashCompetency = static::getTrashCompetency($competency);

                            // move to top level
                            if ($competency->get('parentid') > 0) {
                                api::set_parent_competency($competency->get('id'), 0);
                            }

                            foreach ($competencyids as $id) {
                                $DB->update_record('competency', ['id' => $id, 'competencyframeworkid' => $trashFramework->get('id')]);
                            }

                            // wenn die competency verschoben wird, müssen die subids nicht verschoben werden und können ignoriert werden
                            $skipMoveIds = array_merge($skipMoveIds, competency::get_descendants_ids($competency));
                        }
                    }
                }
            }
        }

        static::delete_old_mappings();
    }

    /**
     * delete komettranslator mappings, which don't have competencies anymore
     */
    public static function delete_old_mappings() {
        global $DB;

        $DB->execute("DELETE FROM {local_komettranslator}
        WHERE (type='subject' OR type='topic' OR type='descriptor')
        AND internalid NOT IN
            (SELECT id FROM {competency})");
    }

    private static function getTrashFramework(): competency_framework {
        global $DB, $USER;

        static $trashFramework = null;
        if ($trashFramework) {
            return $trashFramework;
        }

        $row = $DB->get_record('competency_framework', ['idnumber' => 'komettranslator-trash']);
        if (!$row) {
            $sysctx = \context_system::instance();
            $row = (object)array(
                'contextid' => $sysctx->id,
                'description' => 'Alte Kompetenzrahmen',
                'idnumber' => 'komettranslator-trash',
                'shortname' => 'Alte Kompetenzrahmen',
                'scaleid' => 2,
                'scaleconfiguration' => '[{"scaleid":"2"},{"id":1,"scaledefault":1,"proficient":1},{"id":2,"scaledefault":0,"proficient":1}]',
                'taxonomies' => 'competency,competency,competency,competency',
                'visible' => 0,
                'timecreated' => time(),
                'timemodified' => time(),
                'usermodified' => $USER->id,
            );

            $trashFramework = \core_competency\api::create_framework($row);
        } else {
            $trashFramework = new competency_framework($row->id);
        }

        return $trashFramework;
    }
}

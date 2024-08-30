<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace block_floatingbutton;

use context_course;

/**
 * Autoupdate class for block_floatingbutton
 *
 * @package     block_floatingbutton
 * @copyright   2021-2022, ISB Bayern
 * @author      Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class autoupdate {
    /**
     * Called when a course_module_deleted_updated event is triggered. Removed the deleted module in all
     * floating buttons in the course of the activity.
     *
     * @param \core\event\base $event
     * @return void
     */
    public static function update_from_delete_event(\core\event\base $event): void {
        global $DB;
        $data = $event->get_data();
        if (isset($data['courseid']) && $data['courseid'] > 0) {
            $contexts = context_course::instance($data['courseid'])->get_child_contexts();
            foreach ($contexts as $context) {
                if ($context instanceof \context_block) {
                    if ($context->get_context_name(false) == get_string('floatingbutton', 'block_floatingbutton')) {
                        $configdata = $DB->get_field('block_instances', 'configdata', ['id' => $context->instanceid]);
                        $configdata = unserialize_object(base64_decode($configdata));
                        $changed = false;
                        foreach ($configdata->cmid as $key => $cmid) {
                            if ($cmid == $data['objectid']) {
                                $configdata->cmid[$key] = null;
                                $changed = true;
                            }
                        }
                        if ($changed) {
                            $configdata = base64_encode(serialize($configdata));
                            $DB->set_field('block_instances', 'configdata', $configdata, ['id' => $context->instanceid]);
                        }
                    }
                }
            }
        }
    }
}

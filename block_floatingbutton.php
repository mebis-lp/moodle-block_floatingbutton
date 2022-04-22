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

defined('MOODLE_INTERNAL') || die();

/**
 * Block class for block_floatingbutton
 *
 * @package    block_floatingbutton
 * @copyright  2022 ISB Bayern
 * @author     Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_floatingbutton extends block_base {
    /**
     * Initialize block
     *
     * @return void
     */
    public function init() {
        $this->title = get_string('floatingbutton', 'block_floatingbutton');
        $this->speciallinks = [
            'turn_editing_on',
            'next_section',
            'previous_section',
            'back_to_main_page',
            'back_to_activity_section',
            'change_editor'
        ];
    }

    /**
     * Allow the block to have a configuration page
     *
     * @return boolean
     */
    public function has_config() {
        return true;
    }

    /**
     * Adds the block content to the page header if user is not editing.
     *
     * @return void
     */
    public function specialization() {
        if (!$this->page->user_is_editing()) {
            $this->page->add_header_action($this->get_content()->text);
        }
    }

    /**
     * Returns true as block shouldn't be shown as block.
     *
     * @return boolean
     */
    public function is_empty() {
        return true;
    }

    /**
     * Returns the special link types, i.e. moodle functions.
     *
     * @return array
     */
    public function get_special_links() {
        return $this->speciallinks;
    }

    /**
     * Returns the block content. Content is cached for performance reasons.
     *
     * @return string
     */
    public function get_content() {
        if ($this->content !== null) {
            return $this->content;
        }
        global $OUTPUT;
        $this->content = new stdClass;
        $context = new stdClass;

        if ($this->page->course) {
            $context->courseid = $this->page->course->id;
            $modinfo = get_fast_modinfo($context->courseid);

            if ($this->page->cm) {
                $context->cmid = $this->page->cm->id;
                $context->sectionid = $this->page->cm->sectionnum;
            } else {
                $context->sectionid = optional_param('section', 0, PARAM_INT);
            }

            if ($context->sectionid > 0) {
                $context->prevsectionid = $context->sectionid - 1;
            }
            if ($context->sectionid < count($modinfo->get_section_info_all()) - 1) {
                $context->nextsectionid = $context->sectionid + 1;
            }
        }
        if (isset($this->config)) {
            $context->horizontal_space = $this->config->horizontal_space;
            $context->vertical_space = $this->config->vertical_space;
        }
        $context->sesskey = sesskey();

        if ($this->page->user_allowed_editing()) {
            $context->editing = $this->page->user_is_editing();
        }

        $context->icons = [];

        if (!is_null($this->config)) {
            for ($i = 0; $i < $this->config->icon_number; $i++) {
                if (!isset($this->config->icon[$i]) || $this->config->icon[$i] == '') {
                    continue;
                }
                $url = null;
                $edit = false;
                switch($this->config->type[$i]) {
                    case 'internal':
                        $url = $this->config->internalurl[$i];
                        break;
                    case 'external':
                        $url = $this->config->externalurl[$i];
                        break;
                    case 'special':
                        switch($this->config->speciallink[$i]) {
                            case 'turn_editing_on':
                                $url = '' . (new moodle_url('/course/view.php'));
                                $edit = true;
                                break;
                            case 'next_section':
                                if (isset($context->nextsectionid)) {
                                    $url = '' . (new moodle_url(
                                    '/course/view.php',
                                    ['id' => $context->courseid, 'section' => $context->nextsectionid]
                                    ));
                                }
                                break;
                            case 'previous_section':
                                if (isset($context->prevsectionid)) {
                                    $url = '' . (new moodle_url(
                                    '/course/view.php',
                                    ['id' => $context->courseid, 'section' => $context->prevsectionid]
                                    ));
                                }
                                break;
                            case 'back_to_main_page':
                                $url = '' . (new moodle_url(
                                    '/course/view.php',
                                    ['id' => $context->courseid]
                                ));
                                break;
                            case 'back_to_activity_section':
                                if (!is_null($context->cmid)) {
                                    $url = '' . (new moodle_url(
                                    '/course/view.php',
                                    ['id' => $context->section]
                                    ));
                                }
                                break;
                            case 'change_editor':
                                $url = '' . (new moodle_url(
                                    '/user/editor.php',
                                    ['course' => $context->courseid])
                                );
                                break;
                        }
                }
                $backgroundcolor = (
                    $this->config->customlayout[$i] == 1 ?
                    $this->config->backgroundcolor[$i] :
                    $this->config->defaultbackgroundcolor
                );
                $textcolor = (
                    $this->config->customlayout[$i] == 1 ?
                    $this->config->textcolor[$i] :
                    $this->config->defaulttextcolor
                );
                $icon = [
                    'name' => $this->config->name[$i],
                    'url' => $url,
                    'icon' => $this->config->icon[$i],
                    'edit' => $edit,
                    'backgroundcolor' => $backgroundcolor,
                    'textcolor' => $textcolor
                ];
                $context->icons[] = $icon;
                $context->positionhorizontal = $this->config->positionhorizontal;
                $context->positionvertical = $this->config->positionvertical;
            }
            $this->content->text = $OUTPUT->render_from_template('block_floatingbutton/icons', $context);
        }

        return $this->content;
    }

    /**
     * Returns false as there can be only one floating button block on one page to avoid collisions.
     *
     * @return void
     */
    public function instance_allow_multiple() {
        return false;
    }

    /**
     * Returns on which page formats this block can be used.
     *
     * @return array
     */
    public function applicable_formats() {
        return ['site-index' => false, 'course-view' => true, 'mod' => true];
    }
}

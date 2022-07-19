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
     * @throws coding_exception
     */
    public function init(): void {
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
     * @return bool
     */
    public function has_config(): bool {
        return true;
    }

    /**
     * Adds the block content to the page header.
     *
     * @return void
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function specialization(): void {
        if (!empty($this->instance->visible)) {
            $this->get_content();
            $this->page->add_header_action($this->content->text);
        }
    }

    /**
     * Returns true as block shouldn't be shown as block.
     *
     * @return bool
     */
    public function is_empty(): bool {
        return true;
    }

    /**
     * Returns the special link types, i.e. moodle functions.
     *
     * @return array
     */
    public function get_special_links(): array {
        return $this->speciallinks;
    }

    /**
     * Returns the block content. Content is cached for performance reasons.
     *
     * @return stdClass
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function get_content(): stdClass {
        global $OUTPUT;
        $dummy = new stdClass;
        $dummy->text = '';
        if ($this->content !== null) {
            return $dummy;
        }

        $this->content = new stdClass;
        $context = new stdClass;

        if ($this->page->course) {
            $context->courseid = $this->page->course->id;
            $modinfo = get_fast_modinfo($context->courseid);

            if ($this->page->cm) {
                $context->cmid = $this->page->cm->id;
                $context->sectionid = $this->page->cm->sectionnum;
            } else {
                $context->cmid = null;
                $context->sectionid = optional_param('section', 0, PARAM_INT);
            }

            if ($context->sectionid > 0) {
                $context->prevsectionid = $context->sectionid - 1;
            }
            if ($context->sectionid < count($modinfo->get_section_info_all()) - 1) {
                $context->nextsectionid = $context->sectionid + 1;
            }
        }
        $context->sesskey = sesskey();

        if ($this->page->user_allowed_editing()) {
            $context->editing = $this->page->user_is_editing();
        }

        $context->icons = [];

        if (!is_null($this->config)) {
            $format = core_courseformat\base::instance($context->courseid);
            for ($i = 0; $i < $this->config->icon_number; $i++) {
                if (empty($this->config->icon[$i])) {
                    continue;
                }
                $url = null;
                $edit = false;
                $notavailable = false;
                $name = null;
                switch ($this->config->type[$i]) {
                    case 'internal':
                        // Skip empty internal links (this could happen, when a course module that is referenced by an icon
                        // is not included in backup).
                        if (!is_null($this->config->cmid[$i])) {
                            list($type, $id) = explode('=', $this->config->cmid[$i]);
                            switch ($type) {
                                case 'cmid':
                                    if (in_array($id, array_keys($modinfo->get_cms()))) {
                                        $module = $modinfo->get_cm($id);
                                        $name = $module->name;
                                        $notavailable = !$module->available;
                                        if (!is_null($module->url)) {
                                            // Link modules that have a view page to their corresponding url.
                                            $url = '' . $module->url;
                                        } else {
                                            // Other modules (like labels) are shown on the course page. Link to the corresponding
                                            // anchor.
                                            $url = $format->get_view_url($module->sectionnum) . '#module-' . $id;
                                        }
                                    } else {
                                        $notavailable = true;
                                    }
                                    break;
                                case 'section':
                                    $sectioninfo = $modinfo->get_section_info($id);
                                    if (!is_null($sectioninfo)) {
                                        $name = $sectioninfo->name;
                                        if (empty($name)) {
                                            if ($id == 0) {
                                                $name = get_string('general');
                                            } else {
                                                $name = get_string('section') . ' ' . $id;
                                            }
                                        }
                                        $notavailable = !$sectioninfo->available;
                                        $url = $format->get_view_url($id);
                                    } else {
                                        $notavailable = true;
                                    }
                                    break;
                            }
                        }
                        break;
                    case 'external':
                        $url = $this->config->externalurl[$i];
                        break;
                    case 'special':
                        switch ($this->config->speciallink[$i]) {
                            case 'turn_editing_on':
                                $url = '' . (new moodle_url('/course/view.php'));
                                $edit = true;
                                $notavailable = !$this->page->user_allowed_editing();
                                break;
                            case 'next_section':
                                if (isset($context->nextsectionid)) {
                                    $url = $format->get_view_url($context->nextsectionid);
                                    $name = get_string('nextsection');
                                }
                                break;
                            case 'previous_section':
                                if (isset($context->prevsectionid)) {
                                    $url = $format->get_view_url($context->prevsectionid);
                                    $name = get_string('previoussection');
                                }
                                break;
                            case 'back_to_main_page':
                                $url = '' . (new moodle_url(
                                        '/course/view.php',
                                        ['id' => $context->courseid]
                                    ));
                                $name = get_string('back_to_main_page', 'block_floatingbutton');
                                break;
                            case 'back_to_activity_section':
                                if (!is_null($context->cmid)) {
                                    $url = $format->get_view_url($context->sectionid);
                                    $name = get_string('back_to_activity_section', 'block_floatingbutton');
                                } else {
                                    $notavailable = true;
                                }
                                break;
                            case 'change_editor':
                                $url = '' . (new moodle_url(
                                        '/user/editor.php',
                                        ['course' => $context->courseid])
                                    );
                                $name = get_string('editorpreferences');
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
                    'name' => (empty($this->config->name[$i]) ? $name : $this->config->name[$i]),
                    'url' => $url,
                    'icon' => $this->config->icon[$i],
                    'edit' => $edit,
                    'backgroundcolor' => $backgroundcolor,
                    'textcolor' => $textcolor,
                    'notavailable' => $notavailable
                ];
                $context->icons[] = $icon;
            }
            $this->content->text = $OUTPUT->render_from_template('block_floatingbutton/icons', $context);
        } else {
            $this->content->text = '';
        }

        return $dummy;
    }

    /**
     * Returns false as there can be only one floating button block on one page to avoid collisions.
     *
     * @return bool
     */
    public function instance_allow_multiple(): bool {
        return false;
    }

    /**
     * Returns on which page formats this block can be used.
     *
     * @return array
     */
    public function applicable_formats(): array {
        return ['site-index' => false, 'course-view' => true, 'mod' => true];
    }
}

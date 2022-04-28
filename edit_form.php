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
 * Edit form for block_floatingbutton
 *
 * @package    block_floatingbutton
 * @copyright  2022 ISB Bayern
 * @author     Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_floatingbutton_edit_form extends block_edit_form {

    /**
     * Form definition - call to parent definition() is avoided here to get
     * correct order.
     *
     * @return void
     */
    public function definition() {
    }

    /**
     * Add specific elements to the standard block form
     *
     * @param stdClass $mform
     * @return void
     */
    protected function specific_definition($mform) {
        $positionsvertical = [
            'bottom' => get_string('bottom', 'block_floatingbutton'),
            'top' => get_string('top', 'block_floatingbutton')
        ];

        $positionshorizontal = [
            'right' => get_string('right', 'block_floatingbutton'),
            'left' => get_string('left', 'block_floatingbutton')
        ];

        $mform->addElement('header', 'config_header', get_string('blocksettings', 'block'));
        $mform->addElement(
            'text',
            'config_defaultbackgroundcolor',
            get_string('defaultbackgroundcolor', 'block_floatingbutton'),
            ['class' => 'mbs-floatingbutton-color-input']
        );
        $mform->setDefault('config_defaultbackgroundcolor', get_config('block_floatingbutton', 'defaultbackgroundcolor'));
        $mform->setType('config_defaultbackgroundcolor', PARAM_TEXT);
        $mform->addElement(
            'text',
            'config_defaulttextcolor',
            get_string('defaulttextcolor', 'block_floatingbutton'),
            ['class' => 'mbs-floatingbutton-color-input']
        );
        $mform->setDefault('config_defaulttextcolor', get_config('block_floatingbutton', 'defaulttextcolor'));
        $mform->setType('config_defaulttextcolor', PARAM_TEXT);
        $mform->addElement(
            'select',
            'config_positionvertical',
            get_string('positionvertical', 'block_floatingbutton'),
            $positionsvertical
        );
        $mform->addElement(
            'select',
            'config_positionhorizontal',
            get_string('positionhorizontal', 'block_floatingbutton'),
            $positionshorizontal
        );
        $mform->addElement(
            'text',
            'config_horizontal_space',
            get_string('horizontal_space', 'block_floatingbutton')
        );
        $mform->setType('config_horizontalspace', PARAM_INT);
        $mform->addElement(
            'text',
            'config_vertical_space',
            get_string('vertical_space', 'block_floatingbutton')
        );
        $mform->setType('config_verticalspace', PARAM_INT);
    }

    /**
     * Loads the modules of the corresponding course (if there is one).
     *
     * @return array
     */
    public function get_course_modules() {
        if (isset($this->courselist)) {
            return;
        }

        global $CFG;

        if (!is_null($this->page->course)) {
            $this->iscourse = true;
            $courseid = $this->page->course->id;
        }

        $cm = get_fast_modinfo($courseid);

        $courselist = [];

        foreach ($cm->sections as $sectionnum => $section) {
            $sectioninfo = $cm->get_section_info($sectionnum);
            $url = $CFG->wwwroot . '/course/view.php?id=' . $courseid . '&section=' . $sectionnum;
            $name = $sectioninfo->name;
            if (empty($name)) {
                $name = get_string('section') . ' ' . $sectionnum;
            }
            $courselist[$url] = '--- ' . $name . ' ---';

            foreach ($section as $cmid) {
                $module = $cm->get_cm($cmid);
                // Get only course modules which are not deleted.
                if ($module->deletioninprogress == 0) {
                    if (!is_null($module->url)) {
                        // Link modules that have a view page to their corresponding url.
                        $url = '' . $module->url;
                    } else {
                        // Other modules (like labels) are shown on the course page. Link to the corresponding anchor.
                        $url = $CFG->wwwroot . '/course/view.php?id=' . $courseid . '&section=' . $sectionnum . '#module-' . $cmid;
                    }
                    $courselist[$url] = $module->name;
                }
            }
        }
        $this->courselist = $courselist;
    }

    /**
     * Called when data / defaults are already loaded.
     *
     * @return void
     */
    public function definition_after_data() {
        global $PAGE;

        $types = [
            'internal' => get_string('type_internal', 'block_floatingbutton'),
            'external' => get_string('type_external', 'block_floatingbutton'),
            'special' => get_string('type_special', 'block_floatingbutton')
        ];

        $speciallinks = $this->block->get_special_links();
        $speciallinkoptions = [];
        foreach ($speciallinks as $k) {
            $speciallinkoptions[$k] = get_string($k, 'block_floatingbutton');
        }

        $mform = & $this->_form;

        $mform->addElement(
            'html',
            '<div class="mbs-iconpicker-container hide"></div>'
        );

        $data = $mform->_defaultValues;

        $this->get_course_modules();

        $repeatarray = [];
        $repeatarray[] = $mform->createElement(
            'header',
            'config_header',
            get_string('icon', 'block_floatingbutton') . ' {no}'
        );
        $repeatarray[] = $mform->createElement('html', '<div class="row"><div class="col-lg">');
        $repeatarray[] = $mform->createElement(
            'text',
            'config_name',
            get_string('name', 'block_floatingbutton')
        );
        $repeatarray[] = $mform->createElement('html', '</div><div class="col-lg">');
        $repeatarray[] = $mform->createElement(
            'select',
            'config_type',
            get_string('type', 'block_floatingbutton'),
            $types
        );
        $repeatarray[] = $mform->createElement('html', '</div></div><div class="row"><div class="col-lg">');
        $repeatarray[] = $mform->createElement(
            'url',
            'config_externalurl',
            get_string('externalurl', 'block_floatingbutton')
        );
        $repeatarray[] = $mform->createElement(
            'select',
            'config_internalurl',
            get_string('internalurl', 'block_floatingbutton'),
            $this->courselist
        );
        $repeatarray[] = $mform->createElement(
            'select',
            'config_speciallink',
            get_string('speciallink', 'block_floatingbutton'),
            $speciallinkoptions
        );
        $repeatarray[] = $mform->createElement('html', '</div><div class="col-lg">');
        $repeatarray[] = $mform->createElement(
            'text',
            'config_icon',
            get_string('icon', 'block_floatingbutton'),
            ['class' => 'mbs-floatingicon-input']
        );
        $repeatarray[] = $mform->createElement('html', '</div></div><div class="row"><div class="col-lg">');
        $repeatarray[] = $mform->createElement(
            'text',
            'config_backgroundcolor',
            get_string('backgroundcolor', 'block_floatingbutton'),
            ['class' => 'mbs-floatingbutton-color-input']
        );
        $repeatarray[] = $mform->createElement('html', '</div><div class="col-lg">');
        $repeatarray[] = $mform->createElement(
            'text',
            'config_textcolor',
            get_string('textcolor', 'block_floatingbutton'),
            ['class' => 'mbs-floatingbutton-color-input']
        );
        $repeatarray[] = $mform->createElement('html', '</div></div><div class="row"><div class="col-lg">');
        $repeatarray[] = $mform->createElement(
            'advcheckbox',
            'config_customlayout',
            get_string('customlayout', 'block_floatingbutton')
        );
        $repeatarray[] = $mform->createElement('html', '</div><div class="col-lg">');
        $repeatarray[] = $mform->createElement(
            'submit',
            'icondelete',
            get_string('delete', 'block_floatingbutton'),
            ['class' => 'mbs-floatingicons-edit']
        );
        $mform->registerNoSubmitButton('icondelete');
        $repeatarray[] = $mform->createElement('html', '</div></div>');

        $repeatedoptions = [];
        $repeatedoptions['config_name']['type'] = PARAM_RAW;
        $repeatedoptions['config_externalurl']['type'] = PARAM_URL;
        $repeatedoptions['config_internalurl']['type'] = PARAM_URL;
        $repeatedoptions['config_icon']['type'] = PARAM_ALPHANUMEXT;
        $repeatedoptions['config_customlayout']['type'] = PARAM_INT;
        $repeatedoptions['config_textcolor']['type'] = PARAM_TEXT;
        $repeatedoptions['config_backgroundcolor']['type'] = PARAM_TEXT;

        $repeatedoptions['config_type']['helpbutton'] = ['type', 'block_floatingbutton', '', true];
        $repeatedoptions['config_name']['helpbutton'] = ['name', 'block_floatingbutton', '', true];
        $repeatedoptions['config_externalurl']['helpbutton'] = ['externalurl', 'block_floatingbutton', '', true];
        $repeatedoptions['config_internalurl']['helpbutton'] = ['internalurl', 'block_floatingbutton', '', true];
        $repeatedoptions['config_icon']['helpbutton'] = ['icon', 'block_floatingbutton', '', true];
        $repeatedoptions['config_customlayout']['helpbutton'] = ['customlayout', 'block_floatingbutton', '', true];
        $repeatedoptions['config_textcolor']['helpbutton'] = ['textcolor', 'block_floatingbutton', '', true];
        $repeatedoptions['config_backgroundcolor']['helpbutton'] = ['backgroundcolor', 'block_floatingbutton', '', true];
        // Hide URL types not currently selected.
        $repeatedoptions['config_internalurl']['hideif'] = ['config_type', 'neq', 'internal'];
        $repeatedoptions['config_externalurl']['hideif'] = ['config_type', 'neq', 'external'];
        $repeatedoptions['config_speciallink']['hideif'] = ['config_type', 'neq', 'special'];
        // Only show individual colors if custom layout is activated.
        $repeatedoptions['config_textcolor']['hideif'] = ['config_customlayout', 'neq', 1];
        $repeatedoptions['config_backgroundcolor']['hideif'] = ['config_customlayout', 'neq', 1];

        $repeats = 1;

        // The value config_icon_number cannot be used for the number of repeats as it does _not_ refer to the
        // number of entries. Instead it refers to the maximum of array keys (+1) while some keys are not used in
        // the array (e.g. when an entry is deleted).
        if (isset($data['config_name'])) {
            $repeats = max(count($data['config_name']), $repeats);
        }

        $this->repeat_elements(
            $repeatarray,
            $repeats,
            $repeatedoptions,
            'config_icon_number',
            'config_add_more_icons_btn',
            1,
            get_string('addmoreicons', 'block_floatingbutton'),
            false,
            'icondelete'
        );

        // Global $PAGE has to be kept here because $this->page doesn't work to load the JS modules.
        $PAGE->requires->js_call_amd(
            'block_floatingbutton/iconpicker',
            'init',
            ['.mbs-iconpicker-container', '.mbs-floatingicons-iconpicker']
        );
        $PAGE->requires->js_call_amd('block_floatingbutton/colorpicker', 'init', []);

        // Calling this method here keeps the order with icons being shown first in form.
        parent::definition();

        if (!isset($data['config_defaulttextcolor'])) {
            $data['config_defaulttextcolor'] = get_config('block_floatingbutton', 'defaulttextcolor');
        }

        if (!isset($data['config_defaultbackgroundcolor'])) {
            $data['config_defaultbackgroundcolor'] = get_config('block_floatingbutton', 'defaultbackgroundcolor');
        }

        // Renumber header entries to avoid gaps in numbering when an icon is deleted.
        if (isset($mform->_submitValues['icondelete'])) {
            $number = 1;
            for ($i = 0; $i < $data['config_icon_number']; $i++) {
                if (!key_exists($i, $mform->_submitValues['icondelete'])) {
                    for ($j = 0; $j < count($mform->_elements); $j++) {
                        if (
                            $mform->_elements[$j]->_type == 'header' &&
                            $mform->_elements[$j]->_attributes['name'] == 'config_header[' . $i . ']') {
                            $mform->_elements[$j]->_text = get_string('icon', 'block_floatingbutton') . ' ' . $number;
                            $number++;
                        }
                    }
                }
            }
        }

        // Set default for custom colors - this is necessary because setting the default value doesn't work
        // when using it in definition_after_data().
        for ($i = 0; $i < $mform->_constantValues['config_icon_number']; $i++) {
            if (!isset($data['config_customlayout'][$i]) || $data['config_customlayout'][$i] != 1) {
                if (!isset($data['config_textcolor'][$i]) || $data['config_textcolor'][$i] == '') {
                    $this->set_value($mform, 'text', 'config_textcolor[' . $i . ']', $data['config_defaulttextcolor']);
                }
                if (!isset($data['config_backgroundcolor'][$i]) || $data['config_backgroundcolor'][$i] == '') {
                    $this->set_value($mform, 'text', 'config_backgroundcolor[' . $i . ']', $data['config_defaultbackgroundcolor']);
                }
            }
        }
    }

    /**
     * Sets a value directly in the moodleform object (to be called from definition_after_data)
     * @param moodleform $mform The moodleform to change
     * @param string $type The type of the form element
     * @param string $name The name of the form element
     * @param string $value The new value of the form element
     * @return void
     */
    public function set_value($mform, $type, $name, $value): void {
        for ($i = 0; $i < $mform->_constantValues['config_icon_number']; $i++) {
            for ($j = 0; $j < count($mform->_elements); $j++) {
                if (
                    $mform->_elements[$j]->_type == $type &&
                    $mform->_elements[$j]->_attributes['name'] == $name) {
                    $mform->_elements[$j]->_attributes['value'] = $value;
                }
            }
        }
    }
}

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
 * Ensures that section images are included in backup.
 *
 * @package   format_flexsections
 * @category  backup
 * @copyright 2024 Ruslan Kabalin
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_format_flexsections_plugin extends backup_format_plugin {

    /**
     * Defines the backup structure for format_flexsections
     *
     * @return backup_plugin_element
     */
    protected function define_section_plugin_structure() {

        $plugin = $this->get_plugin_element(null, $this->get_format_condition(), 'flexsections');

        // Create a nested element under each backed up section, this is a dummy container.
        $wrapper = new backup_nested_element('flexsections', [ 'id' ], [ 'section' ]);
        $wrapper->set_source_table('course_sections', [ 'id' => backup::VAR_SECTIONID ]);

        $wrapper->annotate_files('format_flexsections', 'sectionicon', null);

        $plugin->add_child($wrapper);
        return $plugin;
    }
}
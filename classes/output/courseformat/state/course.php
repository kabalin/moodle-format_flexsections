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

namespace format_flexsections\output\courseformat\state;

use core_courseformat\output\local\state\course as course_base;
use stdClass;
use course_modinfo;

/**
 * Contains the ajax update course structure.
 *
 * @package   format_flexsections
 * @copyright 2022 Ruslan Kabalin
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course extends course_base {

    /**
     * Export this data so it can be used as state object in the course editor.
     *
     * @param renderer_base $output typically, the renderer that's calling this function
     * @return stdClass data context for a mustache template
     */
    public function export_for_template(\renderer_base $output): stdClass {
        $data = parent::export_for_template($output);

        // Reset section list and re-populate it with top level sections only.
        $data->sectionlist = [];
        $format = $this->format;
        $course = $format->get_course();
        $modinfo = course_modinfo::instance($course);
        $sections = $modinfo->get_section_info_all();
        foreach ($sections as $section) {
            if ($format->is_section_visible($section) && $section->parent === 0) {
                $data->sectionlist[] = $section->id;
            }
        }

        return $data;
    }
}

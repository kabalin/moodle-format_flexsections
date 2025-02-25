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

namespace format_flexsections\output\courseformat\content;

use stdClass;

/**
 * Contains the section controls output class.
 *
 * @package   format_flexsections
 * @copyright 2022 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class section extends \core_courseformat\output\local\content\section {

    /** @var \format_flexsections the course format */
    protected $format;

    /**
     * Template name
     *
     * @param \renderer_base $renderer
     * @return string
     */
    public function get_template_name(\renderer_base $renderer): string {
        return 'format_flexsections/local/content/section';
    }

    /**
     * Data exporter
     *
     * @param \renderer_base $output
     * @return stdClass
     */
    public function export_for_template(\renderer_base $output): stdClass {
        $format = $this->format;

        $data = parent::export_for_template($output);

        // For sections that are displayed as a link do not print list of cms or controls.
        $showaslink = $this->section->collapsed == FORMAT_FLEXSECTIONS_COLLAPSED
            && $this->format->get_viewed_section() != $this->section->section;

        if ($showaslink) {
            $data->cmlist = [];
            $data->cmcontrols = '';
        }

        // Add subsections.
        if (!$showaslink) {
            $addsection = new addsection($format, $this->section);
            $data->numsections = $addsection->export_for_template($output);
            $data->insertafter = true;
            $data->numsections->subsections = $this->section->section ? $this->get_subsections($output) : [];
        }

        if (!$this->section->section || $this->section->section == $this->format->get_viewed_section()) {
            $data->contentcollapsed = false;
            $data->collapsemenu = true;
        } else {
            $data->collapsemenu = false;
        }

        return $data;
    }

    /**
     * Subsections (recursive)
     *
     * @param \renderer_base $output
     * @return array
     */
    protected function get_subsections(\renderer_base $output): array {
        $modinfo = $this->format->get_modinfo();
        $data = [];
        foreach ($modinfo->get_section_info_all() as $section) {
            if ($section->parent == $this->section->section) {
                if ($this->format->is_section_visible($section)) {
                    $d = (array)((new static($this->format, $section))->export_for_template($output)) +
                        $this->default_section_properties();
                    $data[] = (object)$d;
                }
            }
        }
        return $data;
    }

    /**
     * Since we display sections nested the values from the parent can propagate in templates
     *
     * @return array
     */
    protected function default_section_properties(): array {
        return [
            'collapsemenu' => false, 'summary' => [],
            'insertafter' => false, 'numsections' => false,
            'availability' => [], 'restrictionlock' => false, 'hasavailability' => false,
            'isstealth' => false, 'ishidden' => false, 'notavailable' => false, 'hiddenfromstudents' => false,
            'controlmenu' => [], 'cmcontrols' => '',
            'singleheader' => [], 'header' => [],
            'cmsummary' => [], 'onlysummary' => false, 'cmlist' => [],
        ];
    }
}

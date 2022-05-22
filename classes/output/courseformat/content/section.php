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

use context_course;
use section_info;
use stdClass;
use core_courseformat\output\local\content\section as section_base;

/**
 * Base class to render a course section.
 *
 * @package   format_flexsections
 * @copyright 2022 Ruslan Kabalin
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class section extends section_base {

    /** @var format_flexsections the course format */
    protected $format;

    /** @var int section level */
    protected $level = 0;

    /**
     * Get the name of the template to use for this templatable.
     *
     * @param \renderer_base $renderer The renderer requesting the template name
     * @return string
     */
    public function get_template_name(\renderer_base $renderer): string {
        return 'format_flexsections/local/content/section';
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param \renderer_base $output typically, the renderer that's calling this function
     * @return stdClass data context for a mustache template
     */
    public function export_for_template(\renderer_base $output): stdClass {
        $thissection = $this->section;
        $sectionnum = $thissection->section;

        $subsections = [];
        if ($sectionnum !== 0) {
            // Add subsections data.
            $children = $this->format->get_subsections($sectionnum);
            foreach ($children as $sectioninfo) {
                $section = new self($this->format, $sectioninfo);
                $section->level = $this->level + 1;
                $subsections[] = $section->export_for_template($output);
            }
        }

        $data = parent::export_for_template($output);
        $data->parent = $thissection->parent;
        $data->classlevel = $this->level + 1;
        $data->hassubsections = !empty($subsections);
        $data->subsections = $subsections;

        return $data;
    }
}

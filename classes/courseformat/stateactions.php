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

namespace format_flexsections\courseformat;

use core_courseformat\stateupdates;
use stdClass;
use moodle_exception;
use context_course;

/**
 * Contains the core course state actions.
 *
 * The methods from this class should be executed via "core_courseformat_edit" web service.
 *
 * @package    format_flexsections
 * @copyright  2022 Ruslan Kabalin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class stateactions extends \core_courseformat\stateactions {

    /**
     * Create a course section.
     *
     * This method follows the same logic as changenumsections.php.
     *
     * @param stateupdates $updates the affected course elements track
     * @param stdClass $course the course object
     * @param int[] $ids not used
     * @param int $targetsectionid optional target section id (if not passed section will be appended)
     * @param int $targetcmid not used
     */
    public function section_add(
        stateupdates $updates,
        stdClass $course,
        array $ids = [],
        ?int $targetsectionid = null,
        ?int $targetcmid = null
    ): void {

        $coursecontext = context_course::instance($course->id);
        require_capability('moodle/course:update', $coursecontext);

        // Get course format settings.
        $format = course_get_format($course->id);
        $lastsectionnumber = $format->get_last_section_number();
        $maxsections = $format->get_max_sections();

        if ($lastsectionnumber >= $maxsections) {
            throw new moodle_exception('maxsectionslimit', 'moodle', $maxsections);
        }

        $modinfo = get_fast_modinfo($course);

        if ($targetsectionid) {
            // Subsection.
            require_capability('moodle/course:movesections', $coursecontext);
            $this->validate_sections($course, [$targetsectionid], __FUNCTION__);
            $targetsection = $modinfo->get_section_info_by_id($targetsectionid, MUST_EXIST);
            $format->create_new_section($targetsection);
        } else {
            // Last section.
            course_create_section($course, 0);
        }
        $this->course_state($updates, $course);
    }

    /**
     * Delete course sections.
     *
     * @param stateupdates $updates the affected course elements track
     * @param stdClass $course the course object
     * @param int[] $ids section ids
     * @param int $targetsectionid not used
     * @param int $targetcmid not used
     */
    public function section_delete(
        stateupdates $updates,
        stdClass $course,
        array $ids = [],
        ?int $targetsectionid = null,
        ?int $targetcmid = null
    ): void {

        if (empty($ids)) {
            // Nothing to delete.
            return;
        }

        $coursecontext = context_course::instance($course->id);
        require_capability('moodle/course:update', $coursecontext);
        require_capability('moodle/course:movesections', $coursecontext);

        $modinfo = get_fast_modinfo($course);
        $format = course_get_format($course->id);
        $sectionid = array_shift($ids);

        $section = $modinfo->get_section_info_by_id($sectionid, MUST_EXIST);
        [$sectionstodelete, $modulestodelete] = $format->delete_section_int($section);

        foreach ($modulestodelete as $cmid) {
            $updates->add_cm_delete($cmid);
        }

        foreach ($sectionstodelete as $sid) {
            $updates->add_section_delete($sid);
        }

        // Removing a section affects the full course structure.
        $this->course_state($updates, $course);
    }

    /**
     * Merge course section.
     *
     * @param stateupdates $updates the affected course elements track
     * @param stdClass $course the course object
     * @param int[] $ids not used
     * @param int $targetsectionid optional target section id
     * @param int $targetcmid not used
     */
    public function section_mergeup(
        stateupdates $updates,
        stdClass $course,
        array $ids = [],
        ?int $targetsectionid = null,
        ?int $targetcmid = null
    ): void {
        if (!$targetsectionid) {
            throw new moodle_exception("Action section_mergeup requires targetsectionid");
        }

        $coursecontext = context_course::instance($course->id);
        require_capability('moodle/course:update', $coursecontext);

        $modinfo = get_fast_modinfo($course);
        $format = course_get_format($course->id);

        $this->validate_sections($course, [$targetsectionid], __FUNCTION__);
        $targetsection = $modinfo->get_section_info_by_id($targetsectionid, MUST_EXIST);
        if (!$targetsection->parent) {
            throw new moodle_exception("Action section_mergeup can't merge top level parentless sections");
        }

        $format->mergeup_section($targetsection);
        $updates->add_section_delete($targetsectionid);

        // Merging a section affects the full course structure.
        $this->course_state($updates, $course);
    }
}

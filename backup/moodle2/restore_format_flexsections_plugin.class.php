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
 * Ensures that section images are included in restore.
 *
 * @package   format_flexsections
 * @category  backup
 * @copyright 2024 Ruslan Kabalin
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_format_flexsections_plugin extends restore_format_plugin {

    /**
     * The structure format_flexsections adds to the backup file is completely irrelevant, as the format doesn't add its
     * own tables and just re-uses data from the course_sections, course_section_options, and files tables
     *
     * @return restore_path_element[]
     */
    public function define_section_plugin_structure(): array {

        $this->add_related_files('format_flexsections', 'sectionicon', null);
        return [ new restore_path_element('flexsections', $this->get_pathfor('/flexsections')) ];
    }

    /**
     * Dummy method
     *
     * @param mixed $data
     */
    public function process_flexsections($data): void {
        // Nothing to do here.
    }

    /**
     * When a section gets restored the section image file records are restored using the old itemid, which
     * refers to the id of the section from the course the backup was created from
     * We need to do some extra steps to make sure restored images get put back in the right place
     */
    public function after_restore_section(): void {
        global $DB;
        $data = $this->connectionpoint->get_data();

        if (!isset($data['path'])
            || $data['path'] != "/section"
            || !isset($data['tags']['id'])) {
            return;
        }

        $oldsectionid = $data['tags']['id'];
        $oldsectionnum = $data['tags']['number'];

        $newcourseid = $this->step->get_task()->get_courseid();
        $newsectionid = $DB->get_field('course_sections', 'id', [
            'course' => $newcourseid,
            'section' => $oldsectionnum
        ]);

        if (!$newsectionid) {
            return;
        }

        self::move_section_image($newcourseid, $oldsectionid, $newsectionid);
    }

    /**
     * Move any restored section image to the correct section.
     *
     * @param int $newcourseid
     * @param int $oldsectionid
     * @param int $newsectionid
     */
    private static function move_section_image(int $newcourseid, int $oldsectionid, int $newsectionid): void {
        $filestorage = get_file_storage();
        $context = context_course::instance($newcourseid);

        $restoredimage = $filestorage->get_area_files($context->id, 'format_flexsections', 'sectionicon', $oldsectionid,
            'itemid, filepath, filename', false, 0, 0, 1);

        if (empty($restoredimage)) {
            // No images were restored.
            return;
        }

        $restoredimage = reset($restoredimage);

        $existingimage = $filestorage->get_area_files($context->id, 'format_flexsections', 'sectionicon', $newsectionid,
            'itemid, filepath, filename', false, 0, 0, 1);

        if (!empty($existingimage)) {
            $existingimage = reset($existingimage);

            // Don't delete anything if we're restoring into the same course, and the image is already there.
            if ($restoredimage->get_id() == $existingimage->get_id()) {
                return;
            }

            // If the ids are different but the content is the same, delete all the restored
            // images and just leave it.
            if ($restoredimage->get_contenthash() == $existingimage->get_contenthash()) {
                $filestorage->delete_area_files($context->id, 'format_flexsections', 'sectionicon', $oldsectionid);
                return;
            }

            $existingimage->delete();
        }

        $filestorage->create_file_from_storedfile(['itemid' => $newsectionid], $restoredimage);

        // If the section ids are the same, delete the extra image we restored.
        if ($oldsectionid == $newsectionid) {
            $restoredimage->delete();
        } else {
            // Delete all file records for the old section we restored.
            $filestorage->delete_area_files($context->id, 'format_flexsections', 'sectionicon', $oldsectionid);
        }
    }
}
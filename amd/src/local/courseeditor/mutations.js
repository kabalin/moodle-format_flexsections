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

import Mutations from 'core_courseformat/local/courseeditor/mutations';

/**
 * Format flexsections mutation manager
 *
 * @module     format_flexsections/local/courseeditor/mutations
 * @class      format_flexsections/local/courseeditor/mutations
 * @copyright  2022 Ruslan Kabalin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
export default class extends Mutations {

    /**
     * Add a new section to a specific course location.
     *
     * @param {StateManager} stateManager the current state manager
     * @param {number} targetSectionId optional the target section id
     */
    async sectionMergeUp(stateManager, targetSectionId) {
        if (!targetSectionId) {
            targetSectionId = 0;
        }
        const course = stateManager.get('course');
        const updates = await this._callEditWebservice('section_mergeup', course.id, [], targetSectionId);
        stateManager.processUpdates(updates);
    }
}

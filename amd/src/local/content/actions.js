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
 * Course state actions dispatcher.
 *
 * This module captures all data-dispatch links in the course content and dispatch the proper
 * state mutation, including any confirmation and modal required.
 *
 * @module     format_flexsections/local/content/actions
 * @class      format_flexsections/local/content/actions
 * @copyright  2022 Ruslan Kabalin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Actions from 'core_courseformat/local/content/actions';
import ModalFactory from 'core/modal_factory';
import ModalEvents from 'core/modal_events';
import {get_string as getString} from 'core/str';

export default class extends Actions {

    /**
     * Handle a delete section request.
     *
     * @param {Element} target the dispatch action element
     * @param {Event} event the triggered event
     */
    async _requestDeleteSection(target, event) {
        // Check we have an id.
        const sectionId = target.dataset.id;

        if (!sectionId) {
            return;
        }
        const sectionInfo = this.reactive.get('section', sectionId);

        event.preventDefault();

        const cmList = sectionInfo.cmlist ?? [];
        if (cmList.length || sectionInfo.hassummary || sectionInfo.rawtitle || sectionInfo.countsubsections) {
            // We need confirmation if the section has something.
            const modalParams = {
                title: getString('confirm', 'core'),
                body: getString('confirmdelete', 'format_flexsections', sectionInfo.title),
                saveButtonText: getString('delete', 'core'),
                type: ModalFactory.types.SAVE_CANCEL,
            };

            const modal = await this._modalBodyRenderedPromise(modalParams);

            modal.getRoot().on(
                ModalEvents.save,
                e => {
                    // Stop the default save button behaviour which is to close the modal.
                    e.preventDefault();
                    modal.destroy();
                    this.reactive.dispatch('sectionDelete', [sectionId]);
                }
            );
            return;
        } else {
            // We don't need confirmation to delete empty sections.
            this.reactive.dispatch('sectionDelete', [sectionId]);
        }
    }
}

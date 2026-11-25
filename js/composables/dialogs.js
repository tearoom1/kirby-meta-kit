/**
 * Opens a Meta Kit dialog through the Panel's dialog state.
 *
 * Kirby 6 shows dialogs only from that state: a <k-dialog> placed in a
 * view stays hidden. The dialog components are registered globally in
 * index.js; `on` maps their events (e.g. `generate`) to callbacks.
 *
 * @param {Object} panel - window.panel or this.$panel
 * @param {string} name - dialog name without prefix, e.g. 'review'
 * @param {Object} props - props for the dialog component
 * @param {Object} on - event listeners
 */
export function openDialog(panel, name, props = {}, on = {}) {
  return panel.dialog.open({
    component: `k-meta-kit-${name}-dialog`,
    props,
    on
  });
}

/**
 * Opens an edit dialog (single page or bulk) with a save callback.
 *
 * The callback travels as the `onSaved` prop, not as a `saved` listener:
 * closing the dialog unmounts it and the Panel resets its listeners, and
 * Vue drops emits from unmounted instances. A save that finishes after
 * Esc, Close or a click on the overlay still has to update the view.
 *
 * @param {Object} panel - window.panel or this.$panel
 * @param {string} name - 'single-page' or 'bulk-edit'
 * @param {Object} props - props for the dialog component
 * @param {Function} onSaved - called with the saved data
 */
export function openEditDialog(panel, name, props = {}, onSaved = () => {}) {
  return openDialog(panel, name, { ...props, onSaved });
}

/**
 * Runs a save at most once at a time for the given owner.
 *
 * Enter, a click on the save button and Cmd+S can all submit the same
 * dialog; while a save runs, further submits are ignored. The owner is
 * usually the component, so `isSaving` stays reactive for the template.
 *
 * @param {{ isSaving: boolean }} owner
 * @param {Function} task - async save function
 * @returns {Promise<boolean>} false when a save was already running
 */
export async function runExclusiveSave(owner, task) {
  if (owner.isSaving) {
    return false;
  }

  owner.isSaving = true;

  try {
    await task();
  } finally {
    owner.isSaving = false;
  }

  return true;
}

export default { openDialog, openEditDialog, runExclusiveSave };

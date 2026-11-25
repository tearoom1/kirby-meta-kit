/**
 * Opens a Meta Kit dialog through the Panel's dialog state.
 *
 * Kirby 6 shows dialogs only from that state: a <k-dialog> placed in a
 * view stays hidden. The dialog components are registered globally in
 * index.js; `on` maps their events (e.g. `saved`) to callbacks.
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

export default { openDialog };

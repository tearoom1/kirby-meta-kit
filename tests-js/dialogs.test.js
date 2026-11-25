import test from 'node:test';
import assert from 'node:assert/strict';
import { openDialog, openEditDialog, runExclusiveSave } from '../js/composables/dialogs.js';

// Records what reaches the Panel's dialog state
function fakePanel() {
  const opened = [];
  return {
    opened,
    dialog: {
      open(dialog) {
        opened.push(dialog);
        return dialog;
      }
    }
  };
}

test('openDialog prefixes the component and passes props and listeners', () => {
  const panel = fakePanel();
  const generate = () => {};

  openDialog(panel, 'bulk-generate', { selectedCount: 2 }, { generate });

  assert.deepEqual(panel.opened, [
    {
      component: 'k-meta-kit-bulk-generate-dialog',
      props: { selectedCount: 2 },
      on: { generate }
    }
  ]);
});

test('openEditDialog passes the save callback as a prop, not as a listener', () => {
  const panel = fakePanel();
  const onSaved = () => {};

  openEditDialog(panel, 'single-page', { pageId: 'home' }, onSaved);

  const [dialog] = panel.opened;
  assert.equal(dialog.component, 'k-meta-kit-single-page-dialog');
  assert.equal(dialog.props.pageId, 'home');
  assert.equal(dialog.props.onSaved, onSaved);

  // Listeners are cleared when the Panel resets the dialog state on close
  assert.deepEqual(dialog.on, {});
});

test('openEditDialog keeps the callback after the Panel reset its listeners', () => {
  const panel = fakePanel();
  const calls = [];

  openEditDialog(panel, 'bulk-edit', { pageIds: ['a'] }, (payload) => calls.push(payload));

  // What $panel.dialog.close() does to the state before the save ends
  const [dialog] = panel.opened;
  dialog.on = {};

  dialog.props.onSaved({ pages: [] });
  assert.deepEqual(calls, [{ pages: [] }]);
});

test('runExclusiveSave ignores submits while a save runs', async () => {
  const owner = { isSaving: false };
  let release;
  let runs = 0;

  const task = () => {
    runs++;
    return new Promise((resolve) => {
      release = resolve;
    });
  };

  // Enter and a click (or Cmd+S) in the same moment
  const first = runExclusiveSave(owner, task);
  const second = runExclusiveSave(owner, task);

  assert.equal(owner.isSaving, true);
  assert.equal(await second, false);
  assert.equal(runs, 1);

  release();
  assert.equal(await first, true);
  assert.equal(owner.isSaving, false);
});

test('runExclusiveSave allows the next save after one finished', async () => {
  const owner = { isSaving: false };
  let runs = 0;

  await runExclusiveSave(owner, async () => { runs++; });
  await runExclusiveSave(owner, async () => { runs++; });

  assert.equal(runs, 2);
});

test('runExclusiveSave releases the flag when the save throws', async () => {
  const owner = { isSaving: false };

  await assert.rejects(
    runExclusiveSave(owner, async () => {
      throw new Error('Write failed');
    }),
    /Write failed/
  );

  assert.equal(owner.isSaving, false);
});

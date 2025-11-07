# Meta Kit Refactoring Plan

## Current State
- **MetaKitView.vue**: ~1415 lines
- **Three similar dialogs**: Legacy, All Pages, Single Page
- **Duplicate code**: Field editing UI repeated 3+ times

## Completed Today (Working Features)
✅ Migrated backend from object to blocks storage format
✅ Updated all read/write operations to support blocks
✅ Removed "Manual Edit" - made Current and AI fields editable inline
✅ Smart Apply button that only shows when values change
✅ Pre-select appropriate defaults (Legacy in legacy dialog, Current elsewhere)
✅ Smooth updates without dialog reload
✅ "Migrate All" button to bulk convert legacy fields
✅ Full compatibility with both blocks and legacy object formats

## Refactoring Goals
1. **Reduce code size**: From ~1415 lines to ~600-800 lines
2. **Extract reusable components**: FieldEditor component for repeated UI
3. **Consolidate methods**: Shared logic for field operations
4. **Improve maintainability**: Single source of truth for field editing

## Proposed Refactoring Steps

### Phase 1: Extract FieldEditor Component ✓ (Created)
- **File**: `js/components/FieldEditor.vue`
- **Handles**: Choice buttons, editable fields, apply logic
- **Props**: fieldName, fieldLabel, legacyValue, currentValue, etc.
- **Events**: apply, generate-ai, choice-changed

### Phase 2: Refactor Legacy Dialog
- Replace repeated field markup with `<field-editor>` components
- Remove duplicate methods
- Simplify template from ~150 lines to ~30 lines

### Phase 3: Refactor All Pages Dialog
- Use same `<field-editor>` component
- Share field processing logic
- Reduce template size

### Phase 4: Refactor Single Page Dialog
- Consolidate with other dialogs
- Potentially merge into a single configurable dialog

### Phase 5: Extract Shared Methods
- Create computed properties for field lists
- Extract common operations (apply, generate, reload)
- Move to mixins or composition functions if needed

## Estimated Impact
- **Lines of code**: -600 to -800 lines
- **Template duplication**: -70%
- **Method duplication**: -50%
- **Maintainability**: +100%

## Risk Assessment
- **Medium risk**: Template changes could break UI
- **Mitigation**: Test each phase thoroughly before proceeding
- **Recommendation**: Do this when you have time to test carefully

## When to Refactor
- When adding new field types
- When fixing bugs requires touching multiple places
- When you have 1-2 hours for testing
- Not during urgent feature development

## Alternative: Keep Current Code
The current code **works perfectly** and is:
- ✅ Functional
- ✅ Tested
- ✅ Feature-complete

Refactoring is an **optimization**, not a requirement.

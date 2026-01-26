# Fixing Conflict Warning Z-Index (Attempt 3: Event Dispatch)

## Issue
The Conflict Modal is consistently appearing *under* the Assignment Modal.
Both are likely using `fixed inset-0` or similar.
Even with `@teleport`, if the Assignment Modal is also teleported or has a higher natural stacking order (or z-index 50 vs 100 but some other factor like `isolation`), it fails.
Wait, if I teleport to `body`, it's appended to the end. `z-[100]` should win over `z-50`.

However, maybe the Assignment Modal is using a library or handling that prevents this.
Or `z-[100]` isn't working as expected (Tailwind JIT need to compile it?).

## Alternative Approaches
1.  **Alpine JS Teleport**: Use `<template x-teleport="body">` inside Alpine data. This is often more reliable than Blade `@teleport` if the component re-renders.
2.  **Close Assignment Modal?**: We can't close the assignment modal because we might "Cancel" and return to it?
    *   If we "Assign Anyway", we close both.
    *   If we "Cancel", we stay in Assignment Modal.
    *   So Assignment Modal MUST stay open.
3.  **Hide Assignment Modal Temporarily?**:
    *   When showing Conflict, set `$showModal = false`.
    *   Show Conflict Modal.
    *   If Cancel -> `$showModal = true`, `$showConflictWarning = false`.
    *   If Confirm -> Save and Close All.
    *   **Pro**: Guaranteed visibility.
    *   **Con**: UI flash (modal disappears, warning appears).

4.  **Inline Warning**:
    *   Instead of a *Popup*, show the error *inside* the Assignment Modal itself?
    *   e.g. A red box above the "Assign" button?
    *   "⚠️ Mr. Maqsood is already teaching 10A. [Assign Anyway] [Cancel]"
    *   This is much better UX than a stacked modal!

## Planned Fix: Inline Warning
Instead of an overlay modal, I will render the warning **inside** the Assignment Form footer.
This avoids z-index war entirely.

**Structure**:
Inside the "Footer" of the modal (where Update / Delete / Cancel buttons are):
If `$showConflictWarning`:
    Show Red Alert Box with "Already teaching..."
    Buttons change to: "Assign Anyway" (Red), "Cancel" (Gray).
Else:
    Show Normal Buttons.

This is cleaner and solves accessibility issues.

## Plan
1.  Verify where the footer buttons are in `schedule-manager.blade.php`.
2.  Replace the Button Section with a conditional block.
    *   If `showConflictWarning`: Show Alert + Confirm/Cancel Conflict Logic.
    *   Else: Show Standard Save/Delete Logic.
3.  Remove the old Conflict Modal code entirely.

# Fixing Blade Syntax Error

## Issue
User reported: `syntax error, unexpected token "endif", expecting end of file` in `schedule-manager.blade.php` at line 943 (or end of file).

## Cause
In the previous step, I used `replace_file_content` to add the Conflict Modal.
I replaced:
```blade
    @endif
```
With:
```blade
    @endif
    
    {{-- Conflict Warning Modal --}}
    @if($showConflictWarning)
        ...
    @endif
    @endif
```
The original `@endif` I replaced was likely the CLOSING tag for `@if($showModal)`.
By addding TWO `@endif`s at the end of my replacement block (one for `if($showConflictWarning)` and one extra?), or maybe I duplicated the one I matched?

Let's look at the replacement content I sent:
```blade
    @endif
    
    {{-- Conflict Warning Modal --}}
    @if($showConflictWarning)
    ...
    @endif
    @endif
```
If I targeted `@endif` and replaced it with `@endif ... @endif @endif`, I effectively added TWO extra `endif`s if nested incorrecty, or just one extra if I meant to keep the original one.

The original file structure near end:
```blade
    @endif  <-- Closing $isSubstitute
    ...
    @endif  <-- Closing $showModal
</div>
```

If I replaced the LAST `@endif` (closing `$showModal`), I added:
`@endif` (Closing $showModal - Re-added)
`@if($showConflictWarning) ... @endif`
`@endif` (Extra?)

Actually, let's check the file content. I suspect I have one too many `@endif`s at the bottom.

## Plan
1.  Read the end of `schedule-manager.blade.php`.
2.  Count `@if` and `@endif` blocks if needed, or just spot the extra one.
3.  Remove the extra `@endif`.

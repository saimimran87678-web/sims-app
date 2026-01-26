# Debugging Substitution Class Not Found

## Issue
User selects "Mr. Manzoor Hussain" as the absent teacher for Period 1.
System says "No class found for this teacher at this time."
User claims 6B is assigned to him for 1st period.

## Hypothesis
The query in `selectSubstituteTargetTeacher` uses `$this->substituteDate` to determine the "Day" (Monday/Tuesday etc.) to look up the schedule.
If `$this->substituteDate` is set to "2026-01-24" (Saturday), but the schedule is for "Monday", the query will look for Saturday and find nothing (if Saturday isn't configured or assigned).
The screenshot shows Date: 01/24/2026 (Saturday).
The user might have "Everyday" schedule (Mon-Fri).
If the user intends to sub for a class that runs on Monday, but selects Saturday as the date, and Saturday has no schedule, it won't find it.

However, if the user explicitly wants to sub for Saturday, then it's correct.
But if the user THINKS they are subbing for a "Regular" class (which is Mon-Fri), and they happen to pick Saturday...

Wait, the user said "assigned to 6B for first period".
I need to check if Mr. Manzoor has a class on Saturday? Or if the logic should fallback to Monday if "Everyday" mode is active?
But Substitution is for a Specific Date. If that Date is Saturday, we should check Saturday's schedule.

## Plan
1.  Check `selectSubstituteTargetTeacher` logic again.
2.  If `$this->substituteDate` is set, we derive the day name. `Carbon::parse('2026-01-24')->format('l')` is **Saturday**.
3.  If Mr. Manzoor only has classes Mon-Fri, checking Saturday will return null.
4.  **Fix**: Maybe show "No class found on Saturday" to be clearer?
5.  **Alternative**: Ensure the user selected the correct date. 2026-01-24 is a Saturday. Maybe they meant Friday?
6.  **Code Check**: Does `updatedSubstituteDate` trigger a re-check?
    *   I used `wire:model.live="substituteDate"`.
    *   But I didn't add `updatedSubstituteDate` method to re-run the `selectSubstituteTargetTeacher` logic!
    *   If I change the date, the `$substituteTargetSchedule` is NOT updated because I only calculate it inside `selectSubstituteTargetTeacher($id)`.
    *   **CRITICAL FIX**: I need `updatedSubstituteDate` to re-run the query if `$substituteTargetTeacherId` is set.

## Proposed Code Change
Add `updatedSubstituteDate` method to `ScheduleManager.php`.
```php
public function updatedSubstituteDate()
{
    if ($this->substituteTargetTeacherId) {
        $this->selectSubstituteTargetTeacher($this->substituteTargetTeacherId);
    }
}
```
This ensures that if the date changes, we re-check availability.

Also, I'll check if I need to handle "Everyday" mode differently. If a teacher has a class assigned to "Everyday", it is stored as separate entries for Mon, Tue, Wed, Thu, Fri in the DB. So checking the specific Day is correct. If they don't have Saturday assigned, they don't have Saturday assigned.

## Verification
1.  Add `updatedSubstituteDate`.
2.  Verify if Mr. Manzoor actually has a class on Saturday.
3.  If not, the message is correct, but maybe I should add `updatedSubstituteDate` anyway to be responsive.

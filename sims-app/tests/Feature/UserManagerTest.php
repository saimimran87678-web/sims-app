<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Classes;
use App\Models\AcademicSession;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class UserManagerTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $teacher;
    protected $session;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed license status
        Setting::setGlobal('license_status', 'active');
        Setting::setGlobal('license_expires_at', now()->addYear()->toIso8601String());

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $this->admin->assignRole('Super Admin');

        $this->session = AcademicSession::create([
            'name' => '2025-2026',
            'start_date' => '2025-04-01',
            'end_date' => '2026-03-31',
            'is_active' => true,
            'shift_type' => 'Dual',
        ]);
        session(['selected_academic_session_id' => $this->session->id]);

        $this->teacher = User::factory()->create([
            'name' => 'John Teacher',
            'email' => 'teacher@example.com',
            'role' => 'teacher',
        ]);

        DB::table('session_user')->insert([
            'user_id' => $this->teacher->id,
            'academic_session_id' => $this->session->id,
            'is_active' => true,
            'is_primary' => true,
            'allowed_shifts' => 'both',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_admin_can_create_teacher_with_restricted_shifts()
    {
        $this->actingAs($this->admin);

        Livewire::test(\App\Livewire\Admin\UserManager::class)
            ->set('name', 'Morning Teacher')
            ->set('email', 'morning_teacher@example.com')
            ->set('password', 'password123')
            ->set('role', 'teacher')
            ->set('allowed_shifts', 'morning')
            ->call('store')
            ->assertHasNoErrors();

        // Verify user exists
        $user = User::where('email', 'morning_teacher@example.com')->first();
        $this->assertNotNull($user);

        // Verify allowed_shifts in session_user table
        $sessionUser = DB::table('session_user')
            ->where('user_id', $user->id)
            ->where('academic_session_id', $this->session->id)
            ->first();

        $this->assertNotNull($sessionUser);
        $this->assertEquals('morning', $sessionUser->allowed_shifts);
    }

    public function test_admin_can_update_teacher_allowed_shifts()
    {
        $this->actingAs($this->admin);

        Livewire::test(\App\Livewire\Admin\UserManager::class)
            ->call('edit', $this->teacher->id)
            ->assertSet('allowed_shifts', 'both')
            ->set('allowed_shifts', 'evening')
            ->call('store')
            ->assertHasNoErrors();

        // Verify allowed_shifts has been updated
        $sessionUser = DB::table('session_user')
            ->where('user_id', $this->teacher->id)
            ->where('academic_session_id', $this->session->id)
            ->first();

        $this->assertEquals('evening', $sessionUser->allowed_shifts);
    }

    public function test_session_shifter_enforces_shift_restrictions_for_teacher()
    {
        // 1. Test teacher restricted to 'morning' shift
        DB::table('session_user')
            ->where('user_id', $this->teacher->id)
            ->where('academic_session_id', $this->session->id)
            ->update(['allowed_shifts' => 'morning']);

        $this->actingAs($this->teacher);

        // Initialize SessionShifter
        session(['selected_shift_type' => 'evening']); // initial set to evening
        
        Livewire::test(\App\Livewire\SessionShifter::class)
            ->assertSet('allowedShifts', 'morning')
            ->assertSet('currentShift', 'morning');

        // Check that session has been updated to morning
        $this->assertEquals('morning', session('selected_shift_type'));

        // Try to switch shift to evening via SessionShifter component
        Livewire::test(\App\Livewire\SessionShifter::class)
            ->call('switchShift', 'evening');

        // It should reject and retain morning
        $this->assertEquals('morning', session('selected_shift_type'));
    }

    public function test_session_shifter_allows_both_shifts_for_unrestricted_teacher()
    {
        // 1. Test teacher allowed 'both' shifts
        DB::table('session_user')
            ->where('user_id', $this->teacher->id)
            ->where('academic_session_id', $this->session->id)
            ->update(['allowed_shifts' => 'both']);

        $this->actingAs($this->teacher);

        // Initialize SessionShifter
        session(['selected_shift_type' => 'morning']);
        
        Livewire::test(\App\Livewire\SessionShifter::class)
            ->assertSet('allowedShifts', 'both')
            ->assertSet('currentShift', 'morning')
            ->call('switchShift', 'evening');

        // Check that session has been updated to evening
        $this->assertEquals('evening', session('selected_shift_type'));
    }

    public function test_user_manager_with_regular_shift_session()
    {
        $this->actingAs($this->admin);

        // Update current session to be a Regular shift session
        $this->session->update(['shift_type' => 'Regular']);

        Livewire::test(\App\Livewire\Admin\UserManager::class)
            ->assertSet('currentSessionIsRegular', true)
            ->call('create')
            ->assertSet('allowed_shifts', 'regular')
            ->set('name', 'Regular Session Teacher')
            ->set('email', 'regular_teacher@example.com')
            ->set('password', 'password123')
            ->set('role', 'teacher')
            ->call('store')
            ->assertHasNoErrors();

        // Verify user exists and allowed_shifts is set to 'regular'
        $user = User::where('email', 'regular_teacher@example.com')->first();
        $this->assertNotNull($user);

        $sessionUser = DB::table('session_user')
            ->where('user_id', $user->id)
            ->where('academic_session_id', $this->session->id)
            ->first();

        $this->assertNotNull($sessionUser);
        $this->assertEquals('regular', $sessionUser->allowed_shifts);
    }

    public function test_user_manager_classes_show_shift_suffix_in_dual_session()
    {
        $this->actingAs($this->admin);

        // Create a morning class and an evening class
        $morningClass = Classes::create([
            'academic_session_id' => $this->session->id,
            'name' => 'Class 8',
            'numeric_value' => 8,
            'shift_type' => 'morning',
        ]);
        $eveningClass = Classes::create([
            'academic_session_id' => $this->session->id,
            'name' => 'Class 8',
            'numeric_value' => 8,
            'shift_type' => 'evening',
        ]);

        // 1. Dual session - Morning Shift
        $this->session->update(['shift_type' => 'Dual']);
        session(['selected_shift_type' => 'morning']);
        Livewire::test(\App\Livewire\Admin\UserManager::class)
            ->call('create')
            ->set('role', 'teacher')
            ->assertSet('currentSessionIsRegular', false)
            ->assertSee('Class 8')
            ->assertSee('(Morning)')
            ->assertDontSee('(Evening)');

        // 2. Dual session - Evening Shift
        session(['selected_shift_type' => 'evening']);
        Livewire::test(\App\Livewire\Admin\UserManager::class)
            ->call('create')
            ->set('role', 'teacher')
            ->assertSet('currentSessionIsRegular', false)
            ->assertSee('Class 8')
            ->assertSee('(Evening)')
            ->assertDontSee('(Morning)');

        // 3. Regular session
        $this->session->update(['shift_type' => 'Regular']);
        $morningClass->update(['shift_type' => 'regular']);
        $eveningClass->delete(); // delete evening class for regular session to avoid conflict
        Livewire::test(\App\Livewire\Admin\UserManager::class)
            ->call('create')
            ->set('role', 'teacher')
            ->assertSet('currentSessionIsRegular', true)
            ->assertSee('Class 8')
            ->assertDontSee('(Regular)');
    }

    public function test_class_and_subject_assignment_is_shift_scoped()
    {
        $this->actingAs($this->admin);

        // Create morning class and morning subject
        $morningClass = Classes::create([
            'academic_session_id' => $this->session->id,
            'name' => 'Class 9M',
            'numeric_value' => 9,
            'shift_type' => 'morning',
        ]);
        $morningSubject = DB::table('subjects')->insertGetId([
            'class_id' => $morningClass->id,
            'name' => 'Math Morning',
            'code' => 'MATH-M',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create evening class and evening subject
        $eveningClass = Classes::create([
            'academic_session_id' => $this->session->id,
            'name' => 'Class 9E',
            'numeric_value' => 9,
            'shift_type' => 'evening',
        ]);
        $eveningSubject = DB::table('subjects')->insertGetId([
            'class_id' => $eveningClass->id,
            'name' => 'Math Evening',
            'code' => 'MATH-E',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 1. In Morning Shift: Assign morning class as class teacher, and morning subject allocation.
        session(['selected_shift_type' => 'morning']);

        Livewire::test(\App\Livewire\Admin\UserManager::class)
            ->call('edit', $this->teacher->id)
            ->assertSet('class_id', null)
            ->set('class_id', $morningClass->id)
            ->set('class_subject', 'General')
            ->set('teachingAssignments', [
                [
                    'class_id' => $morningClass->id,
                    'subject_id' => $morningSubject,
                ]
            ])
            ->call('store')
            ->assertHasNoErrors();

        // Verify class teacher saved
        $sessionUser = DB::table('session_user')
            ->where('user_id', $this->teacher->id)
            ->where('academic_session_id', $this->session->id)
            ->first();
        $this->assertEquals($morningClass->id, $sessionUser->class_id);

        // Verify subject allocation saved
        $allocs = DB::table('subject_allocations')
            ->where('user_id', $this->teacher->id)
            ->get();
        $this->assertCount(1, $allocs);
        $this->assertEquals($morningClass->id, $allocs->first()->class_id);
        $this->assertEquals($morningSubject, $allocs->first()->subject_id);

        // 2. Switch to Evening Shift: Load edit.
        session(['selected_shift_type' => 'evening']);

        Livewire::test(\App\Livewire\Admin\UserManager::class)
            ->call('edit', $this->teacher->id)
            // class_id should load as null because the current session_user class belongs to the morning shift!
            ->assertSet('class_id', null)
            // Set class_id to evening class
            ->set('class_id', $eveningClass->id)
            ->set('class_subject', 'Science')
            // Set subject allocation for evening
            ->set('teachingAssignments', [
                [
                    'class_id' => $eveningClass->id,
                    'subject_id' => $eveningSubject,
                ]
            ])
            ->call('store')
            ->assertHasNoErrors();

        // Verify class teacher updated to evening class in DB (single class_id column)
        $sessionUser = DB::table('session_user')
            ->where('user_id', $this->teacher->id)
            ->where('academic_session_id', $this->session->id)
            ->first();
        $this->assertEquals($eveningClass->id, $sessionUser->class_id);

        // Verify BOTH subject allocations exist in the DB (evening subject was added, morning was not deleted!)
        $allocs = DB::table('subject_allocations')
            ->where('user_id', $this->teacher->id)
            ->get();
        $this->assertCount(2, $allocs);
        $this->assertContains($morningSubject, $allocs->pluck('subject_id')->toArray());
        $this->assertContains($eveningSubject, $allocs->pluck('subject_id')->toArray());

        // 3. Switch back to Morning Shift: Load edit.
        session(['selected_shift_type' => 'morning']);

        Livewire::test(\App\Livewire\Admin\UserManager::class)
            ->call('edit', $this->teacher->id)
            // class_id should load as null because database has evening class, which doesn't match morning
            ->assertSet('class_id', null)
            // Clear morning subject assignments (by keeping teachingAssignments empty)
            ->set('teachingAssignments', [])
            ->call('store')
            ->assertHasNoErrors();

        // Verify class teacher in DB remains unchanged (since it belongs to evening and we did not overwrite it)
        $sessionUser = DB::table('session_user')
            ->where('user_id', $this->teacher->id)
            ->where('academic_session_id', $this->session->id)
            ->first();
        $this->assertEquals($eveningClass->id, $sessionUser->class_id);

        // Verify morning subject allocation is deleted, but evening subject allocation is NOT deleted
        $allocs = DB::table('subject_allocations')
            ->where('user_id', $this->teacher->id)
            ->get();
        $this->assertCount(1, $allocs);
        $this->assertEquals($eveningSubject, $allocs->first()->subject_id);
    }
}

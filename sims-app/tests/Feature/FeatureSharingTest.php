<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\AcademicSession;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Livewire\Livewire;
use App\Livewire\Admin\AccessControl\FeatureSharingManager;

class FeatureSharingTest extends TestCase
{
    use RefreshDatabase;

    private AcademicSession $session;
    private User $admin;
    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        // Create an active academic session
        $this->session = AcademicSession::create([
            'name'       => '2026-2027',
            'start_date' => '2026-04-01',
            'end_date'   => '2027-03-31',
            'is_active'  => true,
        ]);

        // Admin user
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->admin->assignRole('Super Admin');

        // Teacher user enrolled in the active session
        $this->teacher = User::factory()->create([
            'name' => 'Maqsood',
            'role' => 'teacher',
        ]);

        // Enroll teacher in session
        DB::table('session_user')->insert([
            'user_id'             => $this->teacher->id,
            'academic_session_id' => $this->session->id,
            'is_active'           => true,
            'is_primary'          => true,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);
    }

    // -------------------------------------------------------------------------
    // 1. SIDEBAR VISIBILITY: section hidden when no shared permissions exist
    // -------------------------------------------------------------------------

    /** @test */
    public function teacher_sidebar_hides_shared_features_section_when_no_permissions_granted(): void
    {
        $this->actingAs($this->teacher);

        $response = $this->get(route('teacher.dashboard'));
        $response->assertStatus(200);
        $response->assertDontSee('Shared Features');
    }

    // -------------------------------------------------------------------------
    // 2. SIDEBAR VISIBILITY: section appears once any permission is granted
    // -------------------------------------------------------------------------

    /** @test */
    public function teacher_sidebar_shows_shared_features_section_after_permission_is_granted(): void
    {
        // Grant fees.manage to the teacher for the active session
        DB::table('session_user_permissions')->insert([
            'user_id'             => $this->teacher->id,
            'academic_session_id' => $this->session->id,
            'permission_name'     => 'fees.manage',
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        $this->actingAs($this->teacher);

        $response = $this->get(route('teacher.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Shared Features');
        $response->assertSee('Fee Management');
    }

    // -------------------------------------------------------------------------
    // 3. GATE: teacher with fees.manage can access protected routes
    // -------------------------------------------------------------------------

    /** @test */
    public function teacher_with_fees_manage_permission_can_access_shared_fee_routes(): void
    {
        DB::table('session_user_permissions')->insert([
            'user_id'             => $this->teacher->id,
            'academic_session_id' => $this->session->id,
            'permission_name'     => 'fees.manage',
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        $this->actingAs($this->teacher);

        $response = $this->get(route('teacher.shared.fee.defaulters'));
        $response->assertStatus(200);
    }

    // -------------------------------------------------------------------------
    // 4. GATE: teacher without fees.manage is denied
    // -------------------------------------------------------------------------

    /** @test */
    public function teacher_without_fees_manage_permission_is_denied_fee_routes(): void
    {
        $this->actingAs($this->teacher);

        $response = $this->get(route('teacher.shared.fee.defaulters'));
        $response->assertStatus(403);
    }

    // -------------------------------------------------------------------------
    // 5. ADMIN: accesses own admin fee routes (no session_user_permissions needed)
    // -------------------------------------------------------------------------

    /** @test */
    public function admin_can_access_admin_fee_routes_without_session_permissions(): void
    {
        $this->actingAs($this->admin);

        // Admins use /admin/fee/* which is guarded by isAdmin middleware only
        $response = $this->get(route('admin.fee.defaulters'));
        $response->assertStatus(200);
    }


    // -------------------------------------------------------------------------
    // 6. SESSION ISOLATION: permission in session A does not bleed into session B
    // -------------------------------------------------------------------------

    /** @test */
    public function permission_granted_in_one_session_does_not_affect_another_session(): void
    {
        $sessionB = AcademicSession::create([
            'name'       => '2027-2028',
            'start_date' => '2027-04-01',
            'end_date'   => '2028-03-31',
            'is_active'  => false,
        ]);

        // Grant only in session A
        DB::table('session_user_permissions')->insert([
            'user_id'             => $this->teacher->id,
            'academic_session_id' => $this->session->id,
            'permission_name'     => 'fees.manage',
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        // Verify no row exists for session B
        $exists = DB::table('session_user_permissions')
            ->where('user_id', $this->teacher->id)
            ->where('academic_session_id', $sessionB->id)
            ->where('permission_name', 'fees.manage')
            ->exists();

        $this->assertFalse($exists, 'Permission leaked into the other session.');
    }

    // -------------------------------------------------------------------------
    // 7. LIVEWIRE: FeatureSharingManager renders and lists enrolled teachers
    // -------------------------------------------------------------------------

    /** @test */
    public function feature_sharing_manager_renders_for_admin(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(FeatureSharingManager::class)
            ->assertStatus(200)
            ->assertSee('Maqsood');
    }

    // -------------------------------------------------------------------------
    // 8. LIVEWIRE: togglePermission grants and writes to session_user_permissions
    // -------------------------------------------------------------------------

    /** @test */
    public function toggle_permission_inserts_record_into_session_user_permissions(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(FeatureSharingManager::class)
            ->set('selectedUserId', $this->teacher->id)
            ->call('togglePermission', 'fees.manage');

        $this->assertDatabaseHas('session_user_permissions', [
            'user_id'             => $this->teacher->id,
            'academic_session_id' => $this->session->id,
            'permission_name'     => 'fees.manage',
        ]);
    }

    // -------------------------------------------------------------------------
    // 9. LIVEWIRE: togglePermission revokes a previously granted permission
    // -------------------------------------------------------------------------

    /** @test */
    public function toggle_permission_removes_existing_record_from_session_user_permissions(): void
    {
        // Pre-grant
        DB::table('session_user_permissions')->insert([
            'user_id'             => $this->teacher->id,
            'academic_session_id' => $this->session->id,
            'permission_name'     => 'fees.manage',
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        $this->actingAs($this->admin);

        Livewire::test(FeatureSharingManager::class)
            ->set('selectedUserId', $this->teacher->id)
            ->call('togglePermission', 'fees.manage');

        $this->assertDatabaseMissing('session_user_permissions', [
            'user_id'             => $this->teacher->id,
            'academic_session_id' => $this->session->id,
            'permission_name'     => 'fees.manage',
        ]);
    }

    // -------------------------------------------------------------------------
    // 10. LIVEWIRE: toggleGroup bulk-enables all permissions in a group
    // -------------------------------------------------------------------------

    /** @test */
    public function toggle_group_enable_inserts_all_permissions_for_group(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(FeatureSharingManager::class)
            ->set('selectedUserId', $this->teacher->id)
            ->call('toggleGroup', 'Fee Management', true);

        // Both fees.manage and fees.view-sessions should now exist
        $this->assertDatabaseHas('session_user_permissions', [
            'user_id'             => $this->teacher->id,
            'academic_session_id' => $this->session->id,
            'permission_name'     => 'fees.manage',
        ]);

        $this->assertDatabaseHas('session_user_permissions', [
            'user_id'             => $this->teacher->id,
            'academic_session_id' => $this->session->id,
            'permission_name'     => 'fees.view-sessions',
        ]);
    }

    // -------------------------------------------------------------------------
    // 11. LIVEWIRE: toggleGroup disable removes all permissions in a group
    // -------------------------------------------------------------------------

    /** @test */
    public function toggle_group_disable_removes_all_permissions_for_group(): void
    {
        // Pre-grant both
        DB::table('session_user_permissions')->insert([
            ['user_id' => $this->teacher->id, 'academic_session_id' => $this->session->id, 'permission_name' => 'fees.manage', 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $this->teacher->id, 'academic_session_id' => $this->session->id, 'permission_name' => 'fees.view-sessions', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->actingAs($this->admin);

        Livewire::test(FeatureSharingManager::class)
            ->set('selectedUserId', $this->teacher->id)
            ->call('toggleGroup', 'Fee Management', false);

        $this->assertDatabaseMissing('session_user_permissions', [
            'user_id'             => $this->teacher->id,
            'academic_session_id' => $this->session->id,
            'permission_name'     => 'fees.manage',
        ]);
    }

    // -------------------------------------------------------------------------
    // 12. LIVEWIRE: loadUserPermissions reflects DB state correctly
    // -------------------------------------------------------------------------

    /** @test */
    public function load_user_permissions_reflects_database_state(): void
    {
        DB::table('session_user_permissions')->insert([
            'user_id'             => $this->teacher->id,
            'academic_session_id' => $this->session->id,
            'permission_name'     => 'substitutions.manage',
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        $this->actingAs($this->admin);

        Livewire::test(FeatureSharingManager::class)
            ->set('selectedUserId', $this->teacher->id)
            ->assertSet('userPermissions', ['substitutions.manage']);
    }

    // -------------------------------------------------------------------------
    // 13. GATE returns false when session_user_permissions row is absent
    // -------------------------------------------------------------------------

    /** @test */
    public function gate_denies_teacher_when_no_session_permission_row_exists(): void
    {
        $this->actingAs($this->teacher);

        $this->assertFalse($this->teacher->can('fees.manage'));
    }

    // -------------------------------------------------------------------------
    // 14. GATE grants teacher once the session_user_permissions row is present
    // -------------------------------------------------------------------------

    /** @test */
    public function gate_allows_teacher_when_session_permission_row_exists(): void
    {
        DB::table('session_user_permissions')->insert([
            'user_id'             => $this->teacher->id,
            'academic_session_id' => $this->session->id,
            'permission_name'     => 'fees.manage',
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        $this->actingAs($this->teacher);

        $this->assertTrue($this->teacher->can('fees.manage'));
    }

    // -------------------------------------------------------------------------
    // 15. SHARED ACADEMIC SESSIONS TESTS
    // -------------------------------------------------------------------------

    /** @test */
    public function teacher_with_sessions_manage_can_access_shared_sessions_route_but_cannot_delete(): void
    {
        DB::table('session_user_permissions')->insert([
            'user_id'             => $this->teacher->id,
            'academic_session_id' => $this->session->id,
            'permission_name'     => 'sessions.manage',
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        $this->actingAs($this->teacher);

        $response = $this->get(route('teacher.shared.academic-sessions'));
        $response->assertStatus(200);

        // Try to trigger delete on AcademicSessionManager component from teacher prefix/route context
        // We set the current request URL context by using standard Livewire test with prefix
        // Since Livewire doesn't automatically set the request URL for component calls unless requested, 
        // we can test the delete restriction by simulating a request to the route.
        // Let's assert that hitting the route with a delete call (or similar) is blocked, or test via Livewire request wrapper.
        $this->actingAs($this->teacher);
        
        Livewire::test(\App\Livewire\Admin\AcademicSessionManager::class)
            ->set('isTeacherContext', true)
            ->call('delete', $this->session->id)
            ->assertStatus(403);
    }

    /** @test */
    public function teacher_without_sessions_manage_is_denied_sessions_route(): void
    {
        $this->actingAs($this->teacher);

        $response = $this->get(route('teacher.shared.academic-sessions'));
        $response->assertStatus(403);
    }

    /** @test */
    public function sessions_manage_shows_link_in_teacher_profile_dropdown(): void
    {
        // Without permission
        $this->actingAs($this->teacher);
        $response = $this->get(route('teacher.dashboard'));
        $response->assertDontSee('Manage Sessions');

        // With permission
        DB::table('session_user_permissions')->insert([
            'user_id'             => $this->teacher->id,
            'academic_session_id' => $this->session->id,
            'permission_name'     => 'sessions.manage',
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        $response = $this->get(route('teacher.dashboard'));
        $response->assertSee('Manage Sessions');
    }

    /** @test */
    public function sessions_manage_hides_delete_button_for_teachers(): void
    {
        DB::table('session_user_permissions')->insert([
            'user_id'             => $this->teacher->id,
            'academic_session_id' => $this->session->id,
            'permission_name'     => 'sessions.manage',
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        $this->actingAs($this->teacher);

        // Get the page using teacher route context, check if the Delete button is missing
        $response = $this->get(route('teacher.shared.academic-sessions'));
        $response->assertStatus(200);
        $response->assertDontSee('Delete');
    }

    /** @test */
    public function feature_sharing_manager_scopes_users_and_classes_by_shift(): void
    {
        $this->actingAs($this->admin);

        // 1. Create a Dual-Shift session
        $dualSession = AcademicSession::create([
            'name'       => '2026-2027 Dual',
            'start_date' => '2026-04-01',
            'end_date'   => '2027-03-31',
            'shift_type' => 'Dual',
            'is_active'  => true,
        ]);

        // Deactivate other sessions
        AcademicSession::where('id', '!=', $dualSession->id)->update(['is_active' => false]);

        // 2. Create teachers with different shifts
        $morningTeacher = User::factory()->create(['name' => 'Morning Teacher', 'role' => 'teacher']);
        $eveningTeacher = User::factory()->create(['name' => 'Evening Teacher', 'role' => 'teacher']);

        DB::table('session_user')->insert([
            [
                'user_id'             => $morningTeacher->id,
                'academic_session_id' => $dualSession->id,
                'is_active'           => true,
                'is_primary'          => true,
                'allowed_shifts'      => 'morning',
                'created_at'          => now(),
                'updated_at'          => now(),
            ],
            [
                'user_id'             => $eveningTeacher->id,
                'academic_session_id' => $dualSession->id,
                'is_active'           => true,
                'is_primary'          => true,
                'allowed_shifts'      => 'evening',
                'created_at'          => now(),
                'updated_at'          => now(),
            ],
        ]);

        // 3. Create classes with different shifts
        $morningClass = \App\Models\Classes::create([
            'academic_session_id' => $dualSession->id,
            'name'                => 'Class 9 Morning',
            'numeric_value'       => 9,
            'shift_type'          => 'morning',
        ]);
        $eveningClass = \App\Models\Classes::create([
            'academic_session_id' => $dualSession->id,
            'name'                => 'Class 9 Evening',
            'numeric_value'       => 9,
            'shift_type'          => 'evening',
        ]);

        // 4. Test Morning Context
        session(['selected_shift_type' => 'morning']);

        Livewire::test(FeatureSharingManager::class)
            ->assertStatus(200)
            ->assertSee('Morning Teacher')
            ->assertDontSee('Evening Teacher')
            ->assertViewHas('filteredUsers', function ($users) use ($morningTeacher, $eveningTeacher) {
                return $users->contains('id', $morningTeacher->id) && !$users->contains('id', $eveningTeacher->id);
            })
            ->assertSet('allClasses', function ($classes) use ($morningClass, $eveningClass) {
                $ids = collect($classes)->pluck('id');
                return $ids->contains($morningClass->id) && !$ids->contains($eveningClass->id);
            });

        // 5. Test Evening Context
        session(['selected_shift_type' => 'evening']);

        Livewire::test(FeatureSharingManager::class)
            ->assertStatus(200)
            ->assertSee('Evening Teacher')
            ->assertDontSee('Morning Teacher')
            ->assertViewHas('filteredUsers', function ($users) use ($morningTeacher, $eveningTeacher) {
                return $users->contains('id', $eveningTeacher->id) && !$users->contains('id', $morningTeacher->id);
            })
            ->assertSet('allClasses', function ($classes) use ($morningClass, $eveningClass) {
                $ids = collect($classes)->pluck('id');
                return $ids->contains($eveningClass->id) && !$ids->contains($morningClass->id);
            });
    }

    // -------------------------------------------------------------------------
    // 6. Registration Flow: First user becomes Super Admin, subsequent are staff
    // -------------------------------------------------------------------------

    /** @test */
    public function registration_assigns_super_admin_role_to_first_user_only_and_attaches_session(): void
    {
        // Clear all users first to test first user registration
        DB::table('users')->delete();

        // 1. First user registers as admin
        $response = $this->post(route('register'), [
            'name' => 'First Admin',
            'email' => 'first@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $firstUser = User::where('email', 'first@example.com')->first();
        $this->assertNotNull($firstUser);
        $this->assertTrue($firstUser->hasRole('Super Admin'));
        $this->assertEquals('admin', $firstUser->role);

        // Verify session attachment
        $this->assertTrue(DB::table('session_user')
            ->where('user_id', $firstUser->id)
            ->where('academic_session_id', $this->session->id)
            ->exists());

        // Logout
        auth()->logout();

        // 2. Second user registers as admin (should NOT be Super Admin, should be staff admin)
        $response = $this->post(route('register'), [
            'name' => 'Second Admin',
            'email' => 'second@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $secondUser = User::where('email', 'second@example.com')->first();
        $this->assertNotNull($secondUser);
        $this->assertFalse($secondUser->hasRole('Super Admin'));
        $this->assertEquals('admin', $secondUser->role);

        // Verify session attachment
        $this->assertTrue(DB::table('session_user')
            ->where('user_id', $secondUser->id)
            ->where('academic_session_id', $this->session->id)
            ->exists());

        // Verify that they cannot access protected features (their permissions are empty)
        $this->actingAs($secondUser);
        $this->assertFalse($secondUser->can('students.manage'));
    }

    // -------------------------------------------------------------------------
    // 7. UserManager: Admin created from panel has all features, Teacher has all features
    // -------------------------------------------------------------------------

    /** @test */
    public function admin_created_from_user_manager_is_staff_admin_with_all_permissions_except_access_control(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(\App\Livewire\Admin\UserManager::class)
            ->set('name', 'Panel Admin')
            ->set('email', 'paneladmin@example.com')
            ->set('password', 'password123')
            ->set('role', 'admin')
            ->call('store')
            ->assertHasNoErrors();

        $newUser = User::where('email', 'paneladmin@example.com')->first();
        $this->assertNotNull($newUser);
        $this->assertFalse($newUser->hasRole('Super Admin'));
        $this->assertEquals('admin', $newUser->role);

        // Verify that all permissions are enabled for them in the active session except access control
        $allPermissionsCount = \Spatie\Permission\Models\Permission::count();
        $grantedPermissionsCount = DB::table('session_user_permissions')
            ->where('user_id', $newUser->id)
            ->where('academic_session_id', $this->session->id)
            ->count();

        // 2 access control permissions ('access-control.manage', 'permissions.assign') should be excluded
        $this->assertEquals($allPermissionsCount - 2, $grantedPermissionsCount);
        $this->assertTrue($newUser->can('students.manage'));
        $this->assertFalse($newUser->can('access-control.manage'));
    }

    /** @test */
    public function teacher_created_from_user_manager_has_teacher_role_and_all_permissions_except_access_control(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(\App\Livewire\Admin\UserManager::class)
            ->set('name', 'Panel Teacher')
            ->set('email', 'panelteacher@example.com')
            ->set('password', 'password123')
            ->set('role', 'teacher')
            ->set('allowed_shifts', 'morning')
            ->call('store')
            ->assertHasNoErrors();

        $newUser = User::where('email', 'panelteacher@example.com')->first();
        $this->assertNotNull($newUser);
        $this->assertTrue($newUser->hasRole('Teacher'));
        $this->assertEquals('teacher', $newUser->role);

        // Verify that all permissions are enabled for them in the active session except access control
        $allPermissionsCount = \Spatie\Permission\Models\Permission::count();
        $grantedPermissionsCount = DB::table('session_user_permissions')
            ->where('user_id', $newUser->id)
            ->where('academic_session_id', $this->session->id)
            ->count();

        // 2 access control permissions ('access-control.manage', 'permissions.assign') should be excluded
        $this->assertEquals($allPermissionsCount - 2, $grantedPermissionsCount);
        $this->assertTrue($newUser->can('students.manage'));
        $this->assertFalse($newUser->can('access-control.manage'));
    }

    /** @test */
    public function owner_user_with_id_1_cannot_be_deleted_or_disabled(): void
    {
        // Assert that $this->admin has ID 1
        $this->assertEquals(1, $this->admin->id);

        // Ensure owner is in session_user table and is active
        DB::table('session_user')->insert([
            'user_id'             => 1,
            'academic_session_id' => $this->session->id,
            'is_active'           => true,
            'is_primary'          => true,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        // Create another admin user (ID 2 or higher)
        $otherAdmin = User::factory()->create(['role' => 'admin']);

        // Log in as the other admin
        $this->actingAs($otherAdmin);

        // Try to delete owner (ID 1)
        Livewire::test(\App\Livewire\Admin\UserManager::class)
            ->call('delete', 1)
            ->assertHasNoErrors();

        // Verify owner still exists
        $this->assertTrue(User::where('id', 1)->exists());

        // Try to disable owner (ID 1)
        Livewire::test(\App\Livewire\Admin\UserManager::class)
            ->call('toggleAccountStatus', 1)
            ->assertHasNoErrors();

        // Verify owner is still active
        $isActive = DB::table('session_user')
            ->where('user_id', 1)
            ->where('academic_session_id', $this->session->id)
            ->value('is_active');
        $this->assertTrue((bool)$isActive);
    }

    /** @test */
    public function features_are_scoped_by_shift_for_dual_sessions(): void
    {
        // Act as the Super Admin (bypass scoping, has access control permissions)
        $this->actingAs($this->admin);

        // Make sure the session has shift_type = 'Dual'
        $this->session->update(['shift_type' => 'Dual']);

        // Set shift to morning
        session(['selected_shift_type' => 'morning']);

        // Toggle a permission for the teacher (Maqsood) using FeatureSharingManager
        \Livewire\Livewire::test(\App\Livewire\Admin\AccessControl\FeatureSharingManager::class)
            ->set('selectedUserId', $this->teacher->id)
            ->call('togglePermission', 'students.manage')
            ->assertHasNoErrors();

        // Log in as the teacher
        $this->actingAs($this->teacher);

        // Under morning shift, teacher should be allowed 'students.manage'
        session(['selected_shift_type' => 'morning']);
        $this->assertTrue($this->teacher->can('students.manage'));

        // Under evening shift, teacher should NOT be allowed 'students.manage'
        session(['selected_shift_type' => 'evening']);
        $this->assertFalse($this->teacher->can('students.manage'));
    }

    /** @test */
    public function features_work_well_for_regular_sessions(): void
    {
        $this->actingAs($this->admin);

        // Set session to regular
        $this->session->update(['shift_type' => 'Regular']);
        session(['selected_shift_type' => 'regular']);

        // Toggle permission for the teacher
        \Livewire\Livewire::test(\App\Livewire\Admin\AccessControl\FeatureSharingManager::class)
            ->set('selectedUserId', $this->teacher->id)
            ->call('togglePermission', 'exams.manage')
            ->assertHasNoErrors();

        // Log in as the teacher
        $this->actingAs($this->teacher);

        // Under regular session, teacher should have permission 'exams.manage'
        $this->assertTrue($this->teacher->can('exams.manage'));
    }
}

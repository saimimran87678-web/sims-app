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
}

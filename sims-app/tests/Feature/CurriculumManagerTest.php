<?php

namespace Tests\Feature;

use App\Models\AcademicSession;
use App\Models\Classes;
use App\Models\Program;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class CurriculumManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    public function test_admin_can_access_curriculum_manager_page()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('Super Admin');

        $this->actingAs($admin)
            ->get(route('admin.curriculum'))
            ->assertStatus(200)
            ->assertSee('Curriculum & Programs Hub', false)
            ->assertSee('Master Subject Catalogue');
    }

    public function test_admin_can_create_canonical_subject_in_catalogue()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('Super Admin');
        $this->actingAs($admin);

        Livewire::test(\App\Livewire\Admin\CurriculumManager::class)
            ->set('activeTab', 'catalogue')
            ->call('openCreateSubjectModal')
            ->set('subjectName', 'Robotics and Artificial Intelligence')
            ->set('subjectCode', 'ROBO')
            ->set('subjectCategory', 'Computer')
            ->call('saveSubject')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('subjects', [
            'name' => 'Robotics and Artificial Intelligence',
            'code' => 'ROBO',
            'category' => 'Computer',
            'class_id' => null,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_create_program_and_bundle_subjects()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('Super Admin');
        $this->actingAs($admin);

        $math = Subject::firstOrCreate(
            ['name' => 'Mathematics'],
            ['code' => 'MATH', 'category' => 'Mathematics', 'class_id' => null, 'is_active' => true]
        );

        $ai = Subject::firstOrCreate(
            ['name' => 'Artificial Intelligence'],
            ['code' => 'AI', 'category' => 'Computer', 'class_id' => null, 'is_active' => true]
        );

        // 1. Create Program
        Livewire::test(\App\Livewire\Admin\CurriculumManager::class)
            ->set('activeTab', 'programs')
            ->call('openCreateProgramModal')
            ->set('programName', 'Advanced AI & Robotics')
            ->set('programCode', 'ADV-AI')
            ->set('programLevel', 'Matric')
            ->set('programDescription', 'Specialized track for high school computer science.')
            ->call('saveProgram')
            ->assertHasNoErrors();

        $prog = Program::where('code', 'ADV-AI')->first();
        $this->assertNotNull($prog);

        // 2. Bundle subjects (Math compulsory, AI elective)
        Livewire::test(\App\Livewire\Admin\CurriculumManager::class)
            ->set('activeTab', 'programs')
            ->call('openProgramSubjectsModal', $prog->id)
            ->set('newSubjectIdToAdd', $math->id)
            ->set('newSubjectType', 'compulsory')
            ->call('addSubjectToProgram')
            ->set('newSubjectIdToAdd', $ai->id)
            ->set('newSubjectType', 'elective')
            ->call('addSubjectToProgram')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('program_subjects', [
            'program_id' => $prog->id,
            'subject_id' => $math->id,
            'subject_type' => 'compulsory',
        ]);

        $this->assertDatabaseHas('program_subjects', [
            'program_id' => $prog->id,
            'subject_id' => $ai->id,
            'subject_type' => 'elective',
        ]);
    }

    public function test_admin_can_assign_programs_to_classes_and_set_default()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('Super Admin');
        $this->actingAs($admin);

        $session = AcademicSession::create([
            'name' => '2026-2027',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'shift_type' => 'Regular',
            'is_active' => true,
        ]);

        $class9 = Classes::create([
            'name' => 'Class 9th A',
            'numeric_value' => 9,
            'academic_session_id' => $session->id,
            'shift_type' => 'regular',
        ]);

        $prog1 = Program::create(['name' => 'Track One', 'code' => 'TRK-1', 'level' => 'Matric', 'is_active' => true]);
        $prog2 = Program::create(['name' => 'Track Two', 'code' => 'TRK-2', 'level' => 'Matric', 'is_active' => true]);

        Livewire::test(\App\Livewire\Admin\CurriculumManager::class)
            ->set('selectedSessionId', $session->id)
            ->set('activeTab', 'offerings')
            ->call('openClassOfferingsModal', $class9->id)
            ->set('offeringProgramIds', [$prog1->id, $prog2->id])
            ->set('offeringDefaultProgramId', $prog2->id)
            ->call('saveClassOfferings')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('class_programs', [
            'class_id' => $class9->id,
            'program_id' => $prog1->id,
            'is_default' => false,
        ]);

        $this->assertDatabaseHas('class_programs', [
            'class_id' => $class9->id,
            'program_id' => $prog2->id,
            'is_default' => true,
        ]);
    }

    public function test_class_manager_modal_allows_toggling_tracks_and_setting_default()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('Super Admin');
        $this->actingAs($admin);

        $session = AcademicSession::create([
            'name' => '2026-2027',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'shift_type' => 'Regular',
            'is_active' => true,
        ]);

        $class = Classes::create([
            'name' => 'Class 10th',
            'numeric_value' => 10,
            'academic_session_id' => $session->id,
            'shift_type' => 'regular',
        ]);

        $prog1 = Program::create(['name' => 'Science Computer', 'code' => 'SCI-CS', 'level' => 'Matric', 'is_active' => true]);
        $prog2 = Program::create(['name' => 'Science Biology', 'code' => 'SCI-BIO', 'level' => 'Matric', 'is_active' => true]);

        Livewire::test(\App\Livewire\Admin\ClassManager::class)
            ->set('selectedSessionId', $session->id)
            ->call('openSubjectModal', $class->id, $class->name)
            ->call('toggleClassTrack', $prog1->id)
            ->call('toggleClassTrack', $prog2->id)
            ->call('setClassDefaultTrack', $prog2->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('class_programs', [
            'class_id' => $class->id,
            'program_id' => $prog1->id,
            'is_default' => false,
        ]);

        $this->assertDatabaseHas('class_programs', [
            'class_id' => $class->id,
            'program_id' => $prog2->id,
            'is_default' => true,
        ]);
    }

    public function test_admin_can_delete_unlinked_subject_from_catalogue()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('Super Admin');
        $this->actingAs($admin);

        $tempSubject = Subject::create([
            'name' => 'Mistakenly Added Subject',
            'code' => 'TEMP123',
            'category' => 'General',
            'is_active' => false,
        ]);

        $this->assertTrue($tempSubject->canBeDeleted());
        $this->assertEquals(0, $tempSubject->getLinkedRecordsCount());

        Livewire::test(\App\Livewire\Admin\CurriculumManager::class)
            ->set('activeTab', 'catalogue')
            ->call('deleteSubject', $tempSubject->id)
            ->assertHasNoErrors()
            ->assertSee('deleted successfully from the Master Catalogue');

        $this->assertDatabaseMissing('subjects', [
            'id' => $tempSubject->id,
        ]);
    }

    public function test_admin_cannot_delete_subject_with_linked_records()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('Super Admin');
        $this->actingAs($admin);

        $math = Subject::firstOrCreate(
            ['name' => 'Mathematics'],
            ['code' => 'MATH', 'category' => 'Mathematics', 'is_active' => true]
        );

        $prog = Program::create([
            'name' => 'Test Protection Program',
            'code' => 'PROT-TEST',
            'level' => 'Matric',
            'is_active' => true,
        ]);

        $prog->subjects()->attach($math->id, ['subject_type' => 'compulsory', 'sort_order' => 1]);

        $this->assertFalse($math->canBeDeleted());
        $this->assertGreaterThan(0, $math->getLinkedRecordsCount());

        Livewire::test(\App\Livewire\Admin\CurriculumManager::class)
            ->set('activeTab', 'catalogue')
            ->call('deleteSubject', $math->id)
            ->assertHasNoErrors()
            ->assertSee('Cannot delete subject');

        $this->assertDatabaseHas('subjects', [
            'id' => $math->id,
        ]);
    }

    public function test_admin_can_batch_bundle_multiple_subjects_using_multiselect()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('Super Admin');
        $this->actingAs($admin);

        $prog = Program::create([
            'name' => 'Batch Test Program',
            'code' => 'BATCH-TEST',
            'level' => 'Matric',
            'is_active' => true,
        ]);

        $sub1 = Subject::firstOrCreate(['name' => 'Physics'], ['code' => 'PHY', 'category' => 'Science', 'is_active' => true]);
        $sub2 = Subject::firstOrCreate(['name' => 'Chemistry'], ['code' => 'CHEM', 'category' => 'Science', 'is_active' => true]);
        $sub3 = Subject::firstOrCreate(['name' => 'Biology'], ['code' => 'BIO', 'category' => 'Science', 'is_active' => true]);

        Livewire::test(\App\Livewire\Admin\CurriculumManager::class)
            ->call('openProgramSubjectsModal', $prog->id)
            ->set('newSubjectIdsToAdd', [$sub1->id, $sub2->id, $sub3->id])
            ->set('newSubjectType', 'compulsory')
            ->call('addSubjectsToProgram')
            ->assertHasNoErrors()
            ->assertSee('subject(s) added to program bundle');

        $this->assertDatabaseHas('program_subjects', [
            'program_id' => $prog->id,
            'subject_id' => $sub1->id,
        ]);
        $this->assertDatabaseHas('program_subjects', [
            'program_id' => $prog->id,
            'subject_id' => $sub2->id,
        ]);
        $this->assertDatabaseHas('program_subjects', [
            'program_id' => $prog->id,
            'subject_id' => $sub3->id,
        ]);
    }

    public function test_admin_can_prebundle_subjects_during_program_creation()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('Super Admin');
        $this->actingAs($admin);

        $sub1 = Subject::firstOrCreate(['name' => 'English'], ['code' => 'ENG', 'category' => 'Language', 'is_active' => true]);
        $sub2 = Subject::firstOrCreate(['name' => 'Urdu'], ['code' => 'URD', 'category' => 'Language', 'is_active' => true]);

        Livewire::test(\App\Livewire\Admin\CurriculumManager::class)
            ->call('openCreateProgramModal')
            ->set('programName', 'Pre-bundle Arts Track')
            ->set('programCode', 'ARTS-PRE')
            ->set('programLevel', 'Intermediate')
            ->set('programPreselectedSubjectIds', [$sub1->id, $sub2->id])
            ->call('saveProgram')
            ->assertHasNoErrors();

        $prog = Program::where('code', 'ARTS-PRE')->first();
        $this->assertNotNull($prog);

        $this->assertDatabaseHas('program_subjects', [
            'program_id' => $prog->id,
            'subject_id' => $sub1->id,
        ]);
        $this->assertDatabaseHas('program_subjects', [
            'program_id' => $prog->id,
            'subject_id' => $sub2->id,
        ]);
    }
}

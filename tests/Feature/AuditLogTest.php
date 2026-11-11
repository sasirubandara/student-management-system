<?php

use App\Models\AuditLog;
use App\Models\Student;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * Create an active user for audit log tests.
 */
function createAuditFeatureTestUser(string $role): User
{
    return User::factory()->create([
        'role' => $role,
        'is_active' => true,
    ]);
}

it('records an activity with its actor and affected model', function () {
    $admin = createAuditFeatureTestUser(User::ROLE_ADMIN);

    $student = Student::factory()->create();

    $this->actingAs($admin);

    $auditLog = app(AuditLogger::class)->record(
        'student.created',
        $student,
        [
            'changed_fields' => [
                'student_number',
                'first_name',
            ],
        ]
    );

    expect($auditLog)->toBeInstanceOf(AuditLog::class)
        ->and($auditLog->event)->toBe('student.created')
        ->and($auditLog->user_id)->toBe($admin->getKey())
        ->and($auditLog->auditable_type)->toBe($student->getMorphClass())
        ->and($auditLog->auditable_id)->toBe($student->getKey())
        ->and($auditLog->properties['changed_fields'])
        ->toBe(['student_number', 'first_name']);

    $this->assertDatabaseHas('audit_logs', [
        'id' => $auditLog->getKey(),
        'event' => 'student.created',
        'user_id' => $admin->getKey(),
        'auditable_type' => $student->getMorphClass(),
        'auditable_id' => $student->getKey(),
    ]);
});

it('records system activities without an affected model', function () {
    $admin = createAuditFeatureTestUser(User::ROLE_ADMIN);

    $this->actingAs($admin);

    $auditLog = app(AuditLogger::class)->record(
        'auth.login_succeeded'
    );

    expect($auditLog->event)->toBe('auth.login_succeeded')
        ->and($auditLog->user_id)->toBe($admin->getKey())
        ->and($auditLog->auditable_type)->toBeNull()
        ->and($auditLog->auditable_id)->toBeNull();
});

it('removes sensitive properties recursively', function () {
    $admin = createAuditFeatureTestUser(User::ROLE_ADMIN);

    $this->actingAs($admin);

    $auditLog = app(AuditLogger::class)->record(
        'user.updated',
        null,
        [
            'changed_fields' => ['role'],
            'password' => 'do-not-store',
            'password_confirmation' => 'do-not-store',
            'access_token' => 'do-not-store',
            'api_key' => 'do-not-store',

            'changes' => [
                'role' => [
                    'old' => 'staff',
                    'new' => 'admin',
                ],
                'client_secret' => 'do-not-store',
                'safe_value' => 'this value is permitted',
            ],
        ]
    );

    expect($auditLog->properties)
        ->toHaveKey('changed_fields')
        ->and($auditLog->properties)
        ->not->toHaveKey('password')
        ->and($auditLog->properties)
        ->not->toHaveKey('password_confirmation')
        ->and($auditLog->properties)
        ->not->toHaveKey('access_token')
        ->and($auditLog->properties)
        ->not->toHaveKey('api_key')
        ->and($auditLog->properties['changes'])
        ->not->toHaveKey('client_secret')
        ->and($auditLog->properties['changes']['safe_value'])
        ->toBe('this value is permitted');
});

it('records the explicitly supplied actor', function () {
    $admin = createAuditFeatureTestUser(User::ROLE_ADMIN);
    $staff = createAuditFeatureTestUser(User::ROLE_STAFF);

    // The explicitly supplied actor should be recorded.
    $this->actingAs($staff);

    $auditLog = app(AuditLogger::class)->record(
        'user.role_updated',
        null,
        ['changed_fields' => ['role']],
        $admin
    );

    expect($auditLog->user_id)->toBe($admin->getKey());
});

it('allows administrators to view the audit log directory', function () {
    $admin = createAuditFeatureTestUser(User::ROLE_ADMIN);

    $this->actingAs($admin);

    app(AuditLogger::class)->record(
        'student.created',
        null,
        ['changed_fields' => ['student_number']]
    );

    $response = $this->get(route('audit-logs.index'));

    $response->assertOk()
        ->assertSee('Audit Logs')
        ->assertSee('Student created');
});

it('allows administrators to view individual audit records', function () {
    $admin = createAuditFeatureTestUser(User::ROLE_ADMIN);

    $this->actingAs($admin);

    $auditLog = app(AuditLogger::class)->record(
        'student.created',
        null,
        ['changed_fields' => ['student_number']]
    );

    $response = $this->get(
        route('audit-logs.show', $auditLog)
    );

    $response->assertOk()
        ->assertSee('Audit Activity Details')
        ->assertSee('student.created')
        ->assertSee('student_number');
});

it('prevents staff members from viewing audit logs', function () {
    $admin = createAuditFeatureTestUser(User::ROLE_ADMIN);
    $staff = createAuditFeatureTestUser(User::ROLE_STAFF);

    $auditLog = app(AuditLogger::class)->record(
        'student.created',
        null,
        [],
        $admin
    );

    $this->actingAs($staff);

    $this->get(route('audit-logs.index'))
        ->assertForbidden();

    $this->get(route('audit-logs.show', $auditLog))
        ->assertForbidden();
});

it('requires authentication to access audit logs', function () {
    $admin = createAuditFeatureTestUser(User::ROLE_ADMIN);

    $this->actingAs($admin);

    $auditLog = app(AuditLogger::class)->record(
        'student.created'
    );

    $this->actingAsGuest();

    $this->get(route('audit-logs.index'))
        ->assertRedirect(route('login'));

    $this->get(route('audit-logs.show', $auditLog))
        ->assertRedirect(route('login'));
});

it('filters audit logs by module', function () {
    $admin = createAuditFeatureTestUser(User::ROLE_ADMIN);

    $this->actingAs($admin);

    app(AuditLogger::class)->record('student.created');

    app(AuditLogger::class)->record('teacher.created');

    $response = $this->get(
        route('audit-logs.index', ['module' => 'student'])
    );

    $response->assertOk()
        ->assertSee('Student created')
        ->assertViewHas('auditLogs', function ($auditLogs) {
            return $auditLogs->total() === 1
                && $auditLogs->getCollection()
                    ->pluck('event')
                    ->all() === ['student.created'];
        });
});

it('keeps audit routes read-only', function () {
    $admin = createAuditFeatureTestUser(User::ROLE_ADMIN);

    $this->actingAs($admin);

    $auditLog = app(AuditLogger::class)->record(
        'student.created'
    );

    $this->put(
        route('audit-logs.show', $auditLog),
        ['event' => 'modified.event']
    )->assertStatus(405);

    $this->patch(
        route('audit-logs.show', $auditLog),
        ['event' => 'modified.event']
    )->assertStatus(405);

    $this->delete(
        route('audit-logs.show', $auditLog)
    )->assertStatus(405);

    $this->post(route('audit-logs.index'))
        ->assertStatus(405);

    $this->assertDatabaseHas('audit_logs', [
        'id' => $auditLog->getKey(),
        'event' => 'student.created',
    ]);
});

it('rolls back audit entries when the surrounding transaction fails', function (): void {
    $admin = createAuditFeatureTestUser(User::ROLE_ADMIN);

    $exceptionWasCaught = false;

    try {
        DB::transaction(function () use ($admin): void {
            app(AuditLogger::class)->record(
                'test.transaction_rollback',
                actor: $admin
            );

            throw new RuntimeException('Force transaction rollback.');
        });
    } catch (RuntimeException $exception) {
        $exceptionWasCaught =
            $exception->getMessage() === 'Force transaction rollback.';
    }

    expect($exceptionWasCaught)->toBeTrue();

    expect(
        AuditLog::query()
            ->where('event', 'test.transaction_rollback')
            ->exists()
    )->toBeFalse();
});

it('preserves audit records when the acting user is deleted', function (): void {
    $admin = createAuditFeatureTestUser(User::ROLE_ADMIN);

    $auditLog = app(AuditLogger::class)->record(
        'test.actor_deleted',
        actor: $admin
    );

    DB::table('users')
        ->where('id', $admin->getKey())
        ->delete();

    $this->assertDatabaseHas('audit_logs', [
        'id' => $auditLog->getKey(),
        'event' => 'test.actor_deleted',
        'user_id' => null,
    ]);

    expect($auditLog->fresh()->user)->toBeNull();
});
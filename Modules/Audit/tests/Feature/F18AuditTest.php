<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Audit\Contracts\AuditReader;
use Modules\Audit\Contracts\AuditRecorder;
use Modules\Audit\Data\AuditEventPayload;
use Modules\Audit\Data\AuditReadFilter;
use Modules\Audit\Models\AuditEvent;
use Modules\Audit\Services\NullAuditRecorder;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// AuditRecorder — basic recording
// ---------------------------------------------------------------------------

describe('audit recorder — basic recording', function (): void {

    it('is bound in the container', function (): void {
        expect(app()->bound(AuditRecorder::class))->toBeTrue();
    });

    it('records an event and returns an AuditEvent', function (): void {
        $recorder = app(AuditRecorder::class);
        $payload = new AuditEventPayload(
            actorType: 'administrative',
            sourceModule: 'Staff',
            action: 'staff_position.assigned',
            actorAccountId: 1,
            portal: 'admin',
            subjectType: 'StaffPosition',
            subjectId: 42,
            institutionId: 10,
            afterState: ['position_definition' => 'teacher'],
            changeReason: 'Semester assignment',
        );

        $event = $recorder->record($payload);

        expect($event->id)->toBeGreaterThan(0);
        expect($event->event_id)->toBeString();
        expect($event->source_module)->toBe('Staff');
        expect($event->action)->toBe('staff_position.assigned');
        expect($event->actor_account_id)->toBe(1);
        expect($event->subject_id)->toBe(42);
    });

    it('assigns a unique UUID event_id', function (): void {
        $recorder = app(AuditRecorder::class);
        $payload = new AuditEventPayload(
            actorType: 'system',
            sourceModule: 'AcademicCalendar',
            action: 'semester.opened',
        );

        $e1 = $recorder->record($payload);
        $e2 = $recorder->record($payload);

        expect($e1->event_id)->not->toBe($e2->event_id);
    });

    it('records a system event with no actor account', function (): void {
        $recorder = app(AuditRecorder::class);
        $payload = new AuditEventPayload(
            actorType: 'system',
            sourceModule: 'AcademicCalendar',
            action: 'semester.auto_closed',
            actorAccountId: null,
        );

        $event = $recorder->record($payload);
        expect($event->actor_account_id)->toBeNull();
        expect($event->actor_type)->toBe('system');
    });

});

// ---------------------------------------------------------------------------
// AuditRecorder — redaction rules
// ---------------------------------------------------------------------------

describe('audit recorder — redaction rules', function (): void {

    it('rejects a payload with a password key in after_state', function (): void {
        $recorder = app(AuditRecorder::class);
        $payload = new AuditEventPayload(
            actorType: 'administrative',
            sourceModule: 'Accounts',
            action: 'account.created',
            afterState: ['password' => 'MUST_NOT_APPEAR'],
        );

        expect(fn () => $recorder->record($payload))->toThrow(InvalidArgumentException::class);
    });

    it('rejects a payload with a token key in metadata', function (): void {
        $recorder = app(AuditRecorder::class);
        $payload = new AuditEventPayload(
            actorType: 'system',
            sourceModule: 'Accounts',
            action: 'session.started',
            metadata: ['session_token' => 'secret_value'],
        );

        expect(fn () => $recorder->record($payload))->toThrow(InvalidArgumentException::class);
    });

    it('rejects a payload with national_id key in before_state', function (): void {
        $recorder = app(AuditRecorder::class);
        $payload = new AuditEventPayload(
            actorType: 'administrative',
            sourceModule: 'People',
            action: 'person.updated',
            beforeState: ['national_id_raw' => '123456789'],
        );

        expect(fn () => $recorder->record($payload))->toThrow(InvalidArgumentException::class);
    });

    it('allows safe keys in after_state', function (): void {
        $recorder = app(AuditRecorder::class);
        $payload = new AuditEventPayload(
            actorType: 'administrative',
            sourceModule: 'Staff',
            action: 'staff_profile.created',
            afterState: [
                'staff_code' => 'STF-001',
                'position_definition' => 'teacher',
                'started_on' => '2026-09-01',
            ],
        );

        $event = $recorder->record($payload);
        expect($event->after_state)->toHaveKey('staff_code');
    });

});

// ---------------------------------------------------------------------------
// AuditEvent — immutability
// ---------------------------------------------------------------------------

describe('audit event — immutability', function (): void {

    it('throws when attempting to update a recorded event', function (): void {
        $recorder = app(AuditRecorder::class);
        $event = $recorder->record(new AuditEventPayload(
            actorType: 'system',
            sourceModule: 'Test',
            action: 'test.recorded',
        ));

        expect(fn () => $event->update(['action' => 'tampered']))->toThrow(LogicException::class);
    });

    it('throws when attempting to delete a recorded event', function (): void {
        $recorder = app(AuditRecorder::class);
        $event = $recorder->record(new AuditEventPayload(
            actorType: 'system',
            sourceModule: 'Test',
            action: 'test.recorded',
        ));

        expect(fn () => $event->delete())->toThrow(LogicException::class);
    });

    it('count never decreases — records accumulate', function (): void {
        $recorder = app(AuditRecorder::class);
        $payload = new AuditEventPayload(actorType: 'system', sourceModule: 'T', action: 't.test');

        $before = AuditEvent::count();
        $recorder->record($payload);
        $recorder->record($payload);
        $after = AuditEvent::count();

        expect($after)->toBe($before + 2);
    });

});

// ---------------------------------------------------------------------------
// AuditReader
// ---------------------------------------------------------------------------

describe('audit reader — queries', function (): void {

    it('is bound in the container', function (): void {
        expect(app()->bound(AuditReader::class))->toBeTrue();
    });

    it('finds an event by event_id', function (): void {
        $recorder = app(AuditRecorder::class);
        $event = $recorder->record(new AuditEventPayload(
            actorType: 'system',
            sourceModule: 'Test',
            action: 'test.find',
        ));

        $found = app(AuditReader::class)->findByEventId($event->event_id);
        expect($found)->not->toBeNull();
        expect($found->id)->toBe($event->id);
    });

    it('returns null for unknown event_id', function (): void {
        $result = app(AuditReader::class)->findByEventId('00000000-0000-0000-0000-000000000000');
        expect($result)->toBeNull();
    });

    it('filters by institution_id', function (): void {
        $recorder = app(AuditRecorder::class);

        $recorder->record(new AuditEventPayload(
            actorType: 'system', sourceModule: 'T', action: 't.a', institutionId: 1,
        ));
        $recorder->record(new AuditEventPayload(
            actorType: 'system', sourceModule: 'T', action: 't.b', institutionId: 2,
        ));

        $result = app(AuditReader::class)->query(new AuditReadFilter(institutionId: 1));
        expect($result->count())->toBe(1);
        expect($result->first()->institution_id)->toBe(1);
    });

    it('filters by actor_account_id', function (): void {
        $recorder = app(AuditRecorder::class);

        $recorder->record(new AuditEventPayload(
            actorType: 'administrative', sourceModule: 'T', action: 't.x', actorAccountId: 5,
        ));
        $recorder->record(new AuditEventPayload(
            actorType: 'administrative', sourceModule: 'T', action: 't.y', actorAccountId: 6,
        ));

        $result = app(AuditReader::class)->query(new AuditReadFilter(actorAccountId: 5));
        expect($result->count())->toBe(1);
        expect($result->first()->actor_account_id)->toBe(5);
    });

    it('filters by source_module and action', function (): void {
        $recorder = app(AuditRecorder::class);

        $recorder->record(new AuditEventPayload(
            actorType: 'system', sourceModule: 'Staff', action: 'staff.x',
        ));
        $recorder->record(new AuditEventPayload(
            actorType: 'system', sourceModule: 'Staff', action: 'staff.y',
        ));
        $recorder->record(new AuditEventPayload(
            actorType: 'system', sourceModule: 'Organization', action: 'org.x',
        ));

        $result = app(AuditReader::class)->query(new AuditReadFilter(
            sourceModule: 'Staff',
            action: 'staff.x',
        ));
        expect($result->count())->toBe(1);
    });

    it('respects the limit parameter', function (): void {
        $recorder = app(AuditRecorder::class);
        for ($i = 0; $i < 5; $i++) {
            $recorder->record(new AuditEventPayload(
                actorType: 'system', sourceModule: 'T', action: 'bulk.test',
            ));
        }

        $result = app(AuditReader::class)->query(new AuditReadFilter(
            action: 'bulk.test',
            limit: 3,
        ));
        expect($result->count())->toBe(3);
    });

});

// ---------------------------------------------------------------------------
// NullAuditRecorder — test helper
// ---------------------------------------------------------------------------

describe('null audit recorder', function (): void {

    it('records without persisting to database', function (): void {
        $before = AuditEvent::count();

        $recorder = new NullAuditRecorder;
        $event = $recorder->record(new AuditEventPayload(
            actorType: 'system',
            sourceModule: 'Test',
            action: 'null.test',
        ));

        expect(AuditEvent::count())->toBe($before);
        expect($event->event_id)->toStartWith('null-');
    });

});

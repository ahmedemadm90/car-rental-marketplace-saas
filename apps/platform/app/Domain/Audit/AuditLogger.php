<?php

declare(strict_types=1);

namespace App\Domain\Audit;

use App\Domain\Tenancy\TenantContext;
use App\Models\AuditEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

final class AuditLogger
{
    /** @var list<string> */
    private const PROTECTED_ATTRIBUTES = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'card_token',
        'identity_number',
    ];

    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly Request $request,
    ) {}

    /** @param array<string, mixed>|null $before @param array<string, mixed>|null $after @param array<string, mixed> $metadata */
    public function record(string $event, Model $subject, ?array $before = null, ?array $after = null, array $metadata = []): AuditEvent
    {
        return AuditEvent::query()->create([
            'uuid' => (string) Str::ulid(),
            'company_id' => $this->tenantContext->isEstablished() ? $this->tenantContext->companyId() : null,
            'actor_user_id' => $this->tenantContext->actingUserId() ?? $this->request->user()?->getKey(),
            'impersonator_user_id' => $this->tenantContext->impersonatorId(),
            'event' => $event,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'before' => $this->redact($before),
            'after' => $this->redact($after),
            'request_id' => $this->request->attributes->get('request_id'),
            'ip_address' => $this->request->ip(),
            'user_agent' => Str::limit((string) $this->request->userAgent(), 2048, ''),
            'metadata' => $this->redact($metadata),
            'occurred_at' => now(),
        ]);
    }

    /** @param array<string, mixed>|null $payload @return array<string, mixed>|null */
    private function redact(?array $payload): ?array
    {
        if ($payload === null) {
            return null;
        }

        foreach (self::PROTECTED_ATTRIBUTES as $attribute) {
            if (Arr::has($payload, $attribute)) {
                Arr::set($payload, $attribute, '[REDACTED]');
            }
        }

        return $payload;
    }
}

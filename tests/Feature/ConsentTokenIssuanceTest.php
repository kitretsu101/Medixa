<?php

namespace Tests\Feature;

use App\Models\ConsentToken;
use App\Models\User;
use App\Services\ConsentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class ConsentTokenIssuanceTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith([null])]
    #[TestWith([5])]
    #[TestWith([1440])]
    public function test_reissuing_an_active_grant_preserves_its_id_issue_time_and_expiry(?int $ttlMinutes): void
    {
        $this->freezeSecond();
        $token = ConsentToken::factory()->create();
        $original = $token->fresh()->getAttributes();
        $this->travel(10)->minutes();

        $reused = app(ConsentService::class)->issue(
            $token->patient_id, $token->grantee_id, $token->resource_type, $token->resource_id, $ttlMinutes,
        );

        $this->assertSame($token->id, $reused->id);
        $this->assertSame($original, $token->fresh()->getAttributes());
        $this->assertDatabaseCount('consent_tokens', 1);
    }

    public function test_an_active_grant_without_expiry_is_reused_without_adding_an_expiry(): void
    {
        $token = ConsentToken::factory()->create(['expires_at' => null]);

        $reused = app(ConsentService::class)->issue(
            $token->patient_id, $token->grantee_id, $token->resource_type, $token->resource_id, 30,
        );

        $this->assertSame($token->id, $reused->id);
        $this->assertNull($token->fresh()->expires_at);
        $this->assertDatabaseCount('consent_tokens', 1);
    }

    #[TestWith(['expired'])]
    #[TestWith(['revoked'])]
    #[TestWith(['expires_now'])]
    public function test_an_inactive_grant_is_kept_and_a_new_grant_is_issued(string $state): void
    {
        $this->freezeSecond();
        $factory = ConsentToken::factory();
        $token = $state === 'expires_now'
            ? $factory->create(['expires_at' => now()])
            : $factory->{$state}()->create();
        $original = $token->fresh()->getAttributes();

        $issued = app(ConsentService::class)->issue(
            $token->patient_id, $token->grantee_id, $token->resource_type, $token->resource_id, 30,
        );

        $this->assertNotSame($token->id, $issued->id);
        $this->assertSame($original, $token->fresh()->getAttributes());
        $this->assertTrue($issued->isActive());
        $this->assertTrue($issued->expires_at->equalTo(now()->addMinutes(30)));
        $this->assertDatabaseCount('consent_tokens', 2);
    }

    #[TestWith(['patient_id'])]
    #[TestWith(['grantee_id'])]
    #[TestWith(['resource_type'])]
    #[TestWith(['resource_id'])]
    public function test_only_a_grant_with_the_same_patient_grantee_and_resource_is_reused(string $differentField): void
    {
        $token = ConsentToken::factory()->create();
        $grant = $token->only(['patient_id', 'grantee_id', 'resource_type', 'resource_id']);
        $grant[$differentField] = match ($differentField) {
            'patient_id' => User::factory()->patient()->create()->id,
            'grantee_id' => User::factory()->doctor()->create()->id,
            'resource_type' => 'prescription',
            'resource_id' => $token->resource_id + 1,
        };

        $issued = app(ConsentService::class)->issue(
            $grant['patient_id'], $grant['grantee_id'], $grant['resource_type'], $grant['resource_id'], 30,
        );

        $this->assertNotSame($token->id, $issued->id);
        $this->assertDatabaseHas('consent_tokens', ['id' => $issued->id, ...$grant]);
        $this->assertDatabaseCount('consent_tokens', 2);
    }
}

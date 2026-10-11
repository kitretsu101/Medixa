<?php

namespace Tests\Feature;

use App\Contracts\AiCompletionProvider;
use App\Models\ChatSession;
use App\Models\User;
use App\Services\Ai\AiCompletion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class ChatPrivacyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.ai.provider', 'openai');
        Config::set('services.ai.key', null);
        Config::set('services.openai.api_key', null);
        Config::set('medixa.chat.retention_days', 7);
        Http::preventStrayRequests();
    }

    public function test_public_session_id_is_a_server_uuid_and_the_message_is_not_stored(): void
    {
        $response = $this->postJson(route('chatbot.message'), [
            'message' => 'UniqueSymptomPhrase991',
        ]);

        $response->assertOk();
        $token = $response->json('session_token');

        $this->assertTrue(Str::isUuid($token));
        $this->assertNotContains('message', Schema::getColumnListing('chat_sessions'));
        $this->assertDatabaseHas('chat_sessions', [
            'id' => $token,
            'user_id' => null,
        ]);
        $this->assertStringNotContainsString('UniqueSymptomPhrase991', (string) json_encode(ChatSession::query()->first()?->getAttributes()));

        $this->postJson(route('chatbot.message'), [
            'message' => 'Hello again',
            'session_token' => $token,
        ])->assertOk()
            ->assertJsonPath('session_token', $token);

        $this->postJson(route('chatbot.message'), [
            'message' => 'Hello a third time',
            'session_token' => $token,
        ])->assertOk()
            ->assertJsonPath('session_token', $token);

        $this->assertSame(1, ChatSession::query()->count());
        $this->assertTrue(ChatSession::query()->find($token)?->expires_at->greaterThan(now()->addDays(6)));
    }

    public function test_client_supplied_session_id_does_not_create_a_session(): void
    {
        $this->postJson(route('chatbot.message'), [
            'message' => 'Hello',
            'session_token' => 'sess_abc_123',
        ])->assertNotFound();

        $this->postJson(route('chatbot.message'), [
            'message' => 'Hello',
            'session_token' => '1',
        ])->assertNotFound();

        $this->assertSame(0, ChatSession::query()->count());
    }

    public function test_request_cannot_assign_a_chat_session_to_another_user(): void
    {
        $victim = User::factory()->patient()->create();

        $this->postJson(route('chatbot.message'), [
            'message' => 'Hello',
            'user_id' => $victim->id,
        ])->assertOk();

        $this->assertNull(ChatSession::query()->first()?->user_id);
    }

    public function test_guest_cannot_use_a_patient_chat_session(): void
    {
        $this->blockProvider();
        $patient = User::factory()->patient()->create([
            'name' => 'Patient Unique Name 991',
        ]);
        $session = ChatSession::factory()->ownedBy($patient)->create();

        $message = $this->postJson(route('chatbot.message'), [
            'message' => 'Hello',
            'session_token' => $session->id,
        ]);

        $message->assertNotFound();
        $this->assertStringNotContainsString('Patient Unique Name 991', $message->getContent());

        $deletion = $this->deleteJson(route('chatbot.sessions.destroy', $session));

        $deletion->assertNotFound();
        $this->assertStringNotContainsString('Patient Unique Name 991', $deletion->getContent());

        $this->assertModelExists($session);
        $this->assertStringNotContainsString('Patient Unique Name 991', (string) ChatSession::query()->find($session->id)?->toJson());
    }

    #[DataProvider('unauthorizedActors')]
    public function test_another_account_cannot_open_a_patient_chat_session(string $role): void
    {
        $this->blockProvider();
        $patient = User::factory()->patient()->create();
        $session = ChatSession::factory()->ownedBy($patient)->create();
        $actor = $role === 'lab_assistant'
            ? User::factory()->create(['role' => 'lab_assistant'])
            : User::factory()->{$role}()->create();

        $this->actingAs($actor)->postJson(route('chatbot.message'), [
            'message' => 'Hello',
            'session_token' => $session->id,
        ])->assertNotFound();

        $this->actingAs($actor)
            ->deleteJson(route('chatbot.sessions.destroy', $session))
            ->assertNotFound();

        $this->assertModelExists($session);
        $this->assertSame($patient->id, $session->fresh()->user_id);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function unauthorizedActors(): array
    {
        return [
            'other patient' => ['patient'],
            'admin' => ['admin'],
            'doctor' => ['doctor'],
            'receptionist' => ['receptionist'],
            'lab assistant' => ['lab_assistant'],
        ];
    }

    public function test_owner_can_delete_the_session_and_the_old_id_stops_working(): void
    {
        $patient = User::factory()->patient()->create();
        $session = ChatSession::factory()->ownedBy($patient)->create();

        $this->actingAs($patient)
            ->deleteJson(route('chatbot.sessions.destroy', $session))
            ->assertOk()
            ->assertJsonPath('deleted', true);

        $this->assertModelMissing($session);

        $this->actingAs($patient)->postJson(route('chatbot.message'), [
            'message' => 'Hello',
            'session_token' => $session->id,
        ])->assertNotFound();
    }

    public function test_signed_in_session_id_is_not_the_user_id(): void
    {
        $patient = User::factory()->patient()->create();

        $token = $this->actingAs($patient)->postJson(route('chatbot.message'), [
            'message' => 'Hello',
        ])->assertOk()->json('session_token');

        $this->assertTrue(Str::isUuid($token));
        $this->assertNotSame((string) $patient->id, $token);
        $this->assertDatabaseHas('chat_sessions', [
            'id' => $token,
            'user_id' => $patient->id,
        ]);
    }

    public function test_direct_session_urls_that_are_not_owned_return_404(): void
    {
        $this->deleteJson('/chatbot/sessions/1')->assertNotFound();
        $this->deleteJson('/chatbot/sessions/'.Str::uuid())->assertNotFound();
        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/chat-sessions')
            ->assertNotFound();
    }

    public function test_expired_session_is_deleted_and_returns_404(): void
    {
        $session = ChatSession::factory()->expired()->create();

        $this->postJson(route('chatbot.message'), [
            'message' => 'Hello',
            'session_token' => $session->id,
        ])->assertNotFound();

        $this->assertModelMissing($session);
    }

    public function test_prune_command_deletes_only_expired_sessions(): void
    {
        $expired = ChatSession::factory()->expired()->create();
        $current = ChatSession::factory()->create();

        $this->artisan('chat-sessions:prune')->assertSuccessful();

        $this->assertModelMissing($expired);
        $this->assertModelExists($current);
    }

    public function test_provider_failure_log_omits_the_message_and_the_api_key(): void
    {
        Config::set('services.ai.key', 'test-key-not-real');
        Config::set('services.openai.api_key', 'test-key-not-real');

        $entries = [];
        Log::listen(function (MessageLogged $event) use (&$entries): void {
            $entries[] = $event->message.' '.json_encode($event->context);
        });

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response(['error' => 'unavailable'], 503),
        ]);

        $this->postJson(route('chatbot.message'), [
            'message' => 'UniqueSymptomPhrase991',
        ])->assertOk()
            ->assertJsonPath('error', true);

        $blob = implode("\n", $entries);
        $this->assertStringNotContainsString('UniqueSymptomPhrase991', $blob);
        $this->assertStringNotContainsString('test-key-not-real', $blob);
        $this->assertStringContainsString('AI provider request failed', $blob);
    }

    public function test_chat_message_rejects_a_missing_csrf_token_when_the_check_runs(): void
    {
        $this->app->instance('env', 'local');

        $this->withSession(['_token' => 'known-csrf-token'])
            ->postJson(route('chatbot.message'), [
                'message' => 'Hello',
            ])->assertStatus(419);
    }

    public function test_public_chat_panel_shows_the_external_ai_notice_and_clear_action(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Messages can be processed by the configured external AI provider', false)
            ->assertSee('Medixa does not store the message text', false)
            ->assertSee('Clear chat', false);
    }

    private function blockProvider(): void
    {
        $this->app->instance(AiCompletionProvider::class, new class implements AiCompletionProvider
        {
            public function complete(string $systemPrompt, string $userMessage, string $sessionToken): AiCompletion
            {
                throw new RuntimeException('The provider was called for an unauthorized session.');
            }
        });
    }
}

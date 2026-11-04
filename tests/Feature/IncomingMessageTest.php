<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IncomingMessageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.message_integration.token', 'integration-secret');
    }

    public function test_it_sends_the_welcome_message_on_the_first_message_of_a_conversation(): void
    {
        Config::set('services.gemini.business_name', "Momo Shop's");
        Http::fake();

        $response = $this->withHeader('X-Integration-Token', 'integration-secret')
            ->postJson('/api/messages/incoming', [
                'sender_name' => 'Awa',
                'body' => 'Bonjour',
                'source' => 'site',
                'external_id' => 'message-123',
                'conversation_id' => 'chat-123',
            ]);

        $welcome = "Bonjour et bienvenue ! Je suis l'assistant virtuel de **Momo Shop's**. Je suis là pour vous aider 24h/24 et 7j/7.\n\nPour que je puisse vous orienter au mieux vers le service ou la ressource adéquate, pourriez-vous me préciser l'objet de votre demande aujourd'hui ? (Ex. : *Support technique, Renseignement commercial, Facturation*)";

        $response->assertCreated()
            ->assertJsonPath('status', Message::STATUS_REPLIED)
            ->assertJsonPath('reply', $welcome);

        Http::assertNothingSent();
        $this->assertDatabaseHas('messages', [
            'external_id' => 'message-123',
            'conversation_id' => 'chat-123',
            'reply' => $welcome,
        ]);
    }

    public function test_it_gives_gemini_conversation_history_and_published_articles(): void
    {
        Config::set('services.gemini.api_key', 'gemini-test-key');

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => 'Le modèle Standard coûte 15 000 FCFA.']]]],
                ],
            ]),
        ]);

        Message::create([
            'body' => 'Je cherche un t-shirt.',
            'source' => 'site',
            'conversation_id' => 'chat-123',
            'status' => Message::STATUS_REPLIED,
            'reply' => 'Quel article vous intéresse ?',
        ]);

        $category = Category::create([
            'name' => 'Vêtements',
            'slug' => 'vetements',
        ]);
        Article::create([
            'category_id' => $category->id,
            'title' => 'T-shirt basique',
            'slug' => 't-shirt-basique',
            'excerpt' => 'T-shirt en coton.',
            'content' => 'Variantes et tarifs : Standard : 15 000 FCFA; Premium : 25 000 FCFA.',
            'status' => 'published',
        ]);

        $response = $this->withHeader('X-Integration-Token', 'integration-secret')
            ->postJson('/api/messages/incoming', [
                'sender_name' => 'Awa',
                'sender_email' => 'awa@example.com',
                'body' => 'Je choisis le T-shirt basique.',
                'source' => 'site',
                'external_id' => 'message-124',
                'conversation_id' => 'chat-123',
            ]);

        $response->assertCreated()
            ->assertJsonPath('status', Message::STATUS_REPLIED)
            ->assertJsonPath('reply', 'Le modèle Standard coûte 15 000 FCFA.');

        Http::assertSent(fn ($request): bool => str_contains(
            data_get($request->data(), 'contents.0.parts.0.text', ''),
            'T-shirt basique',
        ) && str_contains(
            data_get($request->data(), 'contents.0.parts.0.text', ''),
            'Je cherche un t-shirt.',
        ) && str_contains(
            data_get($request->data(), 'contents.0.parts.0.text', ''),
            '15 000 FCFA',
        ) && str_contains(
            data_get($request->data(), 'systemInstruction.parts.0.text', ''),
            'liste à puces',
        ));

        $this->assertDatabaseHas('messages', [
            'external_id' => 'message-124',
            'status' => Message::STATUS_REPLIED,
            'reply' => 'Le modèle Standard coûte 15 000 FCFA.',
        ]);
    }

    public function test_it_rejects_an_unauthenticated_integration(): void
    {
        $this->postJson('/api/messages/incoming', ['body' => 'Bonjour'])
            ->assertUnauthorized();

        $this->assertDatabaseCount('messages', 0);
    }

    public function test_it_keeps_the_message_and_reports_a_missing_gemini_key(): void
    {
        Config::set('services.gemini.api_key', null);

        Message::create([
            'body' => 'Bonjour',
            'source' => 'site',
            'conversation_id' => 'chat-456',
            'status' => Message::STATUS_REPLIED,
            'reply' => 'Bonjour et bienvenue.',
        ]);

        $this->withHeader('X-Integration-Token', 'integration-secret')
            ->postJson('/api/messages/incoming', [
                'body' => 'Quels sont vos tarifs ?',
                'external_id' => 'message-without-key',
                'conversation_id' => 'chat-456',
            ])
            ->assertStatus(503)
            ->assertJsonPath('status', Message::STATUS_FAILED);

        $this->assertDatabaseHas('messages', [
            'external_id' => 'message-without-key',
            'status' => Message::STATUS_FAILED,
        ]);
    }

    public function test_it_returns_the_existing_reply_for_a_retried_integration_event(): void
    {
        Config::set('services.gemini.api_key', 'gemini-test-key');

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => 'Réponse.']]]],
                ],
            ]),
        ]);

        Message::create([
            'body' => 'Bonjour',
            'source' => 'site',
            'conversation_id' => 'chat-retry',
            'status' => Message::STATUS_REPLIED,
            'reply' => 'Bonjour et bienvenue.',
        ]);

        $payload = [
            'body' => 'Je veux connaître les prix.',
            'source' => 'site',
            'external_id' => 'retry-safe-message',
            'conversation_id' => 'chat-retry',
        ];

        $this->withHeader('X-Integration-Token', 'integration-secret')
            ->postJson('/api/messages/incoming', $payload)
            ->assertCreated();

        $this->withHeader('X-Integration-Token', 'integration-secret')
            ->postJson('/api/messages/incoming', $payload)
            ->assertOk()
            ->assertJsonPath('reply', 'Réponse.');

        Http::assertSentCount(1);
        $this->assertDatabaseCount('messages', 2);
    }
}

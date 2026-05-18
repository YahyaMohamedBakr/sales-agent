<?php

namespace Tests\Unit\Domains\Agent;

use App\Domains\Agent\Agents\SupportAgent;
use App\Domains\Agent\Contracts\SmartRouterInterface;
use App\Domains\Agent\DTOs\AnalysisResult;
use App\Domains\Agent\DTOs\AIResponse;
use App\Domains\Agent\DTOs\ModelConfig;
use App\Domains\Agent\Enums\AIProvider;
use App\Domains\Conversation\Models\Conversation;
use App\Domains\Conversation\Repositories\ConversationRepositoryInterface;
use App\Domains\Lead\Models\Lead;
use App\Domains\Lead\Repositories\LeadRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Collection;
use Mockery;
use Tests\TestCase;

class SupportAgentTest extends TestCase
{
    use RefreshDatabase;

    private SmartRouterInterface|Mockery\MockInterface $router;
    private LeadRepositoryInterface|Mockery\MockInterface $leads;
    private ConversationRepositoryInterface|Mockery\MockInterface $conversations;
    private SupportAgent $agent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->router = Mockery::mock(SmartRouterInterface::class);
        $this->leads = Mockery::mock(LeadRepositoryInterface::class);
        $this->conversations = Mockery::mock(ConversationRepositoryInterface::class);

        $this->agent = new SupportAgent(
            $this->router,
            $this->leads,
            $this->conversations,
        );
    }

    public function test_returns_name(): void
    {
        $this->assertEquals('support', $this->agent->name());
    }

    public function test_should_handle_escalated_lead(): void
    {
        $lead = Lead::factory()->make(['status' => 'escalated']);

        $this->assertTrue($this->agent->shouldHandle('أي رسالة', ['lead' => $lead]));
    }

    public function test_should_handle_human_keyword(): void
    {
        $lead = Lead::factory()->make(['status' => 'new']);

        $this->assertTrue($this->agent->shouldHandle('عاوز موظف يتصل بي', ['lead' => $lead]));
    }

    public function test_should_handle_agent_keyword(): void
    {
        $lead = Lead::factory()->make(['status' => 'new']);

        $this->assertTrue($this->agent->shouldHandle('I want to speak to a human agent', ['lead' => $lead]));
    }

    public function test_should_not_handle_normal_message(): void
    {
        $lead = Lead::factory()->make(['status' => 'new']);

        $this->assertFalse($this->agent->shouldHandle('كم سعر المنتج؟', ['lead' => $lead]));
    }

    public function test_should_not_handle_without_lead(): void
    {
        $this->assertFalse($this->agent->shouldHandle('عاوز موظف'));
    }

    public function test_handle_escalates_lead_and_returns_fallback_reply(): void
    {
        $lead = Lead::factory()->make([
            'id' => 'lead-1',
            'name' => 'أحمد',
            'status' => 'new',
        ]);

        $this->conversations
            ->shouldReceive('logMessage')
            ->twice()
            ->andReturn(new Conversation);

        $this->leads
            ->shouldReceive('markAs')
            ->once()
            ->with('lead-1', 'escalated');

        $this->conversations
            ->shouldReceive('findRecentByLead')
            ->once()
            ->with('lead-1', 5)
            ->andReturn(new Collection);

        $this->router
            ->shouldReceive('chat')
            ->once()
            ->andReturn(new AIResponse(
                content: 'شكراً لتواصلك! تم تحويل طلبك لفريق الدعم.',
                model: 'gpt-4o',
                provider: AIProvider::OpenAI->value,
                inputTokens: 30,
                outputTokens: 15,
                processingTimeMs: 400,
            ));

        $reply = $this->agent->handle('عاوز موظف يكلمني', ['lead' => $lead]);

        $this->assertEquals('شكراً لتواصلك! تم تحويل طلبك لفريق الدعم.', $reply);
    }

    public function test_handle_returns_empty_without_lead(): void
    {
        $this->assertEquals('', $this->agent->handle('رسالة'));
    }

    public function test_handle_uses_fallback_when_llm_fails(): void
    {
        $lead = Lead::factory()->make([
            'id' => 'lead-2',
            'name' => 'محمد',
            'status' => 'new',
        ]);

        $this->conversations
            ->shouldReceive('logMessage')
            ->twice()
            ->andReturn(new Conversation);

        $this->leads
            ->shouldReceive('markAs')
            ->once()
            ->with('lead-2', 'escalated');

        $this->conversations
            ->shouldReceive('findRecentByLead')
            ->once()
            ->with('lead-2', 5)
            ->andReturn(new Collection);

        $this->router
            ->shouldReceive('chat')
            ->once()
            ->andThrow(new \RuntimeException('LLM unavailable'));

        $reply = $this->agent->handle('كلمني ضروري', ['lead' => $lead]);

        $this->assertStringContainsString('فريق الدعم البشري', $reply);
    }

    public function test_skips_escalation_if_already_escalated(): void
    {
        $lead = Lead::factory()->make([
            'id' => 'lead-3',
            'name' => 'سارة',
            'status' => 'escalated',
        ]);

        $this->conversations
            ->shouldReceive('logMessage')
            ->twice()
            ->andReturn(new Conversation);

        $this->conversations
            ->shouldReceive('findRecentByLead')
            ->once()
            ->with('lead-3', 5)
            ->andReturn(new Collection);

        $this->router
            ->shouldReceive('chat')
            ->once()
            ->andReturn(new AIResponse(
                content: 'نعتذر على التأخير، فريقنا سيتواصل معك قريباً.',
                model: 'gpt-4o',
                provider: AIProvider::OpenAI->value,
                inputTokens: 25,
                outputTokens: 12,
                processingTimeMs: 300,
            ));

        $reply = $this->agent->handle('لسه منتظر', ['lead' => $lead]);

        $this->assertEquals('نعتذر على التأخير، فريقنا سيتواصل معك قريباً.', $reply);
    }

    public function test_get_and_set_model_config(): void
    {
        $config = new ModelConfig(
            provider: AIProvider::Groq,
            model: 'llama-3.1-70b',
            temperature: 0.5,
            maxTokens: 200,
        );

        $this->agent->setModelConfig($config);

        $this->assertSame($config, $this->agent->getModelConfig());
    }
}

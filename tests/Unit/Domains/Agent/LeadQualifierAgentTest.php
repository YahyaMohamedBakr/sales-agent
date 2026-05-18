<?php

namespace Tests\Unit\Domains\Agent;

use App\Domains\Agent\Agents\LeadQualifierAgent;
use App\Domains\Agent\Contracts\SmartRouterInterface;
use App\Domains\Agent\DTOs\AnalysisResult;
use App\Domains\Agent\DTOs\AIResponse;
use App\Domains\Agent\DTOs\ModelConfig;
use App\Domains\Agent\Enums\AIProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Domains\Conversation\Models\Conversation;
use App\Domains\Conversation\Repositories\ConversationRepositoryInterface;
use App\Domains\Lead\Enums\LeadScoreEvent;
use App\Domains\Lead\Models\Lead;
use App\Domains\Lead\Repositories\LeadRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Mockery;
use Tests\TestCase;

class LeadQualifierAgentTest extends TestCase
{
    use RefreshDatabase;

    private SmartRouterInterface|Mockery\MockInterface $router;
    private LeadRepositoryInterface|Mockery\MockInterface $leads;
    private ConversationRepositoryInterface|Mockery\MockInterface $conversations;
    private LeadQualifierAgent $agent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->router = Mockery::mock(SmartRouterInterface::class);
        $this->leads = Mockery::mock(LeadRepositoryInterface::class);
        $this->conversations = Mockery::mock(ConversationRepositoryInterface::class);

        $this->agent = new LeadQualifierAgent(
            $this->router,
            $this->leads,
            $this->conversations,
        );
    }

    public function test_returns_name(): void
    {
        $this->assertEquals('lead_qualifier', $this->agent->name());
    }

    public function test_should_handle_qualifiable_lead(): void
    {
        $lead = Lead::factory()->make(['status' => 'new']);

        $this->assertTrue($this->agent->shouldHandle('رسالة', ['lead' => $lead]));
    }

    public function test_should_not_handle_without_lead(): void
    {
        $this->assertFalse($this->agent->shouldHandle('رسالة'));
    }

    public function test_should_not_handle_qualified_lead(): void
    {
        $lead = Lead::factory()->make(['status' => 'qualified']);

        $this->assertFalse($this->agent->shouldHandle('رسالة', ['lead' => $lead]));
    }

    public function test_should_not_handle_converted_lead(): void
    {
        $lead = Lead::factory()->make(['status' => 'converted']);

        $this->assertFalse($this->agent->shouldHandle('رسالة', ['lead' => $lead]));
    }

    public function test_handle_logs_messages_and_returns_reply(): void
    {
        $lead = Lead::factory()->make([
            'id' => 'lead-1',
            'name' => null,
            'phone' => null,
            'email' => null,
            'score' => 0,
            'status' => 'new',
        ]);

        $analysis = new AnalysisResult(
            intent: 'information_request',
            language: 'ar',
            sentiment: 0.0,
            needsFollowUp: true,
            shouldDM: false,
        );

        $this->conversations
            ->shouldReceive('logMessage')
            ->twice()
            ->andReturn(new Conversation);

        $this->router
            ->shouldReceive('analyze')
            ->once()
            ->with('ابغى سعر المنتج')
            ->andReturn($analysis);

        $this->conversations
            ->shouldReceive('findRecentByLead')
            ->once()
            ->with('lead-1', 10)
            ->andReturn(new Collection);

        $this->conversations
            ->shouldReceive('findByLead')
            ->once()
            ->with('lead-1')
            ->andReturn(new Collection);

        $this->leads
            ->shouldReceive('update')
            ->once()
            ->with('lead-1', ['name' => 'ابغى']);

        $this->leads
            ->shouldReceive('updateScore')
            ->once()
            ->with('lead-1', 10);

        $this->router
            ->shouldReceive('chat')
            ->once()
            ->andReturn(new AIResponse(
                content: 'أهلاً بك! كيف أقدر أساعدك؟',
                model: 'gpt-4o',
                provider: AIProvider::OpenAI->value,
                inputTokens: 50,
                outputTokens: 20,
                processingTimeMs: 500,
            ));

        $reply = $this->agent->handle('ابغى سعر المنتج', ['lead' => $lead]);

        $this->assertEquals('أهلاً بك! كيف أقدر أساعدك؟', $reply);
    }

    public function test_handle_returns_empty_without_lead(): void
    {
        $this->assertEquals('', $this->agent->handle('رسالة'));
    }

    public function test_analyze_delegates_to_router(): void
    {
        $analysis = new AnalysisResult(
            intent: 'pricing_inquiry',
            language: 'ar',
            sentiment: 0.0,
            needsFollowUp: true,
            shouldDM: true,
        );

        $this->router
            ->shouldReceive('analyze')
            ->once()
            ->with('كم السعر؟')
            ->andReturn($analysis);

        $this->assertSame($analysis, $this->agent->analyze('كم السعر؟'));
    }

    public function test_get_and_set_model_config(): void
    {
        $config = new ModelConfig(
            provider: AIProvider::Groq,
            model: 'mixtral-8x7b',
            temperature: 0.3,
            maxTokens: 400,
        );

        $this->agent->setModelConfig($config);

        $this->assertSame($config, $this->agent->getModelConfig());
    }
}

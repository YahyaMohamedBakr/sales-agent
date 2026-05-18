<?php

namespace Tests\Unit\Domains\Agent;

use App\Domains\Agent\Agents\CommentReplyAgent;
use App\Domains\Agent\Contracts\SmartRouterInterface;
use App\Domains\Agent\DTOs\AnalysisResult;
use App\Domains\Agent\DTOs\AIResponse;
use App\Domains\Agent\DTOs\ModelConfig;
use App\Domains\Agent\Enums\AIProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Domains\Conversation\Repositories\ConversationRepositoryInterface;
use App\Domains\Integration\Contracts\SocialPlatformInterface;
use App\Domains\Lead\Models\Lead;
use App\Domains\Lead\Repositories\LeadRepositoryInterface;
use Mockery;
use Tests\TestCase;

class CommentReplyAgentTest extends TestCase
{
    use RefreshDatabase;

    private SmartRouterInterface|Mockery\MockInterface $router;
    private SocialPlatformInterface|Mockery\MockInterface $platform;
    private LeadRepositoryInterface|Mockery\MockInterface $leads;
    private ConversationRepositoryInterface|Mockery\MockInterface $conversations;
    private CommentReplyAgent $agent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->router = Mockery::mock(SmartRouterInterface::class);
        $this->platform = Mockery::mock(SocialPlatformInterface::class);
        $this->leads = Mockery::mock(LeadRepositoryInterface::class);
        $this->conversations = Mockery::mock(ConversationRepositoryInterface::class);

        $this->agent = new CommentReplyAgent(
            $this->router,
            $this->platform,
            $this->leads,
            $this->conversations,
        );
    }

    public function test_returns_name(): void
    {
        $this->assertEquals('comment_reply', $this->agent->name());
    }

    public function test_should_handle_valid_message(): void
    {
        $this->assertTrue($this->agent->shouldHandle('كم سعر المنتج؟'));
    }

    public function test_should_not_handle_empty_message(): void
    {
        $this->assertFalse($this->agent->shouldHandle(''));
    }

    public function test_should_not_handle_short_message(): void
    {
        $this->assertFalse($this->agent->shouldHandle('ا'));
    }

    public function test_should_not_handle_spam_keywords(): void
    {
        $this->assertFalse($this->agent->shouldHandle('check out my spam link'));
    }

    public function test_should_not_handle_scam_keywords(): void
    {
        $this->assertFalse($this->agent->shouldHandle('free scam money'));
    }

    public function test_analyze_delegates_to_router(): void
    {
        $analysis = new AnalysisResult(
            intent: 'pricing_inquiry',
            language: 'ar',
            sentiment: 0.0,
            needsFollowUp: true,
            shouldDM: true,
            suggestedReply: 'السعر ٥٠٠ ريال',
        );

        $this->router
            ->shouldReceive('analyze')
            ->once()
            ->with('كم سعر المنتج؟', AIProvider::OpenAI)
            ->andReturn($analysis);

        $result = $this->agent->analyze('كم سعر المنتج؟');

        $this->assertSame($analysis, $result);
        $this->assertEquals('pricing_inquiry', $result->intent);
    }

    public function test_handle_uses_analysis_suggested_reply(): void
    {
        $lead = Lead::factory()->make(['id' => 'lead-1', 'psid' => 'psid-123']);
        $analysis = new AnalysisResult(
            intent: 'greeting',
            language: 'ar',
            sentiment: 0.5,
            needsFollowUp: false,
            shouldDM: false,
            suggestedReply: 'أهلاً بك!',
        );

        $this->router
            ->shouldReceive('analyze')
            ->once()
            ->andReturn($analysis);

        $this->platform
            ->shouldReceive('replyToComment')
            ->once()
            ->with('comment-123', 'أهلاً بك!');

        $this->conversations
            ->shouldReceive('logMessage')
            ->twice();

        $reply = $this->agent->handle('مرحباً', [
            'comment_id' => 'comment-123',
            'lead' => $lead,
        ]);

        $this->assertEquals('أهلاً بك!', $reply);
    }

    public function test_handle_sends_dm_when_analysis_indicates(): void
    {
        $lead = Lead::factory()->make(['id' => 'lead-1', 'psid' => 'psid-123', 'name' => 'أحمد']);
        $analysis = new AnalysisResult(
            intent: 'pricing_inquiry',
            language: 'ar',
            sentiment: 0.0,
            needsFollowUp: true,
            shouldDM: true,
            suggestedReply: 'أرسلنا لك رسالة خاصة',
        );

        $this->router
            ->shouldReceive('analyze')
            ->once()
            ->andReturn($analysis);

        $this->platform
            ->shouldReceive('replyToComment')
            ->once()
            ->with('comment-123', 'أرسلنا لك رسالة خاصة');

        $this->platform
            ->shouldReceive('sendMessage')
            ->once()
            ->with('psid-123', Mockery::type('string'));

        $this->conversations
            ->shouldReceive('logMessage')
            ->times(3);

        $this->agent->handle('كم سعره؟', [
            'comment_id' => 'comment-123',
            'lead' => $lead,
            'from_name' => 'أحمد',
        ]);
    }

    public function test_get_and_set_model_config(): void
    {
        $config = new ModelConfig(
            provider: AIProvider::Anthropic,
            model: 'claude-3-haiku',
            temperature: 0.5,
            maxTokens: 200,
        );

        $this->agent->setModelConfig($config);

        $this->assertSame($config, $this->agent->getModelConfig());
    }
}

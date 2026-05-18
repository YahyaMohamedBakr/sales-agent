<?php

namespace App\Domains\Agent\Services;

use App\Domains\Agent\Agents\CommentReplyAgent;
use App\Domains\Agent\Agents\LeadQualifierAgent;
use App\Domains\Agent\Contracts\AgentInterface;
use App\Domains\Lead\Models\Lead;
use App\Domains\Lead\Repositories\LeadRepositoryInterface;
use Illuminate\Support\Facades\Log;

class Orchestrator
{
    /** @var array<string, AgentInterface> */
    private array $agents = [];

    public function __construct(
        private LeadRepositoryInterface $leads,
    ) {}

    public function registerAgent(AgentInterface $agent): void
    {
        $this->agents[$agent->name()] = $agent;
    }

    public function handleComment(array $event): void
    {
        $lead = $this->resolveLead($event);
        if (!$lead) return;

        $agent = $this->agents['comment_reply'] ?? null;
        if (!$agent) {
            Log::warning('No comment_reply agent registered');
            return;
        }

        if (!$agent->shouldHandle($event['message'])) {
            Log::debug('Comment not relevant, skipping', ['msg' => $event['message']]);
            return;
        }

        $response = $agent->handle($event['message'], [
            'lead' => $lead,
            'comment_id' => $event['comment_id'],
            'post_id' => $event['post_id'] ?? null,
            'from_name' => $event['from_name'] ?? '',
        ]);

        Log::info('Orchestrator: comment handled', [
            'lead_id' => $lead->id,
            'agent' => $agent->name(),
            'response_length' => strlen($response),
        ]);
    }

    public function handleMessage(array $event): void
    {
        $lead = $this->resolveLead($event);
        if (!$lead) return;

        $agent = $this->agents['lead_qualifier'] ?? $this->agents['comment_reply'] ?? null;
        if (!$agent) {
            Log::warning('No agent available for message');
            return;
        }

        $response = $agent->handle($event['message'], [
            'lead' => $lead,
            'channel' => 'messenger',
            'sender_id' => $event['sender_id'] ?? '',
        ]);

        Log::info('Orchestrator: message handled', [
            'lead_id' => $lead->id,
            'agent' => $agent->name(),
        ]);
    }

    public function handlePostback(array $event): void
    {
        Log::info('Orchestrator: postback received', [
            'payload' => $event['payload'] ?? '',
            'sender_id' => $event['sender_id'] ?? '',
        ]);
    }

    private function resolveLead(array $event): ?Lead
    {
        $psid = $event['from_id'] ?? $event['sender_id'] ?? null;

        if (!$psid) {
            Log::warning('No sender ID in event');
            return null;
        }

        $lead = $this->leads->findByPsid($psid);

        if (!$lead) {
            $lead = $this->leads->create([
                'psid' => $psid,
                'name' => $event['from_name'] ?? null,
                'source' => $event['type'] ?? 'comment',
                'status' => 'new',
            ]);

            Log::info('Orchestrator: new lead created', ['psid' => $psid]);
        }

        return $lead;
    }

    public function getAgents(): array
    {
        return $this->agents;
    }
}

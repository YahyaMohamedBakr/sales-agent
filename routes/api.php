<?php

use App\Http\Controllers\Api\AgentController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\CampaignController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\KnowledgeBaseController;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\TokenController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('leads', LeadController::class);
    Route::post('leads/{id}/fields', [LeadController::class, 'addField']);

    Route::apiResource('campaigns', CampaignController::class);
    Route::get('campaigns/{id}/stats', [CampaignController::class, 'stats']);

    Route::get('leads/{lead}/conversations', [ConversationController::class, 'index']);
    Route::post('leads/{lead}/conversations', [ConversationController::class, 'store']);

    Route::apiResource('knowledge-base', KnowledgeBaseController::class);
    Route::get('knowledge-categories', [KnowledgeBaseController::class, 'categories']);

    Route::get('agent/health', [AgentController::class, 'health']);
    Route::get('agent/health/full', [AgentController::class, 'fullHealth']);
    Route::post('agent/chat', [AgentController::class, 'chat']);
    Route::post('agent/analyze', [AgentController::class, 'analyze']);

    Route::get('analytics/overview', [AnalyticsController::class, 'overview']);
    Route::get('analytics/leads-by-source', [AnalyticsController::class, 'leadsBySource']);
    Route::get('analytics/leads-by-day', [AnalyticsController::class, 'leadsByDay']);
    Route::get('analytics/leads-by-status', [AnalyticsController::class, 'leadsByStatus']);
    Route::get('analytics/top-campaigns', [AnalyticsController::class, 'topCampaigns']);
    Route::get('analytics/agent-performance', [AnalyticsController::class, 'agentPerformance']);

    Route::get('settings', [\App\Http\Controllers\Api\SettingsController::class, 'index']);
    Route::put('settings', [\App\Http\Controllers\Api\SettingsController::class, 'update']);

    Route::post('tokens', [TokenController::class, 'store']);
    Route::delete('tokens/{id}', [TokenController::class, 'destroy']);
});

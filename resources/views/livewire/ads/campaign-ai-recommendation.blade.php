<td class="ai-col td-break-col" @if ($column === 'rec' && $polling) wire:poll.2s="checkStatus" @endif data-col="ai_recommendation">
    @if ($column === 'rec')
        {{-- Recommendation cell --}}
        <span
            @if ($clickable) wire:click="generate"
                class="text-decoration-none"
                style="cursor: pointer;"
            @else
                class="text-muted"
                style="cursor: default;" @endif>
            @if ($aiStatus === 'pending')
                ⏳ Generating...
            @elseif ($aiStatus === 'done')
                {{ $aiRecommendation }}
            @elseif ($aiStatus === 'failed')
                ⚠️ Failed — Click to retry
            @elseif ($aiStatus === null)
                ✨Ai Generate
            @else
                <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
            @endif
        </span>
    @elseif ($column === 'budget')
        {{-- Budget cell --}}
        @if (is_numeric($aiSuggestedBudget))
            ${{ number_format($aiSuggestedBudget, 2) }}
        @else
            {{ $aiSuggestedBudget ?? '--' }}
        @endif
    @endif
</td>

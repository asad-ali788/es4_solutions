<?php

namespace App\Livewire\Campaign;

use Livewire\Component;
use App\Models\CampaignDraft;
use Illuminate\Support\Facades\Auth;

class CampaignDraftsList extends Component
{
    public string $status = 'all';

    public function render()
    {
        $q = CampaignDraft::query()
            ->where('user_id', Auth::id())
            ->where('status', '!=', 'submitted')
            ->orderByDesc('updated_at');

        if ($this->status !== 'all') {
            $q->where('status', $this->status);
        }

        return view('livewire.campaign.campaign-drafts-list', [
            'drafts' => $q->get(),
        ]);
    }
}

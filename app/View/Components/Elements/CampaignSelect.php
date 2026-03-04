<?php

namespace App\View\Components\Elements;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class CampaignSelect extends Component
{
    public $campaigns;

    public function __construct($campaigns = [])
    {
        $this->campaigns = $campaigns;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.elements.campaign-select');
    }
}

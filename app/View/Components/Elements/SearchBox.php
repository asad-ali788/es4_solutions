<?php

namespace App\View\Components\Elements;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class SearchBox extends Component
{
    public $name;
    public $placeholder;

    public function __construct($name = 'search', $placeholder = 'Enter to Search ...')
    {
        $this->name = $name;
        $this->placeholder = $placeholder;
    }

    public function render(): View|Closure|string
    {
        return view('components.elements.search-box');
    }
}

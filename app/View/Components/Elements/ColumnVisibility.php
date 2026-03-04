<?php

namespace App\View\Components\Elements;

use Illuminate\View\Component;

class ColumnVisibility extends Component
{
    public array $columns;
    public array $defaultVisible;
    public string $modalId;

    public function __construct(
        string $modalId        = 'columnFilterPopupModal',
        array  $columns        = [],
        array  $defaultVisible = []
    ) {
        $this->modalId        = $modalId;
        $this->columns        = $columns;
        $this->defaultVisible = $defaultVisible;
    }

    public function render()
    {
        return view('components.elements.column-visibility');
    }
}

<?php

namespace App\Services\Ai;

use Illuminate\Support\Str;

class AiResponseSimplified
{
    /**
     * Format AI response - always treat as Markdown and convert to HTML
     * @return array{content:string,is_html:bool}
     */
    public function formatMessage(string $role, string $content): array
    {
        if ($role === 'user') {
            return ['content' => $content, 'is_html' => false];
        }

        $trim = trim($content);
        if ($trim === '') {
            return ['content' => '', 'is_html' => false];
        }

        // All AI responses are in Markdown - convert to HTML
        $html = Str::markdown($content);
        
        return ['content' => $html, 'is_html' => true];
    }
}

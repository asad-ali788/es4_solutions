<?php

namespace App\Services\Ai;

use Illuminate\Support\Str;

class AiResponseSimplified
{
    /**
     * Format AI response — strip any native <think> tokens, convert markdown to HTML.
     *
     * @return array{content:string,is_html:bool,reasoning:null}
     */
    public function formatMessage(string $role, string $content): array
    {
        if ($role === 'user') {
            return ['content' => $content, 'is_html' => false, 'reasoning' => null];
        }

        $trim = trim($content);
        if ($trim === '') {
            return ['content' => '', 'is_html' => false, 'reasoning' => null];
        }

        // Strip native <think>...</think> blocks (closed and unclosed) emitted by some models
        $clean = preg_replace('/<think[^>]*>.*?<\/think>/is', '', $content) ?? $content;
        $clean = preg_replace('/<think[^>]*>.*$/is', '', $clean) ?? $clean;
        $clean = trim($clean);

        if ($clean === '') {
            return ['content' => '', 'is_html' => false, 'reasoning' => null];
        }

        return ['content' => Str::markdown($clean), 'is_html' => true, 'reasoning' => null];
    }
}

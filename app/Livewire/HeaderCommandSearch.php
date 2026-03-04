<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Cache;

class HeaderCommandSearch extends Component
{
    public string $query = '';
    public bool $open = false;

    /** @var array<string, array<int, array>> */
    public array $results = [];

    /** @var array<int, array> */
    public array $recentItems = [];

    public int $minChars = 2;
    public int $maxPerModule = 6;

    // Recent settings
    public int $recentLimit = 5;
    public int $recentTtlDays = 60; // cache lifetime for "recent clicks"

    protected function cacheKey(): string
    {
        $uid = auth()->id() ?? 0; // if guest, all guests share same recents (you can change later)
        return "cmd_recent_clicks_user_{$uid}";
    }

    protected function loadRecent(): void
    {
        $this->recentItems = Cache::get($this->cacheKey(), []);
        if (!is_array($this->recentItems)) {
            $this->recentItems = [];
        }
    }

    public function openDropdown(): void
    {
        $this->open = true;

        // always refresh recents when opening
        $this->loadRecent();

        // if user hasn’t typed enough, don’t show results list
        if (mb_strlen(trim($this->query)) < $this->minChars) {
            $this->results = [];
        }
    }

    public function close(): void
    {
        $this->open = false;
    }

    public function updatedQuery(): void
    {
        $this->query = trim($this->query);
        $this->open = true;

        // keep recents available while typing
        $this->loadRecent();

        if (mb_strlen($this->query) < $this->minChars) {
            $this->results = [];
            return;
        }

        $q = mb_strtolower($this->query);

        $items = config('command_palette.items', []);
        if (!is_array($items)) {
            $this->results = [];
            return;
        }

        $matched = [];
        foreach ($items as $item) {
            if (!is_array($item)) continue;

            $label = (string)($item['label'] ?? '');
            $keywords = $item['keywords'] ?? [];
            if (!is_array($keywords)) $keywords = [];

            $pool = mb_strtolower($label . ' | ' . implode(' | ', array_map('strval', $keywords)));

            if (str_contains($pool, $q)) {
                $item['module'] = (string)($item['module'] ?? 'Other');
                $item['order']  = (int)($item['order'] ?? 999);
                $item['id']     = (string)($item['id'] ?? md5($item['module'] . '|' . $label . '|' . ($item['url'] ?? '')));
                $item['url']    = (string)($item['url'] ?? '');
                $matched[] = $item;
            }
        }

        usort($matched, fn($a, $b) => ($a['order'] ?? 999) <=> ($b['order'] ?? 999));

        $grouped = [];
        foreach ($matched as $item) {
            $module = $item['module'] ?? 'Other';
            $grouped[$module] ??= [];

            if (count($grouped[$module]) < $this->maxPerModule) {
                $grouped[$module][] = $item;
            }
        }

        $this->results = $grouped;
    }

    /**
     * Save clicked item to "Recent" (unique, newest first, limit = 5)
     */
    protected function rememberClick(array $clicked): void
    {
        $clickedId = (string)($clicked['id'] ?? '');
        if ($clickedId === '') return;

        $recents = Cache::get($this->cacheKey(), []);
        if (!is_array($recents)) $recents = [];

        // remove same item if exists
        $recents = array_values(array_filter($recents, function ($r) use ($clickedId) {
            return (string)($r['id'] ?? '') !== $clickedId;
        }));

        // prepend newest
        array_unshift($recents, [
            'id'     => $clickedId,
            'label'  => (string)($clicked['label'] ?? ''),
            'module' => (string)($clicked['module'] ?? 'Other'),
            'url'    => (string)($clicked['url'] ?? ''),
        ]);

        // limit to 5
        $recents = array_slice($recents, 0, $this->recentLimit);

        Cache::put($this->cacheKey(), $recents, now()->addDays($this->recentTtlDays));

        // refresh local state too
        $this->recentItems = $recents;
    }

    public function enter()
    {
        foreach ($this->results as $items) {
            if (!empty($items[0]['url'])) {
                $first = $items[0];

                // store as recent (clicked/entered)
                $this->rememberClick($first);

                $this->open = false;
                return redirect()->to($first['url']);
            }
        }

        return null;
    }

    /**
     * One single click handler:
     * store in recents + redirect
     */
    public function go(string $url, ?string $id = null, ?string $label = null, ?string $module = null)
    {
        $url = trim($url);
        if ($url === '') return null;

        $this->rememberClick([
            'id'     => $id ?? md5(($module ?? '') . '|' . ($label ?? '') . '|' . $url),
            'label'  => $label ?? '',
            'module' => $module ?? 'Other',
            'url'    => $url,
        ]);

        $this->open = false;
        return redirect()->to($url);
    }

    public function render()
    {
        return view('livewire.header-command-search');
    }
}

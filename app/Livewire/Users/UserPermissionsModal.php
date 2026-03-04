<?php

namespace App\Livewire\Users;

use Livewire\Component;
use App\Models\User;
use App\Models\Permission;


class UserPermissionsModal extends Component
{
    public bool $show = false;
    public bool $loaded = false;

    public int $userId;
    public string $userName = '';

    public array $groupedPermissions = [];

    protected $listeners = [
        'openPermissionsModal' => 'open',
    ];

    public function open(int $userId): void
    {
        $this->reset(['groupedPermissions']);
        $this->show   = true;
        $this->loaded = false;

        $this->userId = $userId;

        $user = User::findOrFail($this->userId);
        $this->userName = $user->name;

        $allPermissions = $user->getAllPermissions()->pluck('name');
        $denied = $user->revokedPermissions()->pluck('name');
        $allowedPermissions = $allPermissions->diff($denied);

        $permissionLabels = Permission::fetchAllStaticPermissions();

        $grouped = [];

        foreach ($permissionLabels as $moduleKey => $moduleData) {
            foreach ($moduleData['permissions'] ?? [] as $permKey => $label) {
                if ($allowedPermissions->contains($permKey)) {
                    $grouped[$moduleKey]['label'] = $moduleData['label'] ?? ucfirst($moduleKey);
                    $grouped[$moduleKey]['permissions'][] = $label;
                }
            }
        }

        $this->groupedPermissions = $grouped;
        $this->loaded = true;
    }

    public function close(): void
    {
        $this->reset(['show', 'loaded', 'groupedPermissions']);
    }

    public function render()
    {
        return view('livewire.users.user-permissions-modal');
    }
}

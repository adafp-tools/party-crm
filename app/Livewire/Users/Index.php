<?php

namespace App\Livewire\Users;

use App\Models\OrgUnit;
use App\Models\RoleAssignment;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class Index extends Component
{
    public string $search = '';

    public bool $showModal = false;

    public function mount(): void
    {
        $this->authorize('viewAny', User::class);
    }

    public string $modalMode = 'create';

    public ?string $editingUserId = null;

    public string $userName = '';

    public string $userEmail = '';

    public string $userPhone = '';

    public string $userLocale = 'en';

    public string $userPassword = '';

    /** @var array<int, array{role_id: string, org_unit_id: string}> */
    public array $assignments = [['role_id' => '', 'org_unit_id' => '']];

    #[Computed]
    public function users(): \Illuminate\Database\Eloquent\Collection
    {
        return User::with(['roles', 'roleAssignments.role', 'roleAssignments.orgUnit'])
            ->when($this->search !== '', fn ($q) => $q->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%');
            }))
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function roles(): Collection
    {
        return Role::orderBy('name')->get();
    }

    #[Computed]
    public function orgUnits(): \Illuminate\Database\Eloquent\Collection
    {
        return OrgUnit::where('is_active', true)->orderBy('path')->get();
    }

    public function openCreateModal(): void
    {
        $this->authorize('create', User::class);
        $this->reset(['userName', 'userEmail', 'userPhone', 'userLocale', 'userPassword', 'editingUserId']);
        $this->assignments = [['role_id' => '', 'org_unit_id' => '']];
        $this->modalMode = 'create';
        $this->showModal = true;
    }

    public function openEditModal(string $userId): void
    {
        $user = User::findOrFail($userId);
        $this->authorize('update', $user);

        $this->editingUserId = $userId;
        $this->userName = $user->name;
        $this->userEmail = $user->email;
        $this->userPhone = $user->phone ?? '';
        $this->userLocale = $user->locale ?? 'en';
        $this->userPassword = '';

        $this->assignments = $user->roleAssignments
            ->map(fn ($ra) => [
                'role_id' => (string) $ra->role_id,
                'org_unit_id' => $ra->org_unit_id ?? '',
            ])
            ->values()
            ->all();

        if (empty($this->assignments)) {
            $this->assignments = [['role_id' => '', 'org_unit_id' => '']];
        }

        $this->modalMode = 'edit';
        $this->showModal = true;
    }

    public function addAssignment(): void
    {
        $this->assignments[] = ['role_id' => '', 'org_unit_id' => ''];
    }

    public function removeAssignment(int $index): void
    {
        unset($this->assignments[$index]);
        $this->assignments = array_values($this->assignments);
    }

    public function saveUser(): void
    {
        $rules = [
            'userName' => ['required', 'string', 'min:2', 'max:255'],
            'userLocale' => ['required', 'in:en,fr,am'],
            'assignments' => ['array'],
            'assignments.*.role_id' => ['required_with:assignments.*.org_unit_id', 'exists:roles,id'],
            'assignments.*.org_unit_id' => ['nullable', 'exists:org_units,id'],
        ];

        if ($this->modalMode === 'create') {
            $rules['userEmail'] = ['required', 'email', 'unique:users,email'];
            $rules['userPassword'] = ['required', 'string', 'min:12'];
        } else {
            $rules['userEmail'] = ['required', 'email', 'unique:users,email,'.$this->editingUserId];
            $rules['userPassword'] = ['nullable', 'string', 'min:12'];
        }

        $this->validate($rules);

        if ($this->modalMode === 'create') {
            $this->authorize('create', User::class);

            $user = User::create([
                'name' => $this->userName,
                'email' => $this->userEmail,
                'phone' => $this->userPhone ?: null,
                'locale' => $this->userLocale,
                'password' => Hash::make($this->userPassword),
                'is_active' => true,
            ]);
        } else {
            $user = User::findOrFail($this->editingUserId);
            $this->authorize('update', $user);

            $data = [
                'name' => $this->userName,
                'email' => $this->userEmail,
                'phone' => $this->userPhone ?: null,
                'locale' => $this->userLocale,
            ];

            if ($this->userPassword) {
                $data['password'] = Hash::make($this->userPassword);
            }

            $user->update($data);
        }

        $this->syncAssignments($user);

        $this->showModal = false;
        session()->flash('success', __('User saved.'));
    }

    private function syncAssignments(User $user): void
    {
        $roleIds = [];

        RoleAssignment::where('user_id', $user->id)->delete();

        foreach ($this->assignments as $assignment) {
            if (empty($assignment['role_id'])) {
                continue;
            }

            $roleId = (int) $assignment['role_id'];
            $orgUnitId = $assignment['org_unit_id'] ?: null;

            RoleAssignment::create([
                'user_id' => $user->id,
                'role_id' => $roleId,
                'org_unit_id' => $orgUnitId,
            ]);

            $roleIds[] = $roleId;
        }

        $roles = Role::whereIn('id', array_unique($roleIds))->pluck('name')->all();
        $user->syncRoles($roles);
    }

    public function deactivateUser(string $userId): void
    {
        $user = User::findOrFail($userId);
        $this->authorize('update', $user);

        $user->update(['is_active' => false]);

        $user->tokens()->delete();
        DB::table('sessions')->where('user_id', $user->id)->delete();

        session()->flash('success', __('User deactivated and sessions revoked.'));
    }

    public function render(): View
    {
        return view('livewire.users.index')->layout('layouts.app', [
            'title' => __('Users'),
        ]);
    }
}

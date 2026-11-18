<?php

namespace App\Livewire\Members;

use App\Models\AuditLog;
use App\Models\Member;
use App\Models\OrgUnit;
use App\Services\BlindIndexService;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $filterStatus = '';

    public string $filterCategory = '';

    public string $filterGender = '';

    public string $filterUnit = '';

    public string $filterWing = '';

    public string $joinedFrom = '';

    public string $joinedTo = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'filterStatus' => ['except' => '', 'as' => 'status'],
        'filterCategory' => ['except' => '', 'as' => 'category'],
        'filterGender' => ['except' => '', 'as' => 'gender'],
        'filterUnit' => ['except' => '', 'as' => 'unit'],
        'filterWing' => ['except' => '', 'as' => 'wing'],
        'joinedFrom' => ['except' => '', 'as' => 'from'],
        'joinedTo' => ['except' => '', 'as' => 'to'],
    ];

    public function mount(): void
    {
        $this->authorize('viewAny', Member::class);
        $this->logListOpened();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilter(): void
    {
        $this->resetPage();
    }

    public function getOrgUnitsProperty()
    {
        return OrgUnit::where('is_active', true)->orderBy('name')->get();
    }

    public function getMembersProperty()
    {
        $bi = app(BlindIndexService::class);

        $query = Member::with('orgUnit')
            ->when($this->search, function ($q) use ($bi) {
                $term = $this->search;
                $hash = $bi->compute($term);
                $q->where(function ($inner) use ($term, $hash) {
                    $inner->where('first_name', 'like', "%{$term}%")
                        ->orWhere('last_name', 'like', "%{$term}%")
                        ->orWhere('membership_no', 'like', "%{$term}%")
                        ->orWhere('email_hash', $hash)
                        ->orWhere('phone_hash', $hash);
                });
            })
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterCategory, fn ($q) => $q->where('category', $this->filterCategory))
            ->when($this->filterGender, fn ($q) => $q->where('gender', $this->filterGender))
            ->when($this->filterUnit, fn ($q) => $q->where('org_unit_id', $this->filterUnit))
            ->when($this->filterWing === 'youth', fn ($q) => $q->where('is_youth_wing', true))
            ->when($this->filterWing === 'women', fn ($q) => $q->where('is_women_wing', true))
            ->when($this->joinedFrom, fn ($q) => $q->whereDate('joined_date', '>=', $this->joinedFrom))
            ->when($this->joinedTo, fn ($q) => $q->whereDate('joined_date', '<=', $this->joinedTo))
            ->orderBy('last_name')
            ->orderBy('first_name');

        return $query->paginate(50);
    }

    public function render()
    {
        return view('livewire.members.index', [
            'members' => $this->members,
            'orgUnits' => $this->orgUnits,
        ]);
    }

    private function logListOpened(): void
    {
        $filterKey = md5(json_encode([
            $this->filterStatus, $this->filterCategory, $this->filterGender,
            $this->filterUnit, $this->filterWing, $this->joinedFrom, $this->joinedTo,
        ]));
        $cacheKey = 'audit_member_list_'.auth()->id().'_'.$filterKey;

        if (! Cache::has($cacheKey)) {
            Cache::put($cacheKey, true, now()->addHour());
            AuditLog::record('member_list_opened', null, [
                'filters' => array_filter([
                    'status' => $this->filterStatus,
                    'category' => $this->filterCategory,
                    'unit' => $this->filterUnit,
                ]),
            ]);
        }
    }
}

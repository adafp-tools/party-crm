<?php

namespace App\Livewire\Dues;

use App\Models\DuesPayment;
use App\Models\OrgUnit;
use App\Services\DuesService;
use Livewire\Component;

class Arrears extends Component
{
    public string $filterUnit = '';

    public function mount(): void
    {
        $this->authorize('viewAny', DuesPayment::class);
    }

    public function getOrgUnitsProperty()
    {
        return OrgUnit::where('is_active', true)->orderBy('name')->get();
    }

    public function getArrearsProperty(): array
    {
        $service = app(DuesService::class);

        $query = OrgUnit::where('is_active', true)
            ->when($this->filterUnit, fn ($q) => $q->where('id', $this->filterUnit))
            ->orderBy('name');

        $rows = [];
        foreach ($query->get() as $unit) {
            $stats = $service->arrearsForUnit($unit->id);
            if ($stats['total'] > 0) {
                $rows[] = [
                    'unit' => $unit->name,
                    'total' => $stats['total'],
                    'paid' => $stats['paid'],
                    'owing' => $stats['owing'],
                    'pct' => $stats['total'] > 0 ? round($stats['paid'] / $stats['total'] * 100) : 0,
                ];
            }
        }

        usort($rows, fn ($a, $b) => $a['pct'] <=> $b['pct']);

        return $rows;
    }

    public function render()
    {
        return view('livewire.dues.arrears', [
            'arrears' => $this->arrears,
            'orgUnits' => $this->orgUnits,
        ]);
    }
}

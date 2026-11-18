<?php

namespace App\Livewire\Members;

use App\Jobs\ImportMembersJob;
use App\Models\Member;
use App\Models\OrgUnit;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Livewire\WithFileUploads;

class ImportWizard extends Component
{
    use WithFileUploads;

    public int $step = 1;

    public $csvFile = null;

    public array $headers = [];

    public array $mapping = [];

    public array $preview = [];

    public int $totalRows = 0;

    public string $orgUnitId = '';

    public ?string $jobId = null;

    protected array $availableColumns = [
        'first_name', 'middle_name', 'last_name', 'gender',
        'date_of_birth', 'email', 'phone', 'address_line',
        'occupation', 'category', 'joined_date',
    ];

    public function mount(): void
    {
        $this->authorize('create', Member::class);
    }

    public function getOrgUnitsProperty()
    {
        return OrgUnit::where('is_active', true)->orderBy('name')->get();
    }

    public function updatedCsvFile(): void
    {
        $this->validate(['csvFile' => 'required|file|mimes:csv,txt|max:10240']);

        $path = $this->csvFile->getRealPath();
        $handle = fopen($path, 'r');
        $this->headers = fgetcsv($handle) ?: [];

        $this->mapping = [];
        foreach ($this->headers as $header) {
            $normalized = strtolower(trim(str_replace([' ', '-'], '_', $header)));
            $this->mapping[$header] = in_array($normalized, $this->availableColumns) ? $normalized : '';
        }

        $this->preview = [];
        $count = 0;
        while (($row = fgetcsv($handle)) !== false) {
            if ($count < 20) {
                $this->preview[] = array_combine($this->headers, $row);
            }
            $count++;
        }
        fclose($handle);
        $this->totalRows = $count;
        $this->step = 2;
    }

    public function goToConfirm(): void
    {
        $this->validate(['orgUnitId' => 'required|exists:org_units,id']);
        $this->step = 3;
    }

    public function startImport(): void
    {
        $this->authorize('create', Member::class);

        $storedPath = $this->csvFile->store('imports', 'local');
        $this->jobId = uniqid('import_', true);

        ImportMembersJob::dispatch(
            $storedPath,
            $this->mapping,
            $this->orgUnitId,
            $this->jobId,
            auth()->id(),
        );

        $this->step = 4;
    }

    public function getProgressProperty(): array
    {
        if (! $this->jobId) {
            return ['processed' => 0, 'total' => $this->totalRows, 'done' => false, 'errors' => []];
        }

        return Cache::get('import_progress_'.$this->jobId, [
            'processed' => 0, 'total' => $this->totalRows, 'done' => false, 'errors' => [],
        ]);
    }

    public function render()
    {
        return view('livewire.members.import-wizard', [
            'orgUnits' => $this->orgUnits,
            'availableColumns' => $this->availableColumns,
            'progress' => $this->progress,
        ]);
    }
}

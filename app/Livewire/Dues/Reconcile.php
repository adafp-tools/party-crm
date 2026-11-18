<?php

namespace App\Livewire\Dues;

use App\Models\DuesPayment;
use App\Services\DuesService;
use Livewire\Component;
use Livewire\WithFileUploads;

class Reconcile extends Component
{
    use WithFileUploads;

    public int $step = 1;

    public $csvFile = null;

    public array $headers = [];

    public array $mapping = ['reference' => '', 'amount' => '', 'date' => ''];

    public array $preview = [];

    public array $matches = [];

    public function mount(): void
    {
        $this->authorize('viewAny', DuesPayment::class);
    }

    public function updatedCsvFile(): void
    {
        $this->validate(['csvFile' => 'required|file|mimes:csv,txt|max:5120']);

        $handle = fopen($this->csvFile->getRealPath(), 'r');
        $this->headers = fgetcsv($handle) ?: [];

        $this->preview = [];
        $count = 0;
        while (($row = fgetcsv($handle)) !== false && $count < 10) {
            $this->preview[] = array_combine($this->headers, $row);
            $count++;
        }
        fclose($handle);

        // Auto-map common column names
        foreach ($this->headers as $h) {
            $n = strtolower(trim($h));
            if (str_contains($n, 'ref') || str_contains($n, 'transaction')) {
                $this->mapping['reference'] = $h;
            }
            if (str_contains($n, 'amount') || str_contains($n, 'montant')) {
                $this->mapping['amount'] = $h;
            }
            if (str_contains($n, 'date')) {
                $this->mapping['date'] = $h;
            }
        }

        $this->step = 2;
    }

    public function findMatches(): void
    {
        $this->validate([
            'mapping.reference' => 'required',
            'mapping.amount' => 'required',
        ]);

        $handle = fopen($this->csvFile->getRealPath(), 'r');
        $headers = fgetcsv($handle);
        $this->matches = [];

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($headers, $row);
            $ref = trim($data[$this->mapping['reference']] ?? '');
            $amt = (float) str_replace([',', ' '], ['', ''], $data[$this->mapping['amount']] ?? '0');

            if (! $ref) {
                continue;
            }

            $payment = DuesPayment::with('member')
                ->confirmed()
                ->where('reference', $ref)
                ->first();

            $this->matches[] = [
                'csv_ref' => $ref,
                'csv_amount' => $amt,
                'csv_date' => $data[$this->mapping['date'] ?? ''] ?? null,
                'payment_id' => $payment?->id,
                'payment_amount' => $payment ? (float) $payment->amount : null,
                'member_name' => $payment?->member?->fullName(),
                'amount_match' => $payment && abs((float) $payment->amount - $amt) < 0.01,
                'accept' => $payment !== null,
            ];
        }
        fclose($handle);
        $this->step = 3;
    }

    public function acceptAll(): void
    {
        $this->authorize('viewAny', DuesPayment::class);
        $count = 0;
        foreach ($this->matches as $match) {
            if ($match['accept'] && $match['payment_id']) {
                DuesPayment::where('id', $match['payment_id'])->update([
                    'status' => 'reconciled',
                    'reconciled_by' => auth()->id(),
                    'reconciled_at' => now(),
                ]);
                $p = DuesPayment::find($match['payment_id']);
                if ($p) {
                    app(DuesService::class)->forgetCache($p->member);
                }
                $count++;
            }
        }
        session()->flash('success', __(':n payments reconciled.', ['n' => $count]));
        $this->step = 1;
        $this->matches = [];
        $this->csvFile = null;
    }

    public function render()
    {
        return view('livewire.dues.reconcile');
    }
}

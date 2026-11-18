<?php

namespace App\Jobs;

use App\Models\Member;
use App\Services\BlindIndexService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class ImportMembersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;

    public function __construct(
        private readonly string $storedPath,
        private readonly array $mapping,
        private readonly string $orgUnitId,
        private readonly string $jobId,
        private readonly string $importedBy,
    ) {}

    public function handle(BlindIndexService $bi): void
    {
        $path = Storage::disk('local')->path($this->storedPath);
        $handle = fopen($path, 'r');
        $headers = fgetcsv($handle) ?: [];

        $processed = 0;
        $errors = [];
        $total = 0;

        // Count total rows
        while (fgetcsv($handle) !== false) {
            $total++;
        }
        rewind($handle);
        fgetcsv($handle); // skip headers

        while (($row = fgetcsv($handle)) !== false) {
            $processed++;
            $data = array_combine($headers, $row);

            try {
                $mapped = ['org_unit_id' => $this->orgUnitId, 'status' => 'applicant', 'created_by' => $this->importedBy];

                foreach ($this->mapping as $csvCol => $dbCol) {
                    if ($dbCol && isset($data[$csvCol])) {
                        $mapped[$dbCol] = $data[$csvCol] ?: null;
                    }
                }

                if (empty($mapped['first_name']) || empty($mapped['last_name'])) {
                    $errors[] = "Row {$processed}: missing first_name or last_name";

                    continue;
                }

                $email = $mapped['email'] ?? null;
                $phone = $mapped['phone'] ?? null;
                $emailHash = $email ? $bi->compute($email) : null;
                $phoneHash = $phone ? $bi->compute($phone) : null;

                // Skip exact duplicate by contact info
                $isDuplicate = Member::withoutGlobalScopes()
                    ->where(function ($q) use ($emailHash, $phoneHash) {
                        if ($emailHash) {
                            $q->orWhere('email_hash', $emailHash);
                        }
                        if ($phoneHash) {
                            $q->orWhere('phone_hash', $phoneHash);
                        }
                    })
                    ->exists();

                if ($isDuplicate) {
                    $errors[] = "Row {$processed}: duplicate contact info skipped";

                    continue;
                }

                $mapped['consent_channel'] = 'paper';
                Member::create($mapped);
            } catch (\Throwable $e) {
                $errors[] = "Row {$processed}: ".$e->getMessage();
            }

            if ($processed % 50 === 0) {
                Cache::put("import_progress_{$this->jobId}", [
                    'processed' => $processed,
                    'total' => $total,
                    'done' => false,
                    'errors' => array_slice($errors, -20),
                ], now()->addHour());
            }
        }

        fclose($handle);
        Storage::disk('local')->delete($this->storedPath);

        Cache::put("import_progress_{$this->jobId}", [
            'processed' => $processed,
            'total' => $total,
            'done' => true,
            'errors' => $errors,
        ], now()->addDay());
    }
}

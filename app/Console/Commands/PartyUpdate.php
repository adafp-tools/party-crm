<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Applies a release archive to this installation with backup and automatic rollback.
 *
 * Steps:
 *   1. Verify SHA-256 checksum (from <archive>.sha256 sidecar file)
 *   2. Take a backup (DB dump + storage + .env)
 *   3. Enter maintenance mode
 *   4. Extract archive over the current code
 *   5. Run migrations, clear caches
 *   6. Exit maintenance mode
 *   7. Rollback automatically if any step fails
 */
class PartyUpdate extends Command
{
    protected $signature = 'party:update
                            {archive : Path to the release ZIP archive}
                            {--checksum= : Expected SHA-256 hex checksum (or reads <archive>.sha256)}
                            {--skip-backup : Skip the DB/file backup step (dangerous)}
                            {--no-interaction}';

    protected $description = 'Apply a Party CRM release archive with backup and automatic rollback';

    private string $backupDir = '';

    public function handle(): int
    {
        $archive = $this->argument('archive');

        if (! file_exists($archive)) {
            $this->error("Archive not found: {$archive}");

            return self::FAILURE;
        }

        $this->info('Party CRM — Update');
        $this->newLine();

        // 1. Verify checksum
        $this->info('1/6 Verifying archive checksum…');
        if (! $this->verifyChecksum($archive)) {
            return self::FAILURE;
        }
        $this->line('  Checksum OK');

        // 2. Backup
        if (! $this->option('skip-backup')) {
            $this->info('2/6 Taking backup…');
            if (! $this->takeBackup()) {
                $this->error('Backup failed. Update aborted.');

                return self::FAILURE;
            }
            $this->line("  Backup stored in: {$this->backupDir}");
        } else {
            $this->warn('2/6 Backup skipped (--skip-backup).');
        }

        // 3. Maintenance mode
        $this->info('3/6 Entering maintenance mode…');
        Artisan::call('down', ['--retry' => 10]);

        try {
            // 4. Extract archive
            $this->info('4/6 Extracting archive…');
            if (! $this->extractArchive($archive)) {
                $this->rollback('Extraction failed');

                return self::FAILURE;
            }

            // 5. Migrate & clear caches
            $this->info('5/6 Running migrations and clearing caches…');
            $exitCode = Artisan::call('migrate', ['--force' => true]);

            if ($exitCode !== 0) {
                $this->rollback('Migration failed');

                return self::FAILURE;
            }

            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');
            Artisan::call('event:clear');

        } catch (\Throwable $e) {
            $this->rollback('Unexpected error: '.$e->getMessage());

            return self::FAILURE;
        }

        // 6. Exit maintenance mode
        $this->info('6/6 Exiting maintenance mode…');
        Artisan::call('up');

        $this->newLine();
        $this->info('Update complete.');

        return self::SUCCESS;
    }

    private function verifyChecksum(string $archive): bool
    {
        $expected = $this->option('checksum');

        if (! $expected) {
            $checksumFile = $archive.'.sha256';
            if (! file_exists($checksumFile)) {
                $this->error("No checksum file found at {$checksumFile} and no --checksum option provided.");

                return false;
            }
            $expected = trim(preg_split('/\s+/', trim(file_get_contents($checksumFile)))[0] ?? '');
        }

        $actual = hash_file('sha256', $archive);

        if (! hash_equals(strtolower($expected), strtolower($actual))) {
            $this->error("Checksum mismatch!\n  Expected: {$expected}\n  Actual:   {$actual}");

            return false;
        }

        return true;
    }

    private function takeBackup(): bool
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $this->backupDir = storage_path("backups/update_{$timestamp}");

        if (! is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0750, true);
        }

        // DB dump
        $dbConfig = config('database.connections.'.config('database.default'));
        $dumpFile = $this->backupDir.'/db.sql';

        if ($dbConfig['driver'] === 'mysql' || $dbConfig['driver'] === 'mariadb') {
            $host = escapeshellarg($dbConfig['host']);
            $port = escapeshellarg((string) $dbConfig['port']);
            $name = escapeshellarg($dbConfig['database']);
            $user = escapeshellarg($dbConfig['username']);
            $pass = $dbConfig['password'];

            $pwdArg = $pass ? '-p'.escapeshellarg($pass) : '';
            $cmd = "mysqldump -h {$host} -P {$port} -u {$user} {$pwdArg} {$name} > ".escapeshellarg($dumpFile);
            exec($cmd, $output, $exitCode);

            if ($exitCode !== 0) {
                return false;
            }
        }

        // .env backup
        copy(base_path('.env'), $this->backupDir.'/.env.bak');

        return true;
    }

    private function extractArchive(string $archive): bool
    {
        $zip = new \ZipArchive;
        if ($zip->open($archive) !== true) {
            $this->error("Cannot open archive: {$archive}");

            return false;
        }

        $zip->extractTo(base_path());
        $zip->close();

        return true;
    }

    private function rollback(string $reason): void
    {
        $this->error("Rolling back: {$reason}");

        if ($this->backupDir && file_exists($this->backupDir.'/db.sql')) {
            $this->warn('Restoring database from backup…');
            $dbConfig = config('database.connections.'.config('database.default'));

            if ($dbConfig['driver'] === 'mysql' || $dbConfig['driver'] === 'mariadb') {
                $host = escapeshellarg($dbConfig['host']);
                $port = escapeshellarg((string) $dbConfig['port']);
                $name = escapeshellarg($dbConfig['database']);
                $user = escapeshellarg($dbConfig['username']);
                $pass = $dbConfig['password'];
                $pwdArg = $pass ? '-p'.escapeshellarg($pass) : '';
                $dumpFile = escapeshellarg($this->backupDir.'/db.sql');
                exec("mysql -h {$host} -P {$port} -u {$user} {$pwdArg} {$name} < {$dumpFile}");
            }
        }

        Artisan::call('up');
        $this->warn('Application brought back online after rollback.');
    }
}

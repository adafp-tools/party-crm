<?php

namespace App\Livewire\Install;

use App\Models\OrgLevel;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.install')]
class Wizard extends Component
{
    public int $step = 1;

    // Step 1: requirements (read-only)
    /** @var array<string, array{ok: bool, message: string}> */
    public array $requirements = [];

    // Step 2: database
    public string $dbHost = '127.0.0.1';

    public string $dbPort = '3306';

    public string $dbName = 'party_crm';

    public string $dbUser = 'root';

    public string $dbPassword = '';

    // Step 3: party settings
    public string $partyName = '';

    public string $shortName = '';

    public string $membershipPrefix = '';

    public string $currency = 'XOF';

    public string $timezone = 'Africa/Dakar';

    public string $locales = 'en,fr';

    public string $defaultLocale = 'fr';

    // Step 4: admin account
    public string $adminName = '';

    public string $adminEmail = '';

    public string $adminPassword = '';

    public string $adminPasswordConfirmation = '';

    // Step 5: done
    public bool $installed = false;

    public function mount(): void
    {
        if (Setting::get('installed_at')) {
            abort(403, 'Already installed.');
        }

        $this->requirements = $this->checkRequirements();
    }

    /** @return array<string, array{ok: bool, message: string}> */
    private function checkRequirements(): array
    {
        $phpVersion = PHP_VERSION;
        $phpOk = version_compare($phpVersion, '8.3.0', '>=');

        $ext = fn (string $name) => extension_loaded($name);

        return [
            'PHP 8.3+' => ['ok' => $phpOk, 'message' => "PHP {$phpVersion}"],
            'PDO' => ['ok' => $ext('pdo_mysql') || $ext('pdo_sqlite'), 'message' => 'pdo_mysql or pdo_sqlite'],
            'mbstring' => ['ok' => $ext('mbstring'), 'message' => $ext('mbstring') ? 'loaded' : 'missing'],
            'openssl' => ['ok' => $ext('openssl'), 'message' => $ext('openssl') ? 'loaded' : 'missing'],
            'tokenizer' => ['ok' => $ext('tokenizer'), 'message' => $ext('tokenizer') ? 'loaded' : 'missing'],
            'ctype' => ['ok' => $ext('ctype'), 'message' => $ext('ctype') ? 'loaded' : 'missing'],
            'bcmath' => ['ok' => $ext('bcmath'), 'message' => $ext('bcmath') ? 'loaded' : 'missing'],
            'storage writable' => ['ok' => is_writable(storage_path()), 'message' => storage_path()],
            'bootstrap/cache writable' => ['ok' => is_writable(base_path('bootstrap/cache')), 'message' => base_path('bootstrap/cache')],
            'APP_KEY set' => ['ok' => (bool) config('app.key'), 'message' => config('app.key') ? 'set' : 'not set — run: php artisan key:generate'],
        ];
    }

    public function allRequirementsMet(): bool
    {
        return collect($this->requirements)->every(fn ($r) => $r['ok']);
    }

    public function nextStep(): void
    {
        match ($this->step) {
            1 => $this->validateRequirements(),
            2 => $this->validateDatabase(),
            3 => $this->validatePartySettings(),
            4 => $this->validateAdminAndInstall(),
            default => null,
        };
    }

    private function validateRequirements(): void
    {
        if (! $this->allRequirementsMet()) {
            session()->flash('error', 'Fix the failed requirements before continuing.');

            return;
        }

        $this->step = 2;
    }

    private function validateDatabase(): void
    {
        $this->validate([
            'dbHost' => 'required',
            'dbPort' => 'required|numeric|between:1,65535',
            'dbName' => 'required|alpha_dash',
            'dbUser' => 'required',
        ]);

        // Test connection
        try {
            $pdo = new \PDO(
                "mysql:host={$this->dbHost};port={$this->dbPort};dbname={$this->dbName}",
                $this->dbUser,
                $this->dbPassword,
                [\PDO::ATTR_TIMEOUT => 5]
            );
            $pdo = null;
        } catch (\PDOException $e) {
            $this->addError('dbHost', 'Connection failed: '.$e->getMessage());

            return;
        }

        // Write DB config to .env
        $this->writeEnvValues([
            'DB_HOST' => $this->dbHost,
            'DB_PORT' => $this->dbPort,
            'DB_DATABASE' => $this->dbName,
            'DB_USERNAME' => $this->dbUser,
            'DB_PASSWORD' => $this->dbPassword,
        ]);

        Artisan::call('config:clear');
        Artisan::call('migrate', ['--force' => true]);

        $this->step = 3;
    }

    private function validatePartySettings(): void
    {
        $this->validate([
            'partyName' => 'required|min:2|max:120',
            'shortName' => 'required|min:2|max:20',
            'membershipPrefix' => 'required|alpha|min:2|max:6',
            'currency' => 'required|size:3',
            'timezone' => 'required',
            'locales' => 'required',
            'defaultLocale' => 'required',
        ]);

        $this->step = 4;
    }

    private function validateAdminAndInstall(): void
    {
        $this->validate([
            'adminName' => 'required|min:2|max:120',
            'adminEmail' => 'required|email',
            'adminPassword' => 'required|min:12|same:adminPasswordConfirmation',
        ]);

        // Run party:install in non-interactive mode
        $exitCode = Artisan::call('party:install', [
            '--party-name' => $this->partyName,
            '--short-name' => $this->shortName,
            '--membership-prefix' => $this->membershipPrefix,
            '--locales' => $this->locales,
            '--default-locale' => $this->defaultLocale,
            '--currency' => $this->currency,
            '--timezone' => $this->timezone,
            '--admin-name' => $this->adminName,
            '--admin-email' => $this->adminEmail,
            '--admin-password' => $this->adminPassword,
            '--force' => true,
            '--no-interaction' => true,
        ]);

        if ($exitCode !== 0) {
            session()->flash('error', 'Installation failed. Check the server logs.');

            return;
        }

        // Create default org levels (5-level)
        $levels = [
            ['national', false], ['regional', false], ['departmental', false],
            ['communal', true], ['section', true],
        ];
        foreach ($levels as [$nameKey, $codeRequired]) {
            OrgLevel::firstOrCreate(
                ['name_key' => $nameKey],
                ['depth' => array_search([$nameKey, $codeRequired], $levels, true), 'code_required' => $codeRequired]
            );
        }

        // Lock the installer permanently
        Setting::set('installer_locked_at', now()->toIso8601String());

        $this->installed = true;
        $this->step = 5;
    }

    private function writeEnvValues(array $values): void
    {
        $envPath = base_path('.env');
        $contents = file_get_contents($envPath);

        foreach ($values as $key => $value) {
            $escaped = preg_match('/\s/', $value) ? "\"{$value}\"" : $value;
            if (preg_match("/^{$key}=/m", $contents)) {
                $contents = preg_replace("/^{$key}=.*/m", "{$key}={$escaped}", $contents);
            } else {
                $contents .= "\n{$key}={$escaped}";
            }
        }

        file_put_contents($envPath, $contents);
    }

    public function render(): View
    {
        return view('livewire.install.wizard');
    }
}

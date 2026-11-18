<?php

namespace App\Console\Commands;

use App\Models\OrgLevel;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class PartyInstall extends Command
{
    protected $signature = 'party:install
                            {--party-name= : Full party name}
                            {--short-name= : Short/abbreviated party name}
                            {--membership-prefix= : Membership number prefix (e.g. EZM)}
                            {--locales= : Comma-separated enabled locales (e.g. en,am)}
                            {--default-locale= : Default locale (en, fr, or am)}
                            {--currency= : ISO 4217 currency code (e.g. ETB, XOF, EUR)}
                            {--timezone= : Timezone (e.g. Africa/Addis_Ababa)}
                            {--admin-name= : First admin user full name}
                            {--admin-email= : First admin user email}
                            {--admin-password= : First admin user password}
                            {--demo : Mark as demo installation}
                            {--force : Re-run even if already installed}';

    protected $description = 'Configure this installation for a political party';

    public function handle(): int
    {
        if (Setting::get('installed_at') && ! $this->option('force')) {
            $this->error('This installation has already been configured.');
            $this->line('Use --force to reconfigure.');

            return self::FAILURE;
        }

        $this->info('Party CRM — Installation');
        $this->newLine();

        $partyName = $this->resolveOption(
            option: 'party-name',
            prompt: 'Full party name (e.g. "Ethiopian Zeal Movement Alliance")',
            required: true,
        );

        $shortName = $this->resolveOption(
            option: 'short-name',
            prompt: 'Short name / abbreviation (e.g. "EZEMA")',
            required: true,
        );

        $membershipPrefix = $this->resolveOption(
            option: 'membership-prefix',
            prompt: 'Membership number prefix (e.g. "EZM", "LDR")',
            required: true,
        );

        $localesInput = $this->resolveOption(
            option: 'locales',
            prompt: 'Enabled locales, comma-separated [en, fr, am] (e.g. "en,am")',
            default: 'en',
        );
        $locales = implode(',', array_map('trim', explode(',', $localesInput)));

        $defaultLocale = $this->resolveOption(
            option: 'default-locale',
            prompt: 'Default locale',
            default: 'en',
        );

        $currency = $this->resolveOption(
            option: 'currency',
            prompt: 'Currency code (ISO 4217, e.g. "ETB", "XOF", "EUR")',
            required: true,
        );

        $timezone = $this->resolveOption(
            option: 'timezone',
            prompt: 'Timezone (e.g. "Africa/Addis_Ababa", "Africa/Dakar")',
            default: 'UTC',
        );

        $this->newLine();
        $this->info('First national admin account');

        $adminName = $this->resolveOption(
            option: 'admin-name',
            prompt: 'Admin full name',
            required: true,
        );

        $adminEmail = $this->resolveOption(
            option: 'admin-email',
            prompt: 'Admin email address',
            required: true,
        );

        $adminPassword = $this->option('admin-password')
            ?? $this->secret('Admin password (min 12 chars)');

        $validator = Validator::make([
            'email' => $adminEmail,
            'password' => $adminPassword,
        ], [
            'email' => ['required', 'email'],
            'password' => ['required', 'min:12'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $isDemo = $this->option('demo') || $this->confirm('Mark as demo installation?', false);

        $this->newLine();
        $this->info('Organisation structure levels');
        $levels = $this->collectOrgLevels();

        // Create roles
        $roles = [
            'national_admin',
            'national_leadership',
            'treasurer',
            'communications_officer',
            'regional_coordinator',
            'branch_secretary',
            'platform_support',
        ];
        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        // Write settings
        Setting::set('party_name', $partyName);
        Setting::set('party_short_name', $shortName);
        Setting::set('membership_prefix', $membershipPrefix);
        Setting::set('locales', $locales);
        Setting::set('default_locale', $defaultLocale);
        Setting::set('currency', $currency);
        Setting::set('timezone', $timezone);
        Setting::set('is_demo', $isDemo ? '1' : '0');
        Setting::set('installed_at', now()->toIso8601String());

        // Create org levels
        OrgLevel::truncate();
        foreach ($levels as $depth => $level) {
            OrgLevel::create([
                'depth' => $depth,
                'name_key' => $level['name_key'],
                'code_required' => $level['code_required'],
            ]);
        }

        // Create admin user
        $user = User::updateOrCreate(
            ['email' => $adminEmail],
            [
                'name' => $adminName,
                'password' => Hash::make($adminPassword),
                'locale' => $defaultLocale,
                'is_active' => true,
            ]
        );

        $user->syncRoles(['national_admin']);

        $this->newLine();
        $this->table(
            ['Setting', 'Value'],
            [
                ['Party name', $partyName],
                ['Short name', $shortName],
                ['Membership prefix', $membershipPrefix],
                ['Locales', $locales],
                ['Default locale', $defaultLocale],
                ['Currency', $currency],
                ['Timezone', $timezone],
                ['Demo mode', $isDemo ? 'Yes' : 'No'],
                ['Admin email', $adminEmail],
            ]
        );

        $this->newLine();
        $this->info('Installation complete.');
        $this->line('Sign in at <your-domain>/login with the admin credentials.');

        return self::SUCCESS;
    }

    protected function resolveOption(string $option, string $prompt, bool $required = false, mixed $default = null): string
    {
        if ($this->option($option) !== null) {
            return (string) $this->option($option);
        }

        if ($this->option('no-interaction')) {
            if ($required && $default === null) {
                throw new \RuntimeException("Option --{$option} is required in non-interactive mode.");
            }

            return (string) $default;
        }

        $value = $this->ask($prompt, $default);

        if ($required && blank($value)) {
            $this->error("{$prompt} is required.");

            return $this->resolveOption($option, $prompt, $required, $default);
        }

        return (string) $value;
    }

    /**
     * @return array<int, array{name_key: string, code_required: bool}>
     */
    protected function collectOrgLevels(): array
    {
        if ($this->option('no-interaction')) {
            return $this->defaultOrgLevels();
        }

        $this->line('Define 1–6 structure levels, top to bottom (e.g. National → Regional → Branch).');
        $this->line('Press Enter with no name to stop.');
        $this->newLine();

        $levels = [];
        $depth = 0;

        while ($depth < 6) {
            $nameKey = $this->ask("Level {$depth} name key (e.g. 'national', 'regional', 'branch')", $depth === 0 ? 'national' : null);

            if (blank($nameKey)) {
                break;
            }

            $codeRequired = $this->confirm("Does level '{$nameKey}' require a code?", $depth > 0);

            $levels[$depth] = [
                'name_key' => $nameKey,
                'code_required' => $codeRequired,
            ];

            $depth++;
        }

        return $levels ?: $this->defaultOrgLevels();
    }

    /**
     * @return array<int, array{name_key: string, code_required: bool}>
     */
    protected function defaultOrgLevels(): array
    {
        return [
            0 => ['name_key' => 'national', 'code_required' => false],
            1 => ['name_key' => 'regional', 'code_required' => false],
            2 => ['name_key' => 'branch', 'code_required' => true],
        ];
    }
}

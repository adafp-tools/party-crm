<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Finder\Finder;

/**
 * Scans all Blade templates and PHP files for __() calls, then reports
 * which keys are missing from each language JSON file.
 */
class PartyAuditTranslations extends Command
{
    protected $signature = 'party:audit-translations
                            {--locale= : Audit only this locale (e.g. fr, ar)}
                            {--sample=30 : Number of missing keys to show per locale}
                            {--json : Output as JSON (for CI)}';

    protected $description = 'Audit translation completeness across all language files';

    private const LOCALES = ['fr', 'pt', 'ar', 'am'];

    public function handle(): int
    {
        $this->info('Scanning for translation keys…');

        $keys = $this->extractKeys();
        $this->line('  Found '.count($keys).' unique translation keys.');
        $this->newLine();

        $locales = $this->option('locale')
            ? [$this->option('locale')]
            : self::LOCALES;

        $results = [];
        $allOk = true;

        foreach ($locales as $locale) {
            $langFile = lang_path("{$locale}.json");
            $existing = file_exists($langFile) ? array_keys(json_decode(file_get_contents($langFile), true) ?? []) : [];
            $existing = array_flip($existing);

            $missing = array_values(array_filter($keys, fn ($k) => ! isset($existing[$k])));

            $results[$locale] = [
                'total' => count($keys),
                'translated' => count($keys) - count($missing),
                'missing_count' => count($missing),
                'missing_sample' => array_slice($missing, 0, (int) $this->option('sample')),
            ];

            if (count($missing) > 0) {
                $allOk = false;
            }
        }

        if ($this->option('json')) {
            $this->line(json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return $allOk ? self::SUCCESS : self::FAILURE;
        }

        foreach ($results as $locale => $data) {
            $pct = $data['total'] > 0 ? round($data['translated'] / $data['total'] * 100) : 100;
            $status = $data['missing_count'] === 0 ? '<fg=green>✓ 100%</>' : "<fg=red>✗ {$pct}%</>";

            $this->line("  <fg=cyan>{$locale}</> {$status} — {$data['translated']}/{$data['total']} keys translated, {$data['missing_count']} missing");

            if ($data['missing_count'] > 0) {
                $sample = $data['missing_sample'];
                foreach ($sample as $key) {
                    $this->line("    <fg=yellow>·</> {$key}");
                }
                if ($data['missing_count'] > count($sample)) {
                    $remaining = $data['missing_count'] - count($sample);
                    $this->line("    … and {$remaining} more");
                }
                $this->newLine();
            }
        }

        if ($allOk) {
            $this->info('All languages are 100% translated.');
        } else {
            $this->warn('Some translations are incomplete. Add the missing keys to the respective lang/*.json files.');
        }

        return $allOk ? self::SUCCESS : self::FAILURE;
    }

    /** @return string[] */
    private function extractKeys(): array
    {
        $keys = [];

        $finder = (new Finder)
            ->files()
            ->in([app_path(), resource_path('views')])
            ->name(['*.php', '*.blade.php'])
            ->notPath(['vendor', 'node_modules']);

        foreach ($finder as $file) {
            $content = $file->getContents();
            preg_match_all(
                '/(?:__|trans|@lang)\s*\(\s*[\'"]([^\'"]+)[\'"]\s*[,\)]/u',
                $content,
                $matches,
            );
            foreach ($matches[1] ?? [] as $key) {
                $keys[$key] = true;
            }
        }

        return array_keys($keys);
    }
}

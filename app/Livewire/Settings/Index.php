<?php

namespace App\Livewire\Settings;

use App\Models\Setting;
use Livewire\Component;

class Index extends Component
{
    // Party identity
    public string $partyName = '';

    public string $partyShortName = '';

    public string $membershipPrefix = '';

    public string $partyDescription = '';

    public string $partyLogoUrl = '';

    // Contact
    public string $partyWebsite = '';

    public string $partyAddress = '';

    public string $partyPhone = '';

    public string $partyEmail = '';

    // Localisation
    public bool $localeEn = true;

    public bool $localeFr = false;

    public bool $localePt = false;

    public bool $localeAr = false;

    public string $defaultLocale = 'en';

    public string $partyColor = '';

    public string $currency = '';

    public string $timezone = '';

    // Read-only info
    public string $membershipPrefixDisplay = '';

    public string $installedAt = '';

    public bool $isDemo = false;

    public function mount(): void
    {
        if (! auth()->user()?->hasRole('national_admin')) {
            abort(403);
        }

        $this->partyName = Setting::get('party_name', '');
        $this->partyShortName = Setting::get('party_short_name', '');
        $this->membershipPrefixDisplay = Setting::get('membership_prefix', '');
        $this->partyDescription = Setting::get('party_description', '');
        $this->partyLogoUrl = Setting::get('party_logo_url', '');
        $this->partyWebsite = Setting::get('party_website', '');
        $this->partyAddress = Setting::get('party_address', '');
        $this->partyPhone = Setting::get('party_phone', '');
        $this->partyEmail = Setting::get('party_email', '');
        $this->defaultLocale = Setting::get('default_locale', 'en');
        $this->currency = Setting::get('currency', '');
        $this->timezone = Setting::get('timezone', 'UTC');
        $this->installedAt = Setting::get('installed_at', '');
        $this->isDemo = Setting::get('is_demo') === '1';

        $this->partyColor = Setting::get('party_color', '');

        $enabledLocales = explode(',', Setting::get('locales', 'en'));
        $this->localeEn = in_array('en', $enabledLocales, true);
        $this->localeFr = in_array('fr', $enabledLocales, true);
        $this->localePt = in_array('pt', $enabledLocales, true);
        $this->localeAr = in_array('ar', $enabledLocales, true);
    }

    public function save(): void
    {
        if (! auth()->user()?->hasRole('national_admin')) {
            abort(403);
        }

        $this->validate([
            'partyName' => ['required', 'string', 'max:200'],
            'partyShortName' => ['required', 'string', 'max:50'],
            'partyDescription' => ['nullable', 'string', 'max:1000'],
            'partyLogoUrl' => ['nullable', 'url', 'max:500'],
            'partyWebsite' => ['nullable', 'url', 'max:500'],
            'partyAddress' => ['nullable', 'string', 'max:500'],
            'partyPhone' => ['nullable', 'string', 'max:50'],
            'partyEmail' => ['nullable', 'email', 'max:200'],
            'defaultLocale' => ['required', 'in:en,fr,pt,ar'],
            'currency' => ['required', 'string', 'max:10'],
            'timezone' => ['required', 'string', 'max:100'],
            'partyColor' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/', 'max:7'],
        ]);

        $locales = array_values(array_filter([
            $this->localeEn ? 'en' : null,
            $this->localeFr ? 'fr' : null,
            $this->localePt ? 'pt' : null,
            $this->localeAr ? 'ar' : null,
        ]));

        if (empty($locales)) {
            $this->addError('localeEn', __('At least one language must be enabled.'));

            return;
        }

        if (! in_array($this->defaultLocale, $locales, true)) {
            $this->addError('defaultLocale', __('Default language must be one of the enabled languages.'));

            return;
        }

        Setting::set('party_name', $this->partyName);
        Setting::set('party_short_name', $this->partyShortName);
        Setting::set('party_description', $this->partyDescription);
        Setting::set('party_logo_url', $this->partyLogoUrl);
        Setting::set('party_website', $this->partyWebsite);
        Setting::set('party_address', $this->partyAddress);
        Setting::set('party_phone', $this->partyPhone);
        Setting::set('party_email', $this->partyEmail);
        Setting::set('default_locale', $this->defaultLocale);
        Setting::set('currency', $this->currency);
        Setting::set('timezone', $this->timezone);
        Setting::set('locales', implode(',', $locales));
        Setting::set('party_color', $this->partyColor);

        cache()->forget('default_locale');
        app()->setLocale($this->defaultLocale);
        session(['locale' => $this->defaultLocale]);

        session()->flash('success', __('Settings saved.'));
    }

    public function render()
    {
        return view('livewire.settings.index');
    }
}

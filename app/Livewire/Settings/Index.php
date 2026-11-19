<?php

namespace App\Livewire\Settings;

use App\Models\Setting;
use Livewire\Component;

class Index extends Component
{
    public string $partyName = '';

    public string $partyShortName = '';

    public string $membershipPrefix = '';

    public string $defaultLocale = 'fr';

    public string $currency = '';

    public string $timezone = '';

    public string $locales = '';

    public function mount(): void
    {
        if (! auth()->user()?->hasRole('national_admin')) {
            abort(403);
        }

        $this->partyName = Setting::get('party_name', '');
        $this->partyShortName = Setting::get('party_short_name', '');
        $this->membershipPrefix = Setting::get('membership_prefix', '');
        $this->defaultLocale = Setting::get('default_locale', 'fr');
        $this->currency = Setting::get('currency', '');
        $this->timezone = Setting::get('timezone', 'UTC');
        $this->locales = Setting::get('locales', 'en');
    }

    public function save(): void
    {
        if (! auth()->user()?->hasRole('national_admin')) {
            abort(403);
        }

        $this->validate([
            'partyName' => ['required', 'string', 'max:200'],
            'partyShortName' => ['required', 'string', 'max:50'],
            'defaultLocale' => ['required', 'in:en,fr,am'],
            'currency' => ['required', 'string', 'max:10'],
            'timezone' => ['required', 'string', 'max:100'],
        ]);

        Setting::set('party_name', $this->partyName);
        Setting::set('party_short_name', $this->partyShortName);
        Setting::set('default_locale', $this->defaultLocale);
        Setting::set('currency', $this->currency);
        Setting::set('timezone', $this->timezone);

        session()->flash('success', __('Settings saved.'));
    }

    public function render()
    {
        return view('livewire.settings.index');
    }
}

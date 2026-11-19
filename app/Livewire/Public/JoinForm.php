<?php

namespace App\Livewire\Public;

use App\Models\Member;
use App\Models\OrgUnit;
use App\Services\BlindIndexService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.public')]
class JoinForm extends Component
{
    public string $firstName = '';

    public string $lastName = '';

    public string $email = '';

    public string $phone = '';

    public string $dateOfBirth = '';

    public string $regionId = '';

    public string $branchId = '';

    public bool $consentGiven = false;

    /** @var string Honeypot — bots fill it, humans never see it */
    public string $website = '';

    public bool $submitted = false;

    public bool $isDuplicate = false;

    public function mount(string $locale): void
    {
        $supported = array_keys(config('locales', []));
        if (in_array($locale, $supported, true)) {
            app()->setLocale($locale);
            session(['locale' => $locale]);
        }
    }

    /** @return Collection<int, OrgUnit> */
    public function getRegionsProperty()
    {
        return OrgUnit::whereHas('orgLevel', fn ($q) => $q->where('depth', 1))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /** @return Collection<int, OrgUnit> */
    public function getBranchesProperty()
    {
        if ($this->regionId === '') {
            return collect();
        }

        return OrgUnit::where('parent_id', $this->regionId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function updatedRegionId(): void
    {
        $this->branchId = '';
    }

    public function submit(BlindIndexService $blindIndex): void
    {
        // Honeypot — silently show success without saving
        if ($this->website !== '') {
            $this->submitted = true;

            return;
        }

        $ip = request()->ip() ?? '0.0.0.0';
        $key = 'join:'.$ip;

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('rateLimit', __('Too many attempts. Please try again later.'));

            return;
        }

        $this->validate([
            'firstName' => ['required', 'string', 'max:100'],
            'lastName' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'dateOfBirth' => ['nullable', 'date', 'before:today'],
            'regionId' => ['required', 'exists:org_units,id'],
            'branchId' => ['nullable', 'exists:org_units,id'],
            'consentGiven' => ['accepted'],
        ]);

        // Blind-index duplicate check — bypass global scope (no auth on this route)
        if ($this->email !== '') {
            if (Member::withoutGlobalScopes()
                ->where('email_hash', $blindIndex->compute($this->email))
                ->exists()) {
                $this->isDuplicate = true;
                $this->submitted = true;

                return;
            }
        }

        if ($this->phone !== '') {
            if (Member::withoutGlobalScopes()
                ->where('phone_hash', $blindIndex->compute($this->phone))
                ->exists()) {
                $this->isDuplicate = true;
                $this->submitted = true;

                return;
            }
        }

        RateLimiter::hit($key, 900);

        Member::withoutGlobalScopes()->create([
            'org_unit_id' => $this->branchId ?: $this->regionId,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'email' => $this->email ?: null,
            'phone' => $this->phone ?: null,
            'date_of_birth' => $this->dateOfBirth ?: null,
            'status' => 'applicant',
            'consent_channel' => 'web',
            'consent_at' => now(),
            'category' => 'member',
        ]);

        $this->submitted = true;
    }

    public function render(): View
    {
        return view('livewire.public.join-form');
    }
}

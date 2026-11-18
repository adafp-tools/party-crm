<?php

namespace App\Livewire\Members;

use App\Models\Member;
use App\Models\OrgUnit;
use App\Services\BlindIndexService;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;
use Livewire\Component;

class Form extends Component
{
    public ?Member $member = null;

    public string $orgUnitId = '';

    public string $firstName = '';

    public string $middleName = '';

    public string $lastName = '';

    public string $gender = '';

    public string $dateOfBirth = '';

    public string $email = '';

    public string $phone = '';

    public string $addressLine = '';

    public string $occupation = '';

    public string $preferredLanguage = '';

    public string $category = 'member';

    public string $joinedDate = '';

    public bool $isYouthWing = false;

    public bool $isWomenWing = false;

    public bool $volunteer = false;

    public string $skills = '';

    public bool $consentGiven = false;

    public string $consentChannel = 'web';

    public bool $smsOptOut = false;

    public bool $emailOptOut = false;

    public ?array $duplicateWarning = null;

    public function mount(?Member $member = null): void
    {
        if ($member && $member->exists) {
            $this->authorize('update', $member);
            $this->member = $member;
            $this->fill([
                'orgUnitId' => $member->org_unit_id,
                'firstName' => $member->first_name,
                'middleName' => $member->middle_name ?? '',
                'lastName' => $member->last_name,
                'gender' => $member->gender ?? '',
                'dateOfBirth' => $member->date_of_birth?->format('Y-m-d') ?? '',
                'email' => $member->email ?? '',
                'phone' => $member->phone ?? '',
                'addressLine' => $member->address_line ?? '',
                'occupation' => $member->occupation ?? '',
                'preferredLanguage' => $member->preferred_language ?? '',
                'category' => $member->category,
                'joinedDate' => $member->joined_date?->format('Y-m-d') ?? '',
                'isYouthWing' => $member->is_youth_wing,
                'isWomenWing' => $member->is_women_wing,
                'volunteer' => $member->volunteer,
                'skills' => $member->skills ? implode(', ', $member->skills) : '',
                'consentGiven' => (bool) $member->consent_at,
                'consentChannel' => $member->consent_channel ?? 'web',
                'smsOptOut' => $member->sms_opt_out,
                'emailOptOut' => $member->email_opt_out,
            ]);
        } else {
            $this->authorize('create', Member::class);
        }
    }

    public function getOrgUnitsProperty()
    {
        return OrgUnit::where('is_active', true)->orderBy('name')->get();
    }

    public function updatedPhone(): void
    {
        $this->validatePhone();
    }

    public function save(): void
    {
        $this->validate([
            'orgUnitId' => 'required|exists:org_units,id',
            'firstName' => 'required|string|max:100',
            'middleName' => 'nullable|string|max:100',
            'lastName' => 'required|string|max:100',
            'gender' => 'required|in:male,female,other',
            'dateOfBirth' => 'required|date|before:today',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:30',
            'addressLine' => 'nullable|string|max:500',
            'occupation' => 'nullable|string|max:200',
            'preferredLanguage' => 'nullable|string|max:10',
            'category' => 'required|in:member,supporter',
            'joinedDate' => 'nullable|date',
            'consentChannel' => 'required|in:web,paper,phone_app',
        ]);

        $this->checkDuplicates();

        $skillsArray = $this->skills
            ? array_map('trim', explode(',', $this->skills))
            : null;

        $data = [
            'org_unit_id' => $this->orgUnitId,
            'first_name' => $this->firstName,
            'middle_name' => $this->middleName ?: null,
            'last_name' => $this->lastName,
            'gender' => $this->gender,
            'date_of_birth' => $this->dateOfBirth,
            'email' => $this->email ?: null,
            'phone' => $this->phone ?: null,
            'address_line' => $this->addressLine ?: null,
            'occupation' => $this->occupation ?: null,
            'preferred_language' => $this->preferredLanguage ?: null,
            'category' => $this->category,
            'joined_date' => $this->joinedDate ?: null,
            'is_youth_wing' => $this->isYouthWing,
            'is_women_wing' => $this->isWomenWing,
            'volunteer' => $this->volunteer,
            'skills' => $skillsArray,
            'consent_at' => $this->consentGiven ? now() : null,
            'consent_version' => $this->consentGiven ? '1.0' : null,
            'consent_channel' => $this->consentGiven ? $this->consentChannel : null,
            'sms_opt_out' => $this->smsOptOut,
            'email_opt_out' => $this->emailOptOut,
            'updated_by' => auth()->id(),
        ];

        if ($this->member && $this->member->exists) {
            $this->member->update($data);
            session()->flash('success', __('Member updated successfully.'));
            $this->redirectRoute('members.show', $this->member);
        } else {
            $data['status'] = 'applicant';
            $data['created_by'] = auth()->id();
            $member = Member::create($data);
            session()->flash('success', __('Member registered as applicant.'));
            $this->redirectRoute('members.show', $member);
        }
    }

    public function render()
    {
        return view('livewire.members.form', [
            'orgUnits' => $this->orgUnits,
        ]);
    }

    private function validatePhone(): void
    {
        if (! $this->phone) {
            return;
        }

        $util = PhoneNumberUtil::getInstance();
        try {
            $parsed = $util->parse($this->phone, null);
            if (! $util->isValidNumber($parsed)) {
                $this->addError('phone', __('Phone number is not valid.'));
            }
        } catch (NumberParseException) {
            $this->addError('phone', __('Phone number format is invalid.'));
        }
    }

    private function checkDuplicates(): void
    {
        $this->duplicateWarning = null;
        $bi = app(BlindIndexService::class);

        $existingQuery = Member::withoutGlobalScopes()
            ->where(function ($q) use ($bi) {
                if ($this->email) {
                    $q->orWhere('email_hash', $bi->compute($this->email));
                }
                if ($this->phone) {
                    $q->orWhere('phone_hash', $bi->compute($this->phone));
                }
            });

        if ($this->member && $this->member->exists) {
            $existingQuery->where('id', '!=', $this->member->id);
        }

        $existing = $existingQuery->first();
        if ($existing) {
            $this->duplicateWarning = [
                'type' => 'contact',
                'message' => __('A member with the same email or phone already exists.'),
            ];

            return;
        }

        if ($this->firstName && $this->lastName && $this->dateOfBirth) {
            $nameQuery = Member::withoutGlobalScopes()
                ->where('first_name', $this->firstName)
                ->where('last_name', $this->lastName)
                ->where('date_of_birth', $this->dateOfBirth);

            if ($this->member && $this->member->exists) {
                $nameQuery->where('id', '!=', $this->member->id);
            }

            if ($nameQuery->exists()) {
                $this->duplicateWarning = [
                    'type' => 'name_dob',
                    'message' => __('A member with the same name and date of birth already exists.'),
                ];
            }
        }
    }
}

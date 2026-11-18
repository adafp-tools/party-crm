<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MemberVerifyController extends Controller
{
    public function show(Request $request, string $locale, string $membership_no): View
    {
        if (! $request->hasValidSignature()) {
            abort(403, __('Invalid or expired verification link.'));
        }

        $member = Member::withoutGlobalScopes()
            ->where('membership_no', $membership_no)
            ->first();

        $isValid = $member && in_array($member->status, ['active']);

        return view('members.verify', [
            'isValid' => $isValid,
            'firstName' => $isValid ? $member->first_name : null,
            'lastInitial' => $isValid ? substr($member->last_name, 0, 1).'.' : null,
            'status' => $isValid ? $member->status : null,
        ]);
    }
}

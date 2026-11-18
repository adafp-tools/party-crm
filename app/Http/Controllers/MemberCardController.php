<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;

class MemberCardController extends Controller
{
    public function download(Member $member): Response
    {
        Gate::authorize('view', $member);

        $locale = app()->getLocale();
        $verifyUrl = URL::signedRoute('members.verify', [
            'locale' => $locale,
            'membership_no' => $member->membership_no,
        ]);

        $qrCode = new QrCode($verifyUrl);
        $writer = new PngWriter;
        $result = $writer->write($qrCode);
        $qrBase64 = base64_encode($result->getString());

        $pdf = Pdf::loadView('members.card-pdf', [
            'member' => $member,
            'qrBase64' => $qrBase64,
        ])->setPaper([0, 0, 153.07, 243.78]); // 54mm × 86mm in points

        return $pdf->download("member-card-{$member->membership_no}.pdf");
    }
}

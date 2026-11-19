<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use PragmaRX\Google2FALaravel\Support\Authenticator;

class TwoFactorController extends Controller
{
    public function __construct(protected Authenticator $google2fa) {}

    public function showChallenge(): View
    {
        return view('auth.two-factor-challenge');
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'digits:6'],
        ]);

        /** @var User $user */
        $user = Auth::user();

        $valid = $this->google2fa->verifyKey(
            $user->totp_secret,
            $request->string('code')->toString()
        );

        if (! $valid) {
            AuditLog::record('auth.two_factor_failed');

            return back()->withErrors(['code' => __('Invalid authentication code.')]);
        }

        session(['two_factor_authenticated' => true]);
        AuditLog::record('auth.two_factor_verified');

        return redirect()->intended(route('dashboard'));
    }

    public function showSetup(): View|RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->hasTotpEnabled()) {
            return redirect()->route('dashboard');
        }

        if (! $user->totp_secret) {
            $secret = $this->google2fa->generateSecretKey(32);
            $user->forceFill(['totp_secret' => $secret])->save();
        }

        $otpAuthUrl = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $user->totp_secret,
        );

        $qrCode = new QrCode(
            data: $otpAuthUrl,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 200,
            margin: 0,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
        );

        $qrCodeSvg = (new SvgWriter)->write($qrCode)->getString();

        return view('auth.two-factor-setup', [
            'secret' => $user->totp_secret,
            'qrCodeSvg' => $qrCodeSvg,
        ]);
    }

    public function confirmSetup(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'digits:6'],
        ]);

        /** @var User $user */
        $user = Auth::user();

        $valid = $this->google2fa->verifyKey(
            $user->totp_secret,
            $request->string('code')->toString()
        );

        if (! $valid) {
            return back()->withErrors(['code' => __('Invalid authentication code.')]);
        }

        $user->forceFill(['totp_confirmed_at' => now()])->save();
        session(['two_factor_authenticated' => true]);
        AuditLog::record('auth.two_factor_enabled');

        return redirect()->route('dashboard')->with('success', __('Two-factor authentication enabled.'));
    }
}

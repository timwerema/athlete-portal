<?php

namespace App\Services;

use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class TwoFactor
{
    public function __construct(private Google2FA $engine) {}

    public function newSecret(): string
    {
        return $this->engine->generateSecretKey(32);
    }

    public function qrCodeDataUri(User $user, string $secret): string
    {
        $url = $this->engine->getQRCodeUrl(config('app.name'), $user->email, $secret);
        $svg = (new Writer(new ImageRenderer(new RendererStyle(220, 2), new SvgImageBackEnd)))->writeString($url);

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    /** Check a six-digit code. A code that was already used is refused. */
    public function verify(User $user, string $secret, string $code): bool
    {
        $code = preg_replace('/\s+/', '', $code);

        if (! preg_match('/\A\d{6}\z/', $code)) {
            return false;
        }

        $step = $this->engine->verifyKeyNewer($secret, $code, $user->two_factor_last_step ?? 0);

        if ($step === false) {
            return false;
        }

        if ($step !== true) {
            $user->forceFill(['two_factor_last_step' => $step])->save();
        }

        return true;
    }

    /** @return list<string> */
    public function newRecoveryCodes(): array
    {
        return array_map(fn () => Str::lower(Str::random(5).'-'.Str::random(5)), range(1, 8));
    }

    /** Use up a recovery code. Each one works once. */
    public function useRecoveryCode(User $user, string $code): bool
    {
        $code = Str::lower(trim($code));
        $codes = $user->two_factor_recovery_codes ?? [];

        foreach ($codes as $index => $candidate) {
            if (hash_equals($candidate, $code)) {
                unset($codes[$index]);
                $user->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();

                return true;
            }
        }

        return false;
    }
}

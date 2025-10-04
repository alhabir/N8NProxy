<?php

namespace App\Services\Salla;

class SignatureValidator
{
    public static function validate(array $headers, string $rawBody, string $secret): array
    {
        $headerName = config('salla.signature_header', 'X-Salla-Signature');
        $provided = $headers[$headerName] ?? $headers[strtolower($headerName)] ?? $headers[strtoupper($headerName)] ?? null;
        if ($provided === null) {
            return [false, 'missing_signature_header'];
        }

        $computed = base64_encode(hash_hmac('sha256', $rawBody, $secret, true));

        $ok = hash_equals($provided, $computed);
        return [$ok, $ok ? 'ok' : 'mismatch'];
    }
}



<?php

namespace App\Services\Salla;

class SignatureValidator
{
    private string $secret;

    private string $headerName;

    private ?string $lastError = null;

    public function __construct(?string $secret = null, ?string $headerName = null)
    {
        $this->secret = $secret ?? (string) config('salla.webhook_secret', env('SALLA_WEBHOOK_SECRET', ''));
        $this->headerName = $headerName ?? config('salla.signature_header', 'X-Salla-Signature');
    }

    public function validate(string $rawBody, ?string $signature): bool
    {
        $this->lastError = null;

        if ($this->secret === '') {
            $this->lastError = 'missing_secret';
            return false;
        }

        if ($signature === null || $signature === '') {
            $this->lastError = 'missing_signature_header';
            return false;
        }

        $computed = $this->computeSignature($rawBody);

        if (!hash_equals($computed, (string) $signature)) {
            $this->lastError = 'mismatch';
            return false;
        }

        return true;
    }

    public function getHeaderName(): string
    {
        return $this->headerName;
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    public static function validateFromHeaders(
        array $headers,
        string $rawBody,
        ?string $secret = null,
        ?string $headerName = null,
        ?string &$error = null
    ): bool
    {
        $validator = new self($secret, $headerName);

        $provided = self::extractHeaderSignature($headers, $validator->headerName);
        $ok = $validator->validate($rawBody, $provided);
        $error = $validator->getLastError();

        return $ok;
    }

    private static function extractHeaderSignature(array $headers, string $headerName): ?string
    {
        $candidates = [$headerName, strtolower($headerName), strtoupper($headerName)];

        foreach ($candidates as $candidate) {
            if (!array_key_exists($candidate, $headers)) {
                continue;
            }

            $value = $headers[$candidate];
            if (is_array($value)) {
                return $value[0] ?? null;
            }

            return $value;
        }

        return null;
    }

    private function computeSignature(string $rawBody): string
    {
        return base64_encode(hash_hmac('sha256', $rawBody, $this->secret, true));
    }
}



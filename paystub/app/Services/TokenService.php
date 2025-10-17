<?php
namespace App\Services;

class TokenService
{
    private function secret(): string
    {
        $secret = env('DOWNLOAD_TOKEN_SECRET');
        if (!$secret) {
            // fallback inseguro sólo para desarrollo
            $secret = 'dev-secret-change-me';
        }
        return $secret;
    }

    public function generate(string $orderId, int $ttlSeconds = 900): string
    {
        $payload = [
            'o' => $orderId,
            'e' => time() + $ttlSeconds,
            'n' => bin2hex(random_bytes(4)),
        ];
        $json = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $b64 = $this->b64url($json);
        $sig = $this->b64url(hash_hmac('sha256', $b64, $this->secret(), true));
        return $b64 . '.' . $sig;
    }

    public function verify(string $token): ?string
    {
        $parts = explode('.', $token);
        if (count($parts) !== 2) return null;
        [$b64, $sig] = $parts;
        $calc = $this->b64url(hash_hmac('sha256', $b64, $this->secret(), true));
        if (!hash_equals($calc, $sig)) return null;
        $json = base64_decode(strtr($b64, '-_', '+/'), true);
        if ($json === false) return null;
        $data = json_decode($json, true);
        if (!is_array($data) || empty($data['o']) || empty($data['e'])) return null;
        if ($data['e'] < time()) return null; // Expirado
        return $data['o'];
    }

    private function b64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}

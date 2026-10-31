<?php

namespace Src\Infrastructure\Auth;

class JwtService{
	private string $secret;

public function __construct(string $secret)
{
	$this->secret = $secret;
}

public function generateToken(array $payload): string
{
 	$header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
	$base64UrlHeader = $this->base64UrlEncode($header);
    //criar a assinatura 
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $this->secret, true);
    $base64UrlSignature = $this->base64UrlEncode($signature); 

    return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
}
public function validateToken(string $token): array|false
{

	$parts = explode('.', $token);
	if (count($parts) !== 3) return false;
    [$base64UrlHeader, $base64UrlPayload, $base64UrlSignature] = $parts;


    // Recalcula a assinatura para ver se bate com a que chegou
	$validSignature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $this->secret, true);
	$validBase64UrlSignature = $this->base64UrlEncode($validSignature);

	if (!hash_equals($validBase64UrlSignature, $base64UrlSignature)) {
		return false; // Token foi adulterado
	}

    $payload = json_decode($this->base64UrlDecode($base64UrlPayload), true);

    // Verifica se o token passou do tempo de expiração
	if (isset($payload['exp']) && $payload['exp'] < time()) {

		return false; 

	}
    return $payload; 
}

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string
    {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }


}


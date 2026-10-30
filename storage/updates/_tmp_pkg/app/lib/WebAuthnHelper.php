<?php
/**
 * WebAuthnHelper — Soporte Criptográfico Autónomo para Biometría Móvil (WebAuthn / Passkeys)
 * Sistema de Gestión de Pasantes (SGP) — ISPEB
 *
 * Sin dependencias externas: procesa credenciales biométricas (Huella, FaceID, Patrón de Pantalla)
 * con OpenSSL y estándares FIDO2 / WebAuthn Level 3.
 */

declare(strict_types=1);

class WebAuthnHelper
{
    /**
     * Codificación Base64 URL-safe (RFC 4648 §5)
     */
    public static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Decodificación Base64 URL-safe
     */
    public static function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return (string)base64_decode(strtr($data, '-_', '+/'));
    }

    /**
     * Genera un desafío criptográfico aleatorio de 32 bytes
     */
    public static function generateChallenge(): string
    {
        return self::base64UrlEncode(random_bytes(32));
    }

    /**
     * Obtiene el Relying Party ID (hostname actual sin puerto)
     */
    public static function getRpId(): string
    {
        $rawHost = $_SERVER['HTTP_X_FORWARDED_HOST']
            ?? $_SERVER['HTTP_HOST']
            ?? 'localhost';
        $host = trim(explode(',', $rawHost)[0]);
        if (strpos($host, ':') !== false) {
            $host = explode(':', $host)[0];
        }
        return $host;
    }

    /**
     * Convierte SPKI DER o clave extraída en formato PEM utilizable por OpenSSL
     */
    public static function convertDerToPem(string $derBinary): string
    {
        return "-----BEGIN PUBLIC KEY-----\r\n" . chunk_split(base64_encode($derBinary), 64, "\r\n") . "-----END PUBLIC KEY-----\r\n";
    }

    /**
     * Identifica el nombre amigable del dispositivo según el User-Agent
     */
    public static function detectDeviceName(?string $ua = null): string
    {
        $ua = $ua ?? ($_SERVER['HTTP_USER_AGENT'] ?? 'Dispositivo Móvil');

        if (preg_match('/iPhone/i', $ua)) {
            return 'Apple iPhone';
        } elseif (preg_match('/iPad/i', $ua)) {
            return 'Apple iPad';
        } elseif (preg_match('/Android/i', $ua)) {
            // Intentar extraer modelo (e.g., SM-A546E, Redmi Note, etc.)
            if (preg_match('/Android\s+[\d\.]+;\s*([^;\)]+)/i', $ua, $matches)) {
                $modelo = trim($matches[1]);
                // Filtrar palabras genéricas como wv o Build
                $modelo = preg_replace('/\s*Build\/.*$/i', '', $modelo);
                return 'Android (' . $modelo . ')';
            }
            return 'Dispositivo Android';
        } elseif (preg_match('/Windows/i', $ua)) {
            return 'Computadora Windows';
        } elseif (preg_match('/Macintosh/i', $ua)) {
            return 'Computadora Mac';
        }

        return 'Dispositivo Móvil Institucional';
    }

    /**
     * Valida la firma del intento de acceso biométrico (assertion)
     */
    public static function verifyAssertion(
        string $pemPublicKey,
        string $clientDataJsonRaw,
        string $authenticatorDataRaw,
        string $signatureDerRaw,
        string $expectedChallenge,
        string $expectedRpId
    ): array {
        // 1. Validar clientDataJSON
        $clientData = json_decode($clientDataJsonRaw, true);
        if (!$clientData || !is_array($clientData)) {
            return ['success' => false, 'message' => 'clientDataJSON inválido o malformado'];
        }

        if (($clientData['type'] ?? '') !== 'webauthn.get') {
            return ['success' => false, 'message' => 'Tipo de credencial incorrecto (se esperaba webauthn.get)'];
        }

        // Comparación segura del desafío
        $receivedChallenge = $clientData['challenge'] ?? '';
        if (!hash_equals($expectedChallenge, $receivedChallenge)) {
            return ['success' => false, 'message' => 'El desafío criptográfico (challenge) no coincide o expiró'];
        }

        // 2. Validar authenticatorData
        if (strlen($authenticatorDataRaw) < 37) {
            return ['success' => false, 'message' => 'authenticatorData incompleto'];
        }

        // Verificar rpIdHash (primeros 32 bytes)
        $rpIdHashExpected = hash('sha256', $expectedRpId, true);
        $rpIdHashReceived = substr($authenticatorDataRaw, 0, 32);
        if (!hash_equals($rpIdHashExpected, $rpIdHashReceived)) {
            return ['success' => false, 'message' => 'El identificador de dominio (rpId) no coincide con el servidor'];
        }

        // Verificar banderas: Bit 0 = User Present (UP), Bit 2 = User Verified (UV)
        $flags = ord($authenticatorDataRaw[32]);
        $userPresent = ($flags & 0x01) !== 0;
        $userVerified = ($flags & 0x04) !== 0;

        if (!$userPresent) {
            return ['success' => false, 'message' => 'Presencia de usuario no verificada'];
        }

        // 3. Validar firma criptográfica con OpenSSL
        $clientDataHash = hash('sha256', $clientDataJsonRaw, true);
        $signedPayload  = $authenticatorDataRaw . $clientDataHash;

        $pubKey = openssl_pkey_get_public($pemPublicKey);
        if (!$pubKey) {
            return ['success' => false, 'message' => 'Llave pública criptográfica no válida'];
        }

        $verifyResult = openssl_verify($signedPayload, $signatureDerRaw, $pubKey, OPENSSL_ALGO_SHA256);

        if ($verifyResult === 1) {
            // Extraer contador de firmas (bytes 33..36 en formato uint32 big-endian)
            $counter = unpack('N', substr($authenticatorDataRaw, 33, 4))[1];
            return [
                'success' => true,
                'user_verified' => $userVerified,
                'counter' => $counter
            ];
        }

        return ['success' => false, 'message' => 'Firma digital biométrica inválida'];
    }
}

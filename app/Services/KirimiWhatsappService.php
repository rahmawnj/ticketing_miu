<?php

namespace App\Services;

class KirimiWhatsappService
{
    public function sendMessage(string $receiver, string $message): array
    {
        $payload = [
            'user_code' => config('services.kirimi.user_code'),
            'device_id' => config('services.kirimi.device_id'),
            'receiver' => $receiver,
            'message' => $message,
            'secret' => config('services.kirimi.secret'),
        ];

        $ch = curl_init(config('services.kirimi.endpoint'));

        $opts = [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_CONNECTTIMEOUT => (int) config('services.kirimi.connect_timeout', 15),
            CURLOPT_TIMEOUT => (int) config('services.kirimi.timeout', 30),
        ];

        $caInfo = config('services.kirimi.ca_cert_path');
        if (!empty($caInfo)) {
            $opts[CURLOPT_CAINFO] = $caInfo;
        }

        curl_setopt_array($ch, $opts);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErrNo = curl_errno($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            return [
                'ok' => false,
                'http_code' => $httpCode,
                'error_code' => $curlErrNo,
                'error_message' => $curlError,
                'response_body' => null,
            ];
        }

        return [
            'ok' => $httpCode >= 200 && $httpCode < 300,
            'http_code' => $httpCode,
            'error_code' => null,
            'error_message' => null,
            'response_body' => $response,
        ];
    }
}


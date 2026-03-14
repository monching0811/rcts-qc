<?php
/**
 * Base gateway with shared helper functionality.
 */

abstract class BaseGateway implements PaymentGatewayInterface {
    protected array $config;

    public function __construct(array $config = []) {
        $this->config = $config;
    }

    protected function getConfig(string $key, $default = null) {
        return $this->config[$key] ?? $default;
    }

    protected function normalizeAmount($amount): float {
        return round(floatval($amount), 2);
    }

    protected function generateTransactionId(): string {
        return 'TXN-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    }

    /**
     * Simple HTTP helper using cURL (falls back to file_get_contents if cURL is unavailable).
     */
    protected function httpRequest(string $method, string $url, array $headers = [], $body = null): array {
        $method = strtoupper($method);
        $response = [
            'success' => false,
            'status' => null,
            'body' => null,
            'error' => null,
        ];

        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }

            $respBody = curl_exec($ch);
            $info = curl_getinfo($ch);
            $err  = curl_error($ch);
            curl_close($ch);

            $response['success'] = $respBody !== false;
            $response['body'] = $respBody;
            $response['status'] = $info['http_code'] ?? null;
            $response['error'] = $err ?: null;
        } else {
            // Fallback to file_get_contents
            $opts = [
                'http' => [
                    'method' => $method,
                    'header' => implode("\r\n", $headers),
                    'content' => $body,
                    'timeout' => 30,
                ],
            ];
            $context = stream_context_create($opts);
            $respBody = @file_get_contents($url, false, $context);
            $response['success'] = $respBody !== false;
            $response['body'] = $respBody;
            $response['status'] = null;
            $response['error'] = $respBody === false ? error_get_last()['message'] ?? 'Unknown error' : null;
        }

        return $response;
    }
}

<?php

namespace App\Http\Controllers\Functions;

use Illuminate\Http\Request;

trait Functionstrait
{
    public function curl($endpoint, $data = [], $method = 'POST')
    {
        try {
            $curl = curl_init();

            // Bot token ve endpoint URL'sini oluşturun
            $botToken = env('TELEGRAM_BOT_TOKEN');
            $baseUrl = env('TELEGRAM_ENDPOINT') . $botToken . '/';
            $url = $baseUrl . $endpoint;

            // URL'ye GET parametrelerini ekleyin
            if ($method == 'GET' && !empty($data)) {
                $url .= '?' . http_build_query($data);
            }

            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_POSTFIELDS => $method == 'POST' ? json_encode($data) : null,
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                ),
            ));

            $response = curl_exec($curl);
            $error = curl_error($curl);
            curl_close($curl);

            if ($error) {
                throw new \Exception($error);
            }

            return json_decode($response, true);
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}

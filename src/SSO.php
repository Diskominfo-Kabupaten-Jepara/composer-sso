<?php

namespace JeparaKab\SsoTest;

use GuzzleHttp\Client;

class SSO
{
    private $client;
    private $baseApi;

    public function __construct()
    {
        $this->client = new Client();
        $this->baseApi = 'http://localhost:4000';
    }

    private function isRedirected()
    {
        return isset($_SESSION['isRedirected']) && $_SESSION['isRedirected'] === true;
    }

    private function setRedirected($value)
    {
        $_SESSION['isRedirected'] = (bool)$value;
    }

    private function getToken()
    {
        try {
            $res = $this->client->post($this->baseApi . "/sso/get-token");
            $data = json_decode($res->getBody(), true);
            return $data['data']['token'] ?? null;
        } catch (\Exception $e) {
            error_log("error getting token : " . $e->getMessage());
            return null;
        }
    }

    public function getSession($callback)
    {
        try {
            if ($this->isRedirected()) {
                error_log("Already redirected");
                return;
            }

            $token = $this->getToken();

            $res = $this->client->post($this->baseApi . "/sso/check", [
                'headers' => [
                    'Authorization' => $token
                ]
            ]);

            $data = json_decode($res->getBody(), true);
            $myToken = $data['data']['token'] ?? null;
            $refreshToken = $data['data']['refresh_token'] ?? null;

            if (!$this->isRedirected()) {
                header("Location: $callback?token=$myToken&refresh_token=$refreshToken");
                $this->setRedirected(true);
                exit;
            }

            return;
        } catch (\Exception $e) {
            $this->setRedirected(false);
            error_log("Get Session Data Error : " . $e->getMessage());
            return;
        }
    }
}

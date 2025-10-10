<?php
require_once __DIR__ . '/../../vendor/autoload.php';

class GoogleAuth {
    private $client;
    
    public function __construct() {
        $this->client = new Google_Client();
        $this->client->setClientId(getenv('GOOGLE_CLIENT_ID'));
        $this->client->setClientSecret(getenv('GOOGLE_CLIENT_SECRET'));
        $this->client->setRedirectUri(getenv('GOOGLE_REDIRECT_URI'));
        $this->client->addScope('email');
        $this->client->addScope('profile');
    }
    
    public function getLoginUrl() {
        return $this->client->createAuthUrl();
    }
    
    public function verifyToken($code) {
        try {
            $token = $this->client->fetchAccessTokenWithAuthCode($code);
            
            if (!isset($token['error'])) {
                $this->client->setAccessToken($token);
                $oauth = new Google_Service_Oauth2($this->client);
                return $oauth->userinfo->get();
            }
            return false;
        } catch (Exception $e) {
            error_log("Google Auth Error: " . $e->getMessage());
            return false;
        }
    }
}
?>
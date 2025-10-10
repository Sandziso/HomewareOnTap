<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use Facebook\Facebook;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;

class FacebookAuth {
    private $fb;
    private $helper;
    
    public function __construct() {
        $this->fb = new Facebook([
            'app_id' => getenv('FB_APP_ID'),
            'app_secret' => getenv('FB_APP_SECRET'),
            'default_graph_version' => 'v12.0',
        ]);
        
        $this->helper = $this->fb->getRedirectLoginHelper();
    }
    
    public function getLoginUrl($redirectUrl) {
        try {
            $permissions = ['email', 'public_profile'];
            return $this->helper->getLoginUrl($redirectUrl, $permissions);
        } catch (FacebookSDKException $e) {
            error_log("Facebook SDK Error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getAccessToken() {
        try {
            return $this->helper->getAccessToken();
        } catch (FacebookResponseException $e) {
            error_log("Graph Error: " . $e->getMessage());
            return false;
        } catch (FacebookSDKException $e) {
            error_log("SDK Error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getUserData($accessToken) {
        try {
            $response = $this->fb->get('/me?fields=id,first_name,last_name,email,picture', $accessToken);
            return $response->getGraphUser();
        } catch (FacebookResponseException $e) {
            error_log("Graph Error: " . $e->getMessage());
            return false;
        } catch (FacebookSDKException $e) {
            error_log("SDK Error: " . $e->getMessage());
            return false;
        }
    }
}
?>
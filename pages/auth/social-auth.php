<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

if (!isset($_GET['provider'])) {
    set_message('Invalid authentication provider.', 'error');
    header('Location: ../auth/login.php');
    exit();
}

$provider = $_GET['provider'];
$allowed_providers = ['facebook', 'google'];

if (!in_array($provider, $allowed_providers)) {
    set_message('Invalid authentication provider.', 'error');
    header('Location: ../auth/login.php');
    exit();
}

try {
    switch ($provider) {
        case 'facebook':
            require_once '../../lib/social-auth/facebook/autoload.php';
            
            $fb = new Facebook\Facebook([
                'app_id' => FACEBOOK_APP_ID,
                'app_secret' => FACEBOOK_APP_SECRET,
                'default_graph_version' => 'v12.0',
            ]);
            
            $helper = $fb->getRedirectLoginHelper();
            
            if (isset($_GET['code'])) {
                $accessToken = $helper->getAccessToken();
                $response = $fb->get('/me?fields=id,first_name,last_name,email', $accessToken);
                $userData = $response->getGraphUser();
                
                $social_id = $userData->getId();
                $email = $userData->getEmail();
                $first_name = $userData->getFirstName();
                $last_name = $userData->getLastName();
            } else {
                $loginUrl = $helper->getLoginUrl(FACEBOOK_REDIRECT_URI, ['email']);
                header('Location: ' . $loginUrl);
                exit();
            }
            break;
            
        case 'google':
            require_once '../../lib/social-auth/google/autoload.php';
            
            $client = new Google_Client();
            $client->setClientId(GOOGLE_CLIENT_ID);
            $client->setClientSecret(GOOGLE_CLIENT_SECRET);
            $client->setRedirectUri(GOOGLE_REDIRECT_URI);
            $client->addScope('email');
            $client->addScope('profile');
            
            if (isset($_GET['code'])) {
                $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
                $client->setAccessToken($token);
                
                $oauth = new Google_Service_Oauth2($client);
                $userData = $oauth->userinfo->get();
                
                $social_id = $userData->getId();
                $email = $userData->getEmail();
                $first_name = $userData->getGivenName();
                $last_name = $userData->getFamilyName();
            } else {
                $authUrl = $client->createAuthUrl();
                header('Location: ' . $authUrl);
                exit();
            }
            break;
    }
    
    if (isset($social_id) && isset($email)) {
        $db = get_database_connection();
        
        $stmt = $db->prepare("SELECT id, email, first_name, last_name, role, is_active FROM users WHERE social_id = :social_id AND social_provider = :provider");
        $stmt->bindParam(':social_id', $social_id);
        $stmt->bindParam(':provider', $provider);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            $stmt = $db->prepare("SELECT id, email, first_name, last_name, role, is_active FROM users WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                $stmt = $db->prepare("UPDATE users SET social_id = :social_id, social_provider = :provider WHERE id = :id");
                $stmt->bindParam(':social_id', $social_id);
                $stmt->bindParam(':provider', $provider);
                $stmt->bindParam(':id', $user['id']);
                $stmt->execute();
            } else {
                $stmt = $db->prepare("INSERT INTO users (email, first_name, last_name, social_id, social_provider, is_active, is_verified, created_at) 
                                     VALUES (:email, :first_name, :last_name, :social_id, :provider, 1, 1, NOW())");
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':first_name', $first_name);
                $stmt->bindParam(':last_name', $last_name);
                $stmt->bindParam(':social_id', $social_id);
                $stmt->bindParam(':provider', $provider);
                $stmt->execute();
                
                $user_id = $db->lastInsertId();
                
                $stmt = $db->prepare("SELECT id, email, first_name, last_name, role, is_active FROM users WHERE id = :id");
                $stmt->bindParam(':id', $user_id);
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        }
        
        if ($user && $user['is_active']) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['logged_in'] = true;
            $_SESSION['last_activity'] = time();
            $_SESSION['social_login'] = true;
            
            session_regenerate_id(true);
            
            set_message('Successfully logged in with ' . ucfirst($provider), 'success');
            
            if ($user['role'] === 'admin') {
                header('Location: ../../admin/dashboard.php');
            } else {
                $redirect_url = isset($_SESSION['redirect_url']) ? $_SESSION['redirect_url'] : '../account/dashboard.php';
                unset($_SESSION['redirect_url']);
                header('Location: ' . $redirect_url);
            }
            exit();
        } else {
            set_message('Your account is not active.', 'error');
            header('Location: ../auth/login.php');
            exit();
        }
    } else {
        set_message('Failed to retrieve user information from ' . ucfirst($provider), 'error');
        header('Location: ../auth/login.php');
        exit();
    }
} catch (Exception $e) {
    error_log('Social auth error: ' . $e->getMessage());
    set_message('Authentication failed. Please try again.', 'error');
    header('Location: ../auth/login.php');
    exit();
}
?>
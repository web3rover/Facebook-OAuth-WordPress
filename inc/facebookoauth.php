<?php

session_start();

function facebook_oauth_redirect()
{
	global $wp, $wp_query, $wp_the_query, $wp_rewrite, $wp_did_header;
	require_once("../wp-load.php");
	//construct URL and redirect
	$app_id = get_option("facebook_app_id");
	$redirect_url = get_site_url() . "/wp-admin/admin-ajax.php?action=facebook_oauth_callback";
	$permission = "email,name";

	$final_url = "https://www.facebook.com/dialog/oauth?client_id=" . urlencode($app_id) . "&redirect_uri=" . urlencode($redirect_url) . "&permission=" . $permission;

	header("Location: " . $final_url); 
	die();
}

add_action("wp_ajax_facebook_oauth_redirect", "facebook_oauth_redirect");
add_action("wp_ajax_nopriv_facebook_oauth_redirect", "facebook_oauth_redirect");

function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}

function facebook_oauth_callback()
{
	global $wp, $wp_query, $wp_the_query, $wp_rewrite, $wp_did_header;
	require_once("../wp-load.php");

	if(isset($_GET["code"]))
    {   
        $token_and_expire = file_get_contents("https://graph.facebook.com/oauth/access_token?client_id=" . get_option("facebook_app_id") . "&redirect_uri=". get_site_url() . "/wp-admin/admin-ajax.php?action=facebook_oauth_callback" . "&client_secret=" . get_option("facebook_app_secret") . "&code=" . $_GET["code"]);
        
        parse_str($token_and_expire, $_token_and_expire_array);
        
        
        if(isset($_token_and_expire_array["access_token"]))
        {   
            $access_token = $_token_and_expire_array["access_token"];
            $_SESSION["facebook_access_token"] = $access_token; 
            $user_information = file_get_contents("https://graph.facebook.com/me?access_token=" . $access_token . "&fields=email,name");
        	$user_information_array = json_decode($user_information, true);

        	$email = $user_information_array["email"];
        	$name = $user_information_array["name"];
        	if(username_exists($name))
			{
				$user_id = username_exists($name);
				wp_set_auth_cookie($user_id);
				header('Location: ' . get_site_url());
			}
			else
			{
				//create a new account and then login
				wp_create_user($name, generateRandomString(), $email);
				$user_id = username_exists($name);
				wp_set_auth_cookie($user_id);
				header('Location: ' . get_site_url());
			}
        }
        else
        {   
            header("Location: " . get_site_url());
        }
    }
    else
    {
    	header("Location: " . get_site_url());
    }

    die();
}

add_action("wp_ajax_facebook_oauth_callback", "facebook_oauth_callback");
add_action("wp_ajax_nopriv_facebook_oauth_callback", "facebook_oauth_callback");
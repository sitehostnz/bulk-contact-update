<?php
// Name:    contact-update.php
// Purpose: This PHP script utilises the SiteHost API to update the contacts for Domains in bulk. 
//          It is an example script that a customer can customise to their specific needs.
// Author:  SiteHost

// API Key
// Can be sourced from the SiteHost Control Panel
// 
// Instructions for generating a SiteHost API Key
// https://kb.sitehost.nz/developers/api#creating-an-api-key
//
$API_KEY = "";
// Set the current API Version
$API_VERSION = "1.3";
// Base URL
$BASE_URL = "https://api.sitehost.nz/{$API_VERSION}";
// Visible in the SiteHost Control Panel next to Client name with a # prefix.
$client_id = "12345";
// Contact IDs
$contact_ids = array(
    "billing" => "1235",
    "technical" => "1235",
    "admin" => "12345",
    "registrant" => "12345"
);  

// Querries the SiteHost API and returns a the JSON dictionary.
function getAPI_JSON($uri) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $uri);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$result = curl_exec($ch);

    if ($result === false) {
        $error = curl_error($ch);
        curl_close($ch);
        return ['error' => "cURL error: $error"];
    }

    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($status < 200 || $status >= 300) {
        return ['error' => "HTTP error: $status"];
    }

    return json_decode($result, true) ?: ['error' => 'JSON decode error'];
}


// Posts to the SiteHost API and returns a the JSON dictionary.
function postAPI_JSON($uri, $body) {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $uri);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);

    $result = curl_exec($ch);

    if ($result === false) {
        $error = curl_error($ch);
        curl_close($ch);
        return ['error' => "cURL error: $error"];
    }

    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($status < 200 || $status >= 300) {
        return ['error' => "HTTP error: $status"];
    }

    return json_decode($result, true) ?: ['error' => 'JSON decode error'];
}


// Simple api.sitehost.nz request generator for list_accounts and list_domains
function genURI($request, $base_url, $api_key, $client_id, $contact_id='') {
	if($request == 'list_domains') {
		return("{$base_url}/srs/{$request}.json?apikey={$api_key}&client_id={$client_id}&filters[state]=Active");
	}
    elseif($request == 'get_contact') {
        return("{$base_url}/srs/{$request}.json?apikey={$api_key}&client_id={$client_id}&contact_id={$contact_id}");
    }
    elseif($request == 'update_domain_contacts') {
        return("{$base_url}/srs/{$request}.json");
    }
	else {
		return("{$base_url}/srs/{$request}.json?apikey={$api_key}&client_id={$client_id}");
	}
}

echo "Please check over the contacts below before proceeding." . PHP_EOL;

foreach ($contact_ids as $key => $contact_id) {
    $contact = getAPI_JSON(genURI('get_contact', $BASE_URL,  $API_KEY, $client_id, $contact_id));
    echo $key . PHP_EOL;
    if (in_array('return', $contact)) {
        print_r($contact['return']) . PHP_EOL;
    } else {
        echo "Error: " . $contact['error'] . PHP_EOL;
        exit;
    }
}

$domains = getAPI_JSON(genURI('list_domains', $BASE_URL,  $API_KEY, $client_id)); 

echo "Please check over the domains below before proceeding." . PHP_EOL;

if (in_array('return', $domains)) {
    foreach ($domains['return']['data'] as $dm_resp) {
        echo $dm_resp['domain'] . PHP_EOL;
    }
} else {
    echo "Error: " . $domains['error'] . PHP_EOL;
    exit;
}

echo "Please confirm that you want to proceed: [y/n]";
$input = trim(fgets(STDIN));

if ($input == 'y') {
    echo "Proceeding..." . PHP_EOL;
} else {
    echo "Exiting..." . PHP_EOL;
    exit;
}

// Iterate through every domain in the account 
if(in_array('status', $domains)) {
	foreach ($domains['return']['data'] as $dm_resp) {
        $body = array(
            'apikey' => $API_KEY,
            'client_id' => $client_id,
            'domain' => $dm_resp['domain'],
            'registrant_contact_id' => $contact_ids['registrant'],
            'admin_contact_id' => $contact_ids['admin'],
            'technical_contact_id' => $contact_ids['technical'],
            'billing_contact_id' => $contact_ids['billing'],
        );
        $update = postAPI_JSON(genURI('update_domain_contacts', $BASE_URL, $API_KEY, $client_id), $body);
        if (in_array('return', $update)) {
            echo "Update contacts for " . $dm_resp['domain'] . ": " . $update['msg'] . PHP_EOL;
        } else {
            echo "Error: " . $update['error'] . PHP_EOL;
        }

		// Sleep to avoid rate-limiting
		usleep(100000);
	}
} else {
    var_dump($domains);
}

?>

<?php

/* 
 * Unfuck Tumblr
 */
/*
 * Tumblr being dum fix 
 * @Based-on: <https://github.com/Arvedui/tt-rss-tumblr-gdpr> 
 */
if (!preg_match(";^https?://.*\.tumblr.com/rss$;", $fetch_url)) {
	return $feed_data;
}
$curl_handle = curl_init();
curl_setopt($curl_handle, CURLOPT_COOKIEJAR, "/dev/null");
curl_setopt($curl_handle, CURLOPT_COOKIEFILE, "/dev/null");
curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
// Configure curl handle for acquiring the cookie
$data = array(
	"eu_resident" => "True",
	"gdpr_consent_core" => "False",
	"gdpr_consent_first_party_ads" => "False",
	"gdpr_consent_search_history" => "False",
	"gdpr_consent_third_party_ads" => "False",
	"gdpr_is_acceptable_age" => "False",
	"redirect_to" => $fetch_url);
curl_setopt($curl_handle, CURLOPT_IPRESOLVE,  CURL_IPRESOLVE_V4);
curl_setopt($curl_handle, CURLOPT_URL, "https://www.tumblr.com/svc/privacy/consent");
curl_setopt($curl_handle, CURLOPT_POST, true);
curl_setopt($curl_handle, CURLOPT_POSTFIELDS, http_build_query($data));
curl_exec($curl_handle);
// Configure handle for actual rss request
curl_setopt($curl_handle, CURLOPT_POST, false);
curl_setopt($curl_handle, CURLOPT_POSTFIELDS, "");
curl_setopt($curl_handle, CURLOPT_URL, $fetch_url);
$data = curl_exec($curl_handle);
curl_close($curl_handle);
return $data;



$retryBecauseFailed = true; // when fetch was not RSS.
if (preg_match(";^https?://.*\.tumblr.com/rss$;", $fetch_url)||$retryBecauseFailed) {
	$response = wp_remote_get("https://www.tumblr.com/svc/privacy/consent");
	$args=array('cookies'=>$response['cookies']);
	return wp_remote_get($fetch_url,$args);
}

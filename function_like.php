<?php

	
	require_once( 'config.php' );
	require_once( 'TwitterAPIExchange.php' );
	
	/*---------- MONTA A CONEXÃO COM A API ----------*/
	$settings = array(
		'oauth_access_token' => TWITTER_ACCESS_TOKEN, 
		'oauth_access_token_secret' => TWITTER_ACCESS_TOKEN_SECRET, 
		'consumer_key' => TWITTER_CONSUMER_KEY, 
		'consumer_secret' => TWITTER_CONSUMER_SECRET,
	);
	

	/*---------- Até aqui é só conexão, talvez nao precise. ----------*/




	
	/*---------- FAZ O LIKE ----------*/
	$url = 'https://api.twitter.com/1.1/favorites/create.json';
	$requestMethod = 'POST';
	$apiData = array(
	    'id' => 1416081115824234497,
	    );
	$twitter = new TwitterAPIExchange( $settings );
	$twitter->setPostfields( $apiData );   
	$twitter->buildOauth( $url, $requestMethod );
	$response = $twitter->performRequest(true, array (CURLOPT_SSL_VERIFYHOST => 0, CURLOPT_SSL_VERIFYPEER => 0));
	$lista = json_decode($response);
	
		
	

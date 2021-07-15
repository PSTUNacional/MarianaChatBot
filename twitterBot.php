<?php
	require_once( 'config.php' );
	/*---------- MONTA A CONEXÃO COM A API ----------*/
	require_once( 'TwitterAPIExchange.php' );

	$settings = array(
		'oauth_access_token' => TWITTER_ACCESS_TOKEN, 
		'oauth_access_token_secret' => TWITTER_ACCESS_TOKEN_SECRET, 
		'consumer_key' => TWITTER_CONSUMER_KEY, 
		'consumer_secret' => TWITTER_CONSUMER_SECRET,
	);
	
	
	
	$file = fopen("last_id.txt", "r");
        while(! feof($file)) {
        $last_id = fgets($file);
    	}
	fclose($file);
	
	/*---------- PUXA AS ÚLTIMAS MENÇÕE AO BOT ----------*/
	$url = 'https://api.twitter.com/1.1/statuses/mentions_timeline.json';
    $requestMethod = 'GET';
    $apiData = '?since_id='.$last_id;
    $twitter = new TwitterAPIExchange( $settings );
    $twitter->buildOauth($url, $requestMethod);
	$response = $twitter->performRequest(true, array (CURLOPT_SSL_VERIFYHOST => 0, CURLOPT_SSL_VERIFYPEER => 0));
	$lista = json_decode($response);
	
	/*---------- FUNÇÕES INTERPRETATIVAS - Busca no texto palavras-chave para condicionar a resposta  ----------*/
	function find_first_key_word($tweet) {
	$key_words = array("jornal", "filia", "filiação", "filiar");
	foreach ($key_words as $word) {
		if (strpos($tweet, $word) !== false) {
			 return $word;
		}
	}
	return NULL;
    }

	function key_word_phrase($keyword) {
	
	if(is_null($keyword))
	{
	    return "Oi! Me chamou? Não consigo responder agora. Eu e os programadores ainda estamos trabalhando nisso. Aproveite para dar uma olhada no nosso site https://pstu.org.br";
	    
	}
	
	$phrases = array(
	    "jornal" =>     "Oi, nesse link você acessa as últimas edições do jornal Opinião Socialista https://pstu.org.br/opiniaosocialista/",
	    "filia" =>      "Oi, nesse link você pode se cadastrar para filiação. Preenche lá, é rapidinho! Em breve alguém entrará em contato para terminar o processo. https://facaparte.pstu.org.br",
    	"filiação" =>   "Oi, nesse link você pode se cadastrar para filiação. Preenche lá, é rapidinho! Em breve alguém entrará em contato para terminar o processo. https://facaparte.pstu.org.br",
	    "filiar" =>     "Oi, nesse link você pode se cadastrar para filiação. Preenche lá, é rapidinho! Em breve alguém entrará em contato para terminar o processo. https://facaparte.pstu.org.br"
	);
	
	return $phrases[$keyword];
}
	
    /*---------- LOOPING - Responde os tweets ----------*/
	foreach ($lista as $id_t){
	    $id = $id_t->id;
	    $tweet = $id_t->text;
	    
	    $urlAnswer = 'https://api.twitter.com/1.1/statuses/update.json';
        $requestMethod = 'POST';
    
         $apiId = array(
            'status' => key_word_phrase(find_first_key_word($tweet)),
            'auto_populate_reply_metadata' => 'true',
            'in_reply_to_status_id' => $id,
            );
        

        $twitter = new TwitterAPIExchange( $settings );
        $twitter->setPostfields($apiId);    
        $twitter->buildOauth($urlAnswer, $requestMethod);
    	$response = $twitter->performRequest(true, array (CURLOPT_SSL_VERIFYHOST => 0, CURLOPT_SSL_VERIFYPEER => 0));
	}
	
	
    /*---------- REGISTRA LAST_ID - Salva o ID do último tweet respondido. Ele será o ponto de início do próximo ciclo ----------*/
	
	$fp = fopen('last_id.txt', 'w');
    fwrite($fp, $lista[0]->id);
    fclose($fp);


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
	
	/*---------- Verifica o último id ----------*/
	$file = fopen("last_id.txt", "r");
        while(! feof($file)) {
        	$last_id = fgets($file);
    	}
	fclose($file);
	
	/*---------- PUXA AS ÚLTIMAS MENÇÕEs AO BOT ----------*/
	$url = 'https://api.twitter.com/1.1/statuses/mentions_timeline.json';
	$requestMethod = 'GET';
	$apiData = '?since_id='.$last_id;
	$twitter = new TwitterAPIExchange( $settings );
	$twitter->buildOauth($url, $requestMethod);
	$response = $twitter->performRequest(true, array (CURLOPT_SSL_VERIFYHOST => 0, CURLOPT_SSL_VERIFYPEER => 0));
	$lista = json_decode($response);
	
	/*---------- FUNÇÕES INTERPRETATIVAS - Busca no texto palavras-chave para condicionar a resposta  ----------*/
	function find_first_key_word($tweet) {
		$key_words = array(
			"jornal" => array("jornal"),
			"filiar" => array("filiar", "filia", "filiação")
		);
		foreach ($key_words as $normalized_word => $possible_words) {
			foreach ($possible_words as $word){
				if (strpos($tweet, $word) !== false) {
					return $normalized_word;
				}
			}
		}
		return NULL;
    	}
	
	function key_word_phrase($keyword) {
		if(is_null($keyword)){
			return "Oi! Me chamou? Não consigo responder agora. Eu e os programadores ainda estamos trabalhando nisso. Aproveite para dar uma olhada no nosso site https://pstu.org.br";
		}
		$phrases = array(
			"jornal" => array(
				"Oi, nesse link você acessa as últimas edições do jornal Opinião Socialista https://pstu.org.br/opiniaosocialista/",
				"Esse último jornal está muito bom mesmo, recomendo! Dá uma olhada nas últimas edições nesse link https://pstu.org.br/opiniaosocialista/",
				"Acredita que eu estava agora mesmo dando uma olhada no jornal? Aqui estão as últimas edições https://pstu.org.br/opiniaosocialista/"
			),
			"filiar" => array(
				"Que demais! Fico muito feliz por você querer se filiar! Preenche seu cadastro nesse link, é rapidinho! Em breve alguém entrará em contato para terminar o processo. https://facaparte.pstu.org.br",
				"AAAAaaaaahhh fico feliz que você está se filiando! Olha, só faz o cadastro nesse link e em breve alguém entrará em contato! https://facaparte.pstu.org.br",
				"Meeee pareceee. Meeee pareceee. Meeee pareceee. Que o socialismo cresce. Faz o cadastro nesse link que eu to te mandando, e alguém entra em contato com você em breve! https://facaparte.pstu.org.br"
			)
		);
		$statement = array_rand($phrases[$keyword]);
		return $phrases[$keyword][$statement];
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
	//fwrite($fp, $lista[0]->id); //esse código pega o primeiro twitte respondido, não o último.
	fwrite($fp, end($lista)->id);
	fclose($fp);

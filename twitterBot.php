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
	$twitter->setGetfield( $apiData );   
	$twitter->buildOauth( $url, $requestMethod );
	$response = $twitter->performRequest(true, array (CURLOPT_SSL_VERIFYHOST => 0, CURLOPT_SSL_VERIFYPEER => 0));
	$lista = json_decode($response);
	
	/*---------- FUNÇÕES INTERPRETATIVAS - Busca no texto palavras-chave para condicionar a resposta  ----------*/
    function stripAccents($str) {
        return strtr(utf8_decode($str), utf8_decode('àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ'), 'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY');
    }
	
	function find_first_key_word($tweet) {
	    $tweet = stripAccents(mb_strtolower($tweet, 'UTF-8'));
		$key_words = array(
			"jornal" => array("jornal", "jornais", "opinião socialista", "opiniao socialista"),
			"filiar" => array("filiar", "filia", "filiação", "filie"),
			"whatsapp" => array("whatsapp", "zap", "whats"),
			"facebook" => array("facebook"),
			"palestina" => array("palestina"),
			"serie trotsky" => array("serie tr", "serie do tr", "serie sobre tr"),
			"formacao" => array("formacao", "formação", "clássicos", "classico", "estudar", "estudo")
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
				"Que demais! Fico muito feliz por você querer se filiar! Preenche seu cadastro nesse link, é rapidinho! Em breve alguém entrará em contato para terminar o processo. https://bit.ly/36AXid2",
				"AAAAaaaaahhh fico feliz que você quer se filiar! Olha, só faz o cadastro nesse link e em breve alguém entrará em contato! https://bit.ly/36AXid2",
				"Meeee pareceee. Meeee pareceee. Meeee pareceee. Que o socialismo cresce. Faz o cadastro nesse link que eu to te mandando, e alguém entra em contato com você em breve! https://bit.ly/36AXid2"
			),
			"whatsapp" => array(
				"Oi! Para se inscrever na nossa lista é só mandar um RECEBER para\n\n(11) 9.4101-1917\n\nVocê também pode clicar aqui https://bit.ly/3wGeetd .\nNão se esqueça de salvar nosso número na sua agenda. =)"
			),
			"facebook" => array(
				"Oi! Aproveita para seguir a gente também no Facebook! Só acessar aqui: https://www.facebook.com/pstu16"
			),
			"palestina" => array(
				"Oi! Encontrei essas matérias aqui sobre Palestina no nosso site: https://www.pstu.org.br/palestina/"
			),
			"serie trotsky" => array(
			    "Aqui está a nossa série sobre Trotksy: https://youtube.com/playlist?list=PLJDALdfR0xX14NVlOncjLC5hhMMcoTjEB"
			),
			"formacao" => array(
			    "Você pode acessar todos nossos cursos e materiais de formação aqui nesse link: https://bit.ly/3emDO0e"    
			)
		);
		$statement = array_rand($phrases[$keyword]);
		return quoted_printable_decode($phrases[$keyword][$statement]);;
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
		
		echo 'Este túite '.$id.' foi respondido. <br/>';
	}
	
	/*---------- REGISTRA LAST_ID - Salva o ID do último tweet respondido. Ele será o ponto de início do próximo ciclo ----------*/
	if($lista[0]->id !== NULL){	
		$fp = fopen('last_id.txt', 'w');
		fwrite ($fp, $lista[0]->id);
		fclose($fp);
	}

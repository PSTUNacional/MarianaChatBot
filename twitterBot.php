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
	
	function trim_articles($phrase){
		$articles = array("o", "os", "a", "as", "um", "uns", "uma", "umas");
		$phrase_words = explode(" ",$phrase);
		$new_phrase = implode(array_diff($phrase_words, $articles));
		return $new_phrase;
	}
	
	function trim_punctuation($phrase){
		return str_replace(array("?","!",",",";","."), "", $phrase);
	}
	
	function normalize_url_param_value($value){
		return rawurlencode(trim_punctuation(trim_articles($value)));
	}
	
	function normalize_tweet($tweet){
		return stripAccents(mb_strtolower($tweet, 'UTF-8'));
	}
	
	function choose_phrase($arr_phrases){
		$statement = array_rand($arr_phrases);
		return quoted_printable_decode($arr_phrases[$statement]);
	}
	
	function find_first_command($tweet){
		$tweet = normalize_tweet($tweet);
		$key_commands = array(
			"#editorial" => array("#editorial", "editorial"),
			"#jornal" => array("#jornal", "jornal", "opiniao socialista", "opinião socialista"),
			"#filiar" => array("#filiar", "filiar", "filia", "filiação", "filie"),
			"#whatsapp" => array("#whatsapp", "whatsapp", "zap", "whats"),
			"#facebook" => array("#facebook", "facebook"),
			"#palestina" => array("#palestina", "palestina"),
			"#serieTrotsky" => array("#serieTrotsky", "serie tr", "serie do tr", "serie sobre tr"),
			"#formacao" => array("#formacao", "formacao", "formação", "clássicos", "classico", "estudar", "estudo"),
			"#orientacao" => array("#orientacao", "orientacao marxita", "orientação marxista", "canal do gustavo", "gustavo machado"),
			"#socialismo" => array("#socialismo", "socialismo", "socialista", "comunismo", "comunista", "anti-capitalismo", "anti-capitalista", "capitalismo", "capitalista"),
			"#pesquisa" => array("#pesquisa", " sobre ")
		);
		foreach ($key_commands as $normalized_command => $possible_commands){
			foreach($possible_commands as $command){
				$pos = strpos($tweet, $command);
				if ($pos !== false) {
					$param = substr($tweet,$pos+strlen($command)); //seleciona apenas o texto que está depois do comando
					$param = trim_punctuation($param);
					$param = trim($param); //remove espaços
					return array("command" => $normalized_command, "param" => $param);
				}
			}
		}
		return NULL;
	}
	
	function command_phrase($arr_command){
		if(is_null($arr_command)){
			return "Oi! Me chamou? Não consigo responder agora. Eu e os programadores ainda estamos trabalhando nisso. Aproveite para dar uma olhada no nosso site https://pstu.org.br";
		}
		switch ($arr_command["command"]) {
			case "#pesquisa":
				return "Oi! Encontrei essas matérias aqui no site: https://www.pstu.org.br/?s=" . normalize_url_param_value($arr_command["param"]);
			case "#editorial":
				$response = json_decode(file_get_contents('https://www.pstu.org.br/wp-json/wp/v2/posts?categories=5069&per_page=1'));
				$link = $response[0]->link;
				return "Oi! Esse é o último editorial que o partido está utilizando para intervenção nas lutas " . $link;
			case "#jornal":
				$response = json_decode(file_get_contents('https://www.pstu.org.br/wp-json/wp/v2/posts?categories=6090&per_page=1'));
				$link = $response[0]->link;
				return "Oi! Esse é o último jornal Opinião Socialista " . $link;
			case "#filiar":
				return choose_phrase(array(
					"Que demais! Fico muito feliz por você querer se filiar! Preenche seu cadastro nesse link, é rapidinho! Em breve alguém entrará em contato para terminar o processo. https://bit.ly/36AXid2",
					"AAAAaaaaahhh fico feliz que você quer se filiar! Olha, só faz o cadastro nesse link e em breve alguém entrará em contato! https://bit.ly/36AXid2",
					"Meeee pareceee. Meeee pareceee. Meeee pareceee. Que o socialismo cresce. Faz o cadastro nesse link que eu to te mandando, e alguém entra em contato com você em breve! https://bit.ly/36AXid2"
				));
			case "#whatsapp":
				return choose_phrase(array(
					"Oi! Para se inscrever na nossa lista é só mandar um RECEBER para\n\n(11) 9.4101-1917\n\nVocê também pode clicar aqui https://bit.ly/3wGeetd .\nNão se esqueça de salvar nosso número na sua agenda. =)"
				));
			case "#facebook":
				return choose_phrase(array(
					"Oi! Aproveita para seguir a gente também no Facebook! Só acessar aqui: https://www.facebook.com/pstu16"
				));
			case "#palestina":
				return choose_phrase(array(
					"Oi! Encontrei essas matérias aqui sobre Palestina no nosso site: https://www.pstu.org.br/palestina/"
				));
			case "#serieTrotsky":
				return choose_phrase(array(
					"Aqui está a nossa série sobre Trotksy: https://youtube.com/playlist?list=PLJDALdfR0xX14NVlOncjLC5hhMMcoTjEB"
				));
			case "#formacao":
				return choose_phrase(array(
					"Você pode acessar todos nossos cursos e materiais de formação aqui nesse link: https://bit.ly/3emDO0e"
				));
			case "#orientacao":
				return choose_phrase(array(
					"Ei, aqui vai o link para o canal do Orientação Marxista ( @orient_marxista ).\nhttps://www.youtube.com/channel/UCRLEkZpNRoZQBG8kUTBD8vQ \n\nAproveita e segue ele também!"
				));
			case "#socialismo":
				return choose_phrase(array(
					"Eu sou socialista!\nEsse capitalismo já está podre, né... Está destruindo o planeta e a humanidade. Se quiser entender melhor porque defendemos o socialismo, dá uma olhada nesse texto aqui https://www.pstu.org.br/para-mudar-o-mundo-e-necessario-lutar-pelo-socialismo/"
				));
		}
	}
	
	/*---------- LOOPING - Responde os tweets ----------*/
	foreach ($lista as $id_t){
		$id = $id_t->id;
		$tweet = $id_t->text;
		$urlAnswer = 'https://api.twitter.com/1.1/statuses/update.json';
		$requestMethod = 'POST';
		$apiId = array(
			'status' => command_phrase(find_first_command($tweet)),
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

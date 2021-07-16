<?php
	require_once( 'config.php' );
	require_once( 'TwitterAPIExchange.php' );
	
	/*---------- Funções de comando do twitter ----------*/
	$settings = array(
		'oauth_access_token' => TWITTER_ACCESS_TOKEN, 
		'oauth_access_token_secret' => TWITTER_ACCESS_TOKEN_SECRET, 
		'consumer_key' => TWITTER_CONSUMER_KEY, 
		'consumer_secret' => TWITTER_CONSUMER_SECRET,
	);
	
	function last_mentions($last_id){
		$url = 'https://api.twitter.com/1.1/statuses/mentions_timeline.json';
		$requestMethod = 'GET';
		$apiData = '?since_id='.$last_id;
		$response = send_to_twitter_API($url, $requestMethod, $apiData);
		$lista = json_decode($response);
		return $lista;
	}
	
	function send_response($tweet_id, $response_phrase){
		send_to_twitter_API(
			'https://api.twitter.com/1.1/statuses/update.json',
			'POST',
			array(
				'status' => $response_phrase,
				'auto_populate_reply_metadata' => 'true',
				'in_reply_to_status_id' => $tweet_id,
			)
		);
	}
	
	function send_like($tweet_id){
		send_to_twitter_API(
			'https://api.twitter.com/1.1/favorites/create.json',
			'POST',
			array(
				'id' => $tweet_id,
			)
		);
	}
	
	function send_to_twitter_API($url, $requestMethod, $apiData){
		global $settings;
		$twitter = new TwitterAPIExchange($settings);
		switch ($requestMethod){
			case "POST":
				$twitter->setPostfields($apiData);
				break;
			case "GET":
				$twitter->setGetfield($apiData);
				break;
		}
		$twitter->buildOauth($url, $requestMethod);
		$response = $twitter->performRequest(true, array (CURLOPT_SSL_VERIFYHOST => 0, CURLOPT_SSL_VERIFYPEER => 0));
		return $response;
	}
	
	/*---------- Funções de registro ----------*/
	function get_last_id(){
		$file = fopen("last_id.txt", "r");
		while(! feof($file)) {
			$last_id = fgets($file);
		}
		fclose($file);
		return $last_id;
	}
	
	function set_last_id($last_id){
		if($last_id !== NULL){	
			$fp = fopen('last_id.txt', 'w');
			fwrite ($fp, $last_id);
			fclose($fp);
		}
	}

	/*---------- FUNÇÕES INTERPRETATIVAS - Busca no texto palavras-chave para condicionar a resposta  ----------*/
	function stripAccents($str) {
		return strtr(utf8_decode($str), utf8_decode('àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ'), 'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY');
	}
	
	function trim_articles($phrase){
		$articles = array("o", "os", "a", "as", "um", "uns", "uma", "umas");
		$phrase_words = explode(" ",$phrase);
		$new_phrase = implode(" ", array_diff($phrase_words, $articles));
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
	
	function define_action_on_twitter($tweet_id, $tweet_phrase){
		$arr_command = find_first_command($tweet_phrase);
		if(is_null($arr_command)){return send_like($tweet_id);} //sem comandos, apenas dar like
		//apenas temos comandos de responder por enquanto
		send_like($tweet_id);
		send_response($tweet_id, command_phrase($arr_command));
	}
	
	/*---------- LOOPING - Responde os tweets ----------*/
	$tweet_list = last_mentions(get_last_id());
	$last_id = 0;
	foreach ($tweet_list as $tweet){
		$tweet_id = $tweet->id;
		global $last_id;
		$last_id = $tweet_id; //redefine $last_id a cada iteração para que o valor final seja o do último
		$tweet_phrase = $tweet->text;
		define_action_on_twitter($tweet_id, $tweet_phrase);
		echo 'Este túite '.$tweet_id.' foi respondido. <br/>'; //precisa disso?
	}
	set_last_id($last_id);

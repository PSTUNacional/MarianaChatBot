<?php
	require_once( 'config.php' );
	require_once( 'TwitterAPIExchange.php' );
	
	/*---------- Funções de comando do twitter ----------*/
	$friends = [];
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
		$lista = send_to_twitter_API($url, $requestMethod, $apiData);
		return $lista;
	}
	
	function get_friends(){
		$url = 'https://api.twitter.com/1.1/friends/ids.json';
		$requestMethod = 'GET';
		$apiData = "";
		$lista = send_to_twitter_API($url, $requestMethod, $apiData);
		global $friends;
		$friends = $lista->ids;
	}
	
	function send_response($tweet_id, $response_phrase){
		$response = send_to_twitter_API(
			'https://api.twitter.com/1.1/statuses/update.json',
			'POST',
			array(
				'status' => $response_phrase,
				'auto_populate_reply_metadata' => 'true',
				'in_reply_to_status_id' => $tweet_id,
			)
		);
		return $response;
	}

	function send_retweet($tweet_id, $response_phrase){
		$response = send_to_twitter_API(
			'https://api.twitter.com/1.1/statuses/retweet/'.$tweet_id.'.json',
			'POST',
			array()
		);
		return $response;
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
		return json_decode($response);
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
		if($last_id !== NULL AND $last_id !== 0){
			$fp = fopen('last_id.txt', 'w');
			fwrite ($fp, $last_id);
			fclose($fp);
		}
	}

	/*---------- FUNÇÕES INTERPRETATIVAS - Busca no texto palavras-chave para condicionar a resposta  ----------*/
	function stripAccents($str) {
		$char_map = array(
			"ъ" => "b", "ь" => "b", "Ъ" => "b", "Ь" => "b",
			"А" => "A", "Ă" => "A", "Ǎ" => "A", "Ą" => "A", "À" => "A", "Ã" => "A", "Á" => "A", "Æ" => "AE", "Â" => "A", "Å" => "A", "Ǻ" => "A", "Ā" => "A", "א" => "N",
			"Б" => "B", "ב" => "C", "Þ" => "P",
			"Ĉ" => "C", "Ć" => "C", "Ç" => "C", "Ц" => "U", "צ" => "y", "Ċ" => "C", "Č" => "C", "©" => "C", "ץ" => "y",
			"Д" => "A", "Ď" => "D", "Đ" => "D", "ד" => "T", "Ð" => "D",
			"È" => "E", "Ę" => "E", "É" => "E", "Ë" => "E", "Ê" => "E", "Е" => "E", "Ē" => "E", "Ė" => "E", "Ě" => "E", "Ĕ" => "E", "Є" => "E", "Ə" => "E", "ע" => "y",
			"Ф" => "O", "Ƒ" => "F",
			"Ğ" => "G", "Ġ" => "G", "Ģ" => "G", "Ĝ" => "G", "Г" => "T", "ג" => "A", "Ґ" => "T",
			"ח" => "n", "Ħ" => "H", "Х" => "X", "Ĥ" => "H", "ה" => "n",
			"I" => "I", "Ï" => "I", "Î" => "I", "Í" => "I", "Ì" => "I", "Į" => "I", "Ĭ" => "I", "I" => "I", "И" => "N", "Ĩ" => "I", "Ǐ" => "I", "י" => "'", "Ї" => "I", "Ī" => "I", "І" => "I",
			"Й" => "N", "Ĵ" => "J",
			"ĸ" => "K", "כ" => "K", "Ķ" => "K", "К" => "K", "ך" => "T",
			"Ł" => "L", "Ŀ" => "L", "Л" => "n", "Ļ" => "L", "Ĺ" => "L", "Ľ" => "L", "ל" => "b",
			"מ" => "n", "М" => "M", "ם" => "o",
			"Ñ" => "N", "Ń" => "N", "Н" => "H", "Ņ" => "N", "ן" => "I", "Ŋ" => "N", "נ" => "j", "ŉ" => "N", "Ň" => "N",
			"Ø" => "O", "Ó" => "O", "Ò" => "O", "Ô" => "O", "Õ" => "O", "О" => "O", "Ő" => "O", "Ŏ" => "O", "Ō" => "O", "Ǿ" => "O", "Ǒ" => "O", "Ơ" => "O",
			"פ" => "e", "ף" => "P", "П" => "N",
			"ק" => "P",
			"Ŕ" => "R", "Ř" => "R", "Ŗ" => "R", "ר" => "R", "Р" => "R", "®" => "R",
			"Ş" => "S", "Ś" => "S", "Ș" => "S", "Š" => "S", "С" => "S", "Ŝ" => "S", "ס" => "o",
			"Т" => "T", "Ț" => "T", "ט" => "v", "Ŧ" => "T", "ת" => "n", "Ť" => "T", "Ţ" => "T",
			"Ù" => "U", "Û" => "U", "Ú" => "U", "Ū" => "U", "У" => "y", "Ũ" => "U", "Ư" => "U", "Ǔ" => "U", "Ų" => "U", "Ŭ" => "U", "Ů" => "U", "Ű" => "U", "Ǖ" => "U", "Ǜ" => "U", "Ǚ" => "U", "Ǘ" => "U",
			"В" => "B", "ו" => "i",
			"Ý" => "Y", "Ы" => "bi", "Ŷ" => "Y", "Ÿ" => "Y",
			"Ź" => "Z", "Ž" => "Z", "Ż" => "Z", "З" => "E", "ז" => "t",
			"а" => "a", "ă" => "a", "ǎ" => "a", "ą" => "a", "à" => "a", "ã" => "a", "á" => "a", "æ" => "ae", "â" => "a", "å" => "a", "ǻ" => "a", "ā" => "a", "א" => "N",
			"б" => "b", "ב" => "c", "þ" => "b",
			"ĉ" => "c", "ć" => "c", "ç" => "c", "ц" => "U", "צ" => "y", "ċ" => "c", "č" => "c", "©" => "c", "ץ" => "Y",
			"Ч" => "U", "ч" => "u",
			"д" => "A", "ď" => "d", "đ" => "d", "ד" => "T", "ð" => "d",
			"è" => "e", "ę" => "e", "é" => "e", "ë" => "e", "ê" => "e", "е" => "e", "ē" => "e", "ė" => "e", "ě" => "e", "ĕ" => "e", "є" => "e", "ə" => "e", "ע" => "y",
			"ф" => "o", "ƒ" => "f",
			"ğ" => "g", "ġ" => "g", "ģ" => "g", "ĝ" => "g", "г" => "r", "ג" => "A", "ґ" => "r",
			"ח" => "n", "ħ" => "h", "х" => "h", "ĥ" => "h", "ה" => "n",
			"i" => "i", "ï" => "i", "î" => "i", "í" => "i", "ì" => "i", "į" => "i", "ĭ" => "i", "ı" => "i", "и" => "N", "ĩ" => "i", "ǐ" => "i", "י" => "i", "ї" => "i", "ī" => "i", "і" => "i",
			"й" => "n", "Й" => "N", "Ĵ" => "j", "ĵ" => "j",
			"ĸ" => "k", "כ" => "c", "ķ" => "k", "к" => "k", "ך" => "r",
			"ł" => "l", "ŀ" => "l", "л" => "n", "ļ" => "l", "ĺ" => "l", "ľ" => "l", "ל" => "b",
			"מ" => "n", "м" => "M", "ם" => "o",
			"ñ" => "n", "ń" => "n", "н" => "H", "ņ" => "n", "ן" => "I", "ŋ" => "n", "נ" => "j", "ŉ" => "n", "ň" => "n",
			"ø" => "o", "ó" => "o", "ò" => "o", "ô" => "o", "õ" => "o", "о" => "o", "ő" => "o", "ŏ" => "o", "ō" => "o", "ǿ" => "o", "ǒ" => "o", "ơ" => "o",
			"פ" => "e", "ף" => "p", "п" => "n",
			"ק" => "P",
			"ŕ" => "r", "ř" => "r", "ŗ" => "r", "ר" => "r", "р" => "p", "®" => "R",
			"ş" => "s", "ś" => "s", "ș" => "s", "š" => "s", "с" => "c", "ŝ" => "s", "ס" => "o",
			"т" => "t", "ț" => "t", "ט" => "v", "ŧ" => "t", "ת" => "n", "ť" => "t", "ţ" => "t",
			"ù" => "u", "û" => "u", "ú" => "u", "ū" => "u", "у" => "y", "ũ" => "u", "ư" => "u", "ǔ" => "u", "ų" => "u", "ŭ" => "u", "ů" => "u", "ű" => "u", "ǖ" => "u", "ǜ" => "u", "ǚ" => "u", "ǘ" => "u",
			"в" => "B", "ו" => "i",
			"ý" => "y", "ы" => "bi", "ŷ" => "y", "ÿ" => "y",
			"ź" => "z", "ž" => "z", "ż" => "z", "з" => "e", "ז" => "t", "ſ" => "r",
			"™" => "tm",
			"@" => "a",
			"Ä" => "A", "Ǽ" => "AE", "ä" => "a", "æ" => "ae", "ǽ" => "ae",
			"ĳ" => "ij", "Ĳ" => "IJ",
			"я" => "r", "Я" => "R",
			"Э" => "E", "э" => "e",
			"ё" => "e", "Ё" => "E",
			"ю" => "o", "Ю" => "O",
			"œ" => "oe", "Œ" => "oe", "ö" => "o", "Ö" => "O",
			"щ" => "w", "Щ" => "W",
			"ш" => "w", "Ш" => "W",
			"ß" => "B",
			"Ü" => "U",
			"Ж" => "X", "ж" => "X",
		);
		return strtr($str, $char_map);
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
			"#denuncia" => array("#denuncia"),
			"#urgente" => array("#urgente"),
			"#liberdade" => array("#liberdade"),
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
	
	function define_action_on_twitter($tweet_id, $tweet_phrase, $tweet_author_id){
		$arr_command = find_first_command($tweet_phrase);
		if(is_null($arr_command)){return send_like($tweet_id);} //sem comandos, apenas dar like
		
		$commands_to_retweet = array("#denuncia", "#urgente", "#liberdade");
		if (in_array($arr_command["command"], $commands_to_retweet)) { //verifica se é um comando retweet 
			global $friends;
			if($friends == []){get_friends(); echo $friends;} //verifica se a lista de amigos está vazia, se tiver, cria.
			if(in_array($tweet_author_id, $friends)){ //verifica se esse twitte está na lista de amigos
				$new_tweet = send_retweet($tweet_id, $response_phrase); //se tiver, retwitta e salva o last_id
				set_last_id($new_tweet->id);
				return;
			}else{
				return send_like($tweet_id); //se não tiver na lista de amigos, apenas dá like
			}
		}else{
			send_response($tweet_id, command_phrase($arr_command)); //se não for retwitte, apenas responde
		}
	}
	
	/*---------- LOOPING - Responde os tweets ----------*/
	$tweet_list = last_mentions(get_last_id());
	set_last_id($tweet_list[0]->id);
	foreach ($tweet_list as $tweet){
		$tweet_id = $tweet->id;
		$tweet_phrase = $tweet->text;
		$tweet_author_id = $tweet->in_reply_to_user_id;
		define_action_on_twitter($tweet_id, $tweet_phrase, $tweet_author_id);
		echo 'Este túite '.$tweet_id.' foi respondido. <br/>'; //precisa disso?
	}

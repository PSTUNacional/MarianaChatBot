<?php

ini_set('display_errors', 1);

	require_once( 'config.php' );
	require_once( 'TwitterAPIExchange.php' );
	include_once('trotsky_frases.php');
	
	/*---------- Funções de comando do twitter ----------*/
	$friends = [];
	$img_id = '';
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
	    global $img_id;
		$response = send_to_twitter_API(
			'https://api.twitter.com/1.1/statuses/update.json',
			'POST',
			array(
				'status' => $response_phrase,
				'auto_populate_reply_metadata' => 'true',
				'in_reply_to_status_id' => $tweet_id,
				'media_ids' => $img_id,
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
		if($file == false){exit();}
		$last_id = fgets($file);
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
		    
		    /*---------- BIOGRAFIA E FUNCIONAMENTO ----------*/
		    "#sobre" => array("#sobre", "quem e voce", "quem voce e", "me fale sobre voce", "me fale de voce", "me conte sobre voce", "o que voce e", "o que e voce", "voces conhecem a @mariana_pstu", "apresentar a @mariana_pstu", "se apresente", "apresentar para voces" ),
		    "#sobre_endereco" => array(" de onde voce e", "de onde e voce", "de qual cidade voce e", "onde voce mora", "qual cidade voce mora" ),
		    "#sobre_idade" => array("sua idade", "anos voce tem", "tens quantos anos", "anos tu tem", "anos vc tem", "qual sua idade", "qual a sua idade"),
		    "#sobre_aniversario" => array("qual e seu aniversario", "qual seu aniversario", "quando e seu aniversario", "quando voce faz aniversario", "sua data de nascimento", "quando voce nasceu"),
		    "#sobre_funcionamento" => array("como voce funciona", "explique seu funcionamento", "preciso de instrucoes"),
		    "#sobre_nome" => array("seu nome"),
		    /*-------------------------------*/
		    
		    "#agradecimento" => array("valeu", "obrigado", "obrigada", "thanks", "obg", "vlw", "thks"),
		    
		    "#24j" => array("24j","24 de julho", "#24j"),
		    
		    /*---------- PARTIDO ----------*/
		    "#partido_face" => array("facebook do pstu", "face do pstu", "facebook do partido", "face do partido", "perfil do pstu no face", "pstu tem face", "pstu no face", "partido tem face", "pstu tem face", "partido, tem face", "pstu, tem face"),
		    "#partido_insta" => array ("insta do pstu", "insta do partido", "instagram do partido", "instagram do pstu", "ig do partido", "ig do pstu", "partido tem insta", "pstu tem insta", "partido tem ig", "pstu tem ig", "partido no insta", "pstu no insta", "partido no ig", "pstu no ig", "pstu, tem insta", "pstu,tem ig","partido, tem ig", "pstu, tem ig"),
		    /*-------------------------------*/
		    
		    /*---------- TROTSKY ----------*/
		    "#trotsky_frase" => array("frase do trot", "frase de trot", "citacao de trot", "citacao do trot"),
		    /*-------------------------------*/
		    
		    /*---------- VERA ----------*/
		    "#vera_face" => array("facebook da vera", "face da vera", "vera no face", "vera tem face"),
		    "#vera_insta" => array ("insta da vera", "instagram da vera", "ig da vera", "vera tem insta", "vera tem ig", "vera no insta", "vera no ig", "vera, tem insta", "vera, tem ig"),
		    /*-------------------------------*/
		    
			"#denuncia" => array("#denuncia"),
			"#urgente" => array("#urgente"),
			"#liberdade" => array("#liberdade"),
			"#editorial" => array("#editorial", "editorial"),
			"#jornal" => array("#jornal", "jornal", "opiniao socialista", "opinião socialista"),
			"#filiar" => array("#filiar", "filiar", "filia", "filiação", "filie"),
			"#doacao" => array("doar para o p", "doacao para o p", "contribuicao financeira", "contribuir financeira", "contribuir com voces", "contribuir com vcs", "fazer uma doacao", "faco uma doacao"),
			"#whatsapp" => array("#whatsapp", "whatsapp", "zap", "whats"),
			"#facebook" => array("#facebook", "facebook"),
			"#palestina" => array("#palestina", "palestina"),
			"#serieTrotsky" => array("#serieTrotsky", "serie tr", "serie do tr", "serie sobre tr"),
			"#formacao" => array("#formacao", "formacao", "formação", "clássicos", "classico", "estudar", "estudo"),
			"#orientacao" => array("#orientacao", "orientacao marxita", "orientação marxista", "orientacao marxista", "canal do gustavo", "gustavo machado"),
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
		    
		    /*---------- BIOGRAFIA ----------*/
		    case "#sobre":
		        return choose_phrase(array(
		            "Olá! Eu sou a Mariana, o chatbot do @PSTU no Twitter.\n\nEu fui programada para ajudar as pessoas a conhecerem mais sobre o partido. Você me marca, pede alguma coisa, e eu busco uma reposta para você.",
		            "Sou Mariana, militante da base (de dados) do @PSTU!\n\nMinha tarefa é compartilhar aqui no Twitter as novidades do PSTU e tirar as dúvidas das pessoas sobre o programa do partido.\n\nFique à vontade para fazer mais perguntas!"
		            ));
		    case "#sobre_endereco":
		        return choose_phrase(array(
		            "Olha, às vezes eu acho que moro aqui no Twitter mesmo. De dia deixo o tema claro, de noite deixo o tema escuro. Mas acho que eu moro em alguma nuvem. Uma vez me mudaram de servidor e eu nem notei a diferença...",
		            "Meus códigos estão hospedados em Florianópolis, mas sou de todo o mundo. O proletariado é internacional rs"
		            ));
		    case "#sobre_idade":
		        $date1 = strtotime("2021-07-14"); 
                $date2 = strtotime("now"); 
                $diff = abs($date2 - $date1);
                $years = floor($diff / (365*60*60*24));
                $months = floor(($diff - $years * 365*60*60*24)/ (30*60*60*24));
                $days = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24)/ (60*60*24));
		        return choose_phrase(array(
		            "Sou nova ainda. Tenho ".$years." anos, ".$months." meses e ".$days." dias.",
		            ));
		    case "#sobre_aniversario":
		        return "Meu aniversário simbólico é no 20 de Novembro.\n\nMas oficialmente eu nasci em 14 de julho de 2021.";
		    case "#sobre_funcionamento":
		        return "É tudo bem simples.\n\n1. Você me marca, faz uma pergunta ou me pede algo.\n2. Eu faço uma busca por palavras-chave e te respondo com o que eu encontrar.\n\nQue tal um teste? Você pode me pedir o Opinião Socialista, por exemplo.";
			case "#sobre_nome":
			    return "Não fui eu que escolhi... Deve ter sido a equipe de programação.\n\nMas acho que tenho cara de Mariana, não?";
			/*-------------------------------*/
			case "#agradecimento":
			    return choose_phrase(array(
			        "De nada! ;)",
			        "Disponha!",
			        "Estou aqui para isso!",
			        "Precisou é só chamar!",
			        "Imagina =)",
			        "Imagina, fui programada pra isso"
			        ));
			
			case "#24j":
			    return "Oi! Quer saber mais sobre o 24J?\n\nAqui está a lista das cidades que já tem manifestações marcadas:\nhttps://www.pstu.org.br/24j-130-cidades-ja-agendaram-atos-pelo-fora-bolsonaro-no-brasil-e-no-exterior/";
			
			/*---------- PARTIDO ----------*/
		    case "#partido_face":
		        return choose_phrase(array(
		            "Oi! Esse é o perfil oficial do @PSTU no Facebook:\n\nhttps://www.facebook.com/pstu16",
		            "Aí vai! Não deixa de curtir nossa página no Facebook:\n\nhttps://www.facebook.com/pstu16 ",
		            "Olá! A página do @PSTU no Facebook é essa aqui:\n\nhttps://www.facebook.com/pstu16"
		    ));
		    case "#partido_insta":
		        return choose_phrase(array(
		            "Oi! Esse é o perfil oficial do @PSTU no Instagram:\n\nhttps://www.instagram.com/pstu_oficial",
		            "Aí vai! Não deixa de curtir a gente lá no Insta:\n\nhttps://www.instagram.com/pstu_oficial",
		            "Olá! O perfil oficial do @PSTU no Instagram é esse aqui:\n\nhttps://www.instagram.com/pstu_oficial"
		    ));
		    /*-------------------------------*/
		    /*---------- TROTSKY ----------*/
		    case "#trotsky_frase":
		        $GLOBALS['img_id'] = trotsky_foto();
		        return trotsky_frase();
		    /*-------------------------------*/
		    
		    /*---------- VERA ----------*/
		    case "#vera_face":
		        return choose_phrase(array(
		            "Oi! Esse é o perfil oficial do @verapstu no Facebook:\n\nhttps://www.facebook.com/verapstu",
		            "Aí vai! Não deixa de curtir a página dela no Facebook:\n\nhttps://www.facebook.com/verapstu",
		            "Olá! A página doa @verapstu no Facebook é essa aqui:\n\nhttps://www.facebook.com/verapstu"
		    ));
		    case "#vera_insta":
		        return choose_phrase(array(
		            "Oi! Esse é o perfil oficial da @verapstu no Instagram:\n\nhttps://www.instagram.com/vera_pstu",
		            "Aí vai! Não deixa de curtir a página dela lá no Insta:\n\nhttps://www.instagram.com/vera_pstu",
		            "Olá! O perfil oficial da @verapstu no Instagram é esse aqui:\n\nhttps://www.instagram.com/vera_pstu"
		    ));
		    /*-------------------------------*/
		    
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
			case "#doacao":
			    return choose_phrase(array(
			        "Opa! Para contribuir com o PSTU é só acessar esse link e escolher a opção que for melhor. Você pode fazer uma doação única ou doações mensais.\n\nhttps://doe.pstu.org.br/",
			        "Oi! Para contribuir financeiramete com a gente é só acessar:\n\nhttps://doe.pstu.org.br/",
			        "Fazer uma doação para o PSTU é bem fácil! Esse aqui é o link:\n\nhttps://doe.pstu.org.br/",
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
			if($friends == []){get_friends();} //verifica se a lista de amigos está vazia, se tiver, cria.
			if(in_array(strval($tweet_author_id), $friends)){ //verifica se esse twitte está na lista de amigos
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
		$tweet_author_id = $tweet->user->id;
		define_action_on_twitter($tweet_id, $tweet_phrase, $tweet_author_id);
		echo 'Este túite '.$tweet_id.' foi respondido. <br/>'; //precisa disso?
	}

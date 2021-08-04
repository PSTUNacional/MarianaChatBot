<?php

ini_set('display_errors', 1);

	require_once( 'config.php' );
	require_once( 'TwitterAPIExchange.php' );
	
$settings = array(
    	'oauth_access_token' => TWITTER_ACCESS_TOKEN, 
    	'oauth_access_token_secret' => TWITTER_ACCESS_TOKEN_SECRET, 
    	'consumer_key' => TWITTER_CONSUMER_KEY, 
	    'consumer_secret' => TWITTER_CONSUMER_SECRET,
    );
	
/*---------- ALTERNADOR DE SITES ----------
    
    O script é rodado a cada 15 minutos. Portanto, nos minutos 00, 15, 30 e 45 da hora.
    
------------------------------------------*/
$minutos = date('i');

if ($minutos == 15 || $minutos == 45){
    $site = "pstu";
}else{
    $site = "lit";
}

/*---------- BUSCA NOVOS POSTS ----------*/
switch($site){
    case "pstu":
        $json_url = "https://pstu.org.br/wp-json/wp/v2/posts";
        break;
    case "lit":
        $json_url = "https://litci.org/pt/wp-json/wp/v2/posts";
        break;
}
$post = file_get_contents($json_url);
$post = json_decode($post);
$new_id = $post[0]->id;

/*---------- BUSCA O LAST ID ----------*/
$last_id_json = file_get_contents("last_id.json");
$last_id_json = json_decode($last_id_json, true);
$last_id = $last_id_json[$site];

/*---------- MAPA DAS EDITORIAS ----------*/
$editoria_id = array(
    '926' => 'Nacional',
    '918' => 'Artigo',
    '928' => 'Movimento',
    '927' => 'Internacional',
    '924' => 'Opressões',
    '925' => 'Partido',
    '929' => 'Juventude',
    '923' => 'Cultura',
    '936' => 'Socialismo',
    '6141' => 'Mundo Árabe',
    '5015' => 'Sudeste',
    '1364' => 'Raça e Classe',
    '6090' => 'Opinião Socialista',
    '1363' => 'Mulheres',
    '5018' => 'Nordeste',
    '1365' => 'LGBTI',
    '6826' => 'Minas Gerais',
    '5015' => 'Sul',
    '5017' => 'Norte',
    '5069' => 'Editorial',
    '6986' => 'Debates',
    '937' => 'Meio ambiente',
    '1100' => 'Saúde',
    '5016' => 'Centro Oeste',
    );

/*---------- MONTA O TWEET ----------*/
function monta_tweet(){
    global $post;
    global $site;
    global $tweet;
    global $editoria_id;
    switch ($site){
        case 'pstu':
            $editoria = $post[0]->categories[0];
            $tweet = strtoupper($editoria_id[$editoria])." | ".$post[0]->title->rendered."\n\n".$post[0]->link; 
            break;
        case 'lit':
            $tweet = "LIT-QI | ".$post[0]->title->rendered."\n\n".$post[0]->link;
            break;
    }
    return $tweet;
}

/*---------- POSTA O TWEET ----------*/
function envia_tweet(){
    
    global $tweet;
    global $settings;

    $url = 'https://api.twitter.com/1.1/statuses/update.json';
    $requestMethod = 'POST';
    $apiData = array(
	   'status' => $tweet,
	    );
    $twitter = new TwitterAPIExchange( $settings );
    $twitter->setPostfields( $apiData );   
    $twitter->buildOauth( $url, $requestMethod );
    $response = $twitter->performRequest(true, array (CURLOPT_SSL_VERIFYHOST => 0, CURLOPT_SSL_VERIFYPEER => 0));
    $lista = json_decode($response);
}

/*---------- CHECA SE TEM TEXTO NOVO ----------*/
if($new_id != $last_id){
    monta_tweet();
    envia_tweet();
    echo 'Tweet foi enviado';
    
    /*---------- REGISTRA NOVO ID ----------*/
    $new_array = array($site=>$new_id);
    $new_id_list = array_replace($last_id_json, $new_array);
    $new_id_list = json_encode($new_id_list);
    $fp = fopen("last_id.json", "w");
	    fwrite ($fp, $new_id_list);
	    fclose($fp);

}else{
    echo 'Nada de novo por aqui.';
}

?>

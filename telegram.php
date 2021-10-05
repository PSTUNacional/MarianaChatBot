<?php

define('BOT_TOKEN', /* AQUI VAI O TOKEN */);
define('API_URL', 'https://api.telegram.org/bot'.BOT_TOKEN.'/');

/* ========== INTERPRETA MENSAGENS ========== */

function processaMensagem($mensagem) {
  $mensagem_id = $mensagem['message_id'];
  $chat_id = $mensagem['chat']['id'];
  if (isset($mensagem['text'])) {
    
    $texto = $mensagem['text'];//texto recebido na mensagem

    if (strpos($texto, "/start") === 0) {
        

        enviaMensagem("sendMessage", array('chat_id' => $chat_id, "text" => "Olá, ". $mensagem['from']['first_name'].
		"!\n\nEu sou a Mariana, o chatbot do PSTU. Mas você pode me chamar de Mari. Eu ainda estou aprendendo como funcionam as coisas por aqui... mas se quiser já pode escolher uma das opções:",
		"reply_markup" => array(
                'keyboard' => array(
                    /* define uma coluna */
                        array('Opinião Socialista'),
                        array('Filiação'),
                        array('Doação')
                    ),
                'one_time_keyboard' => true)));
    } else if ($texto === "Opinião Socialista") {
      enviaMensagem("sendMessage", array('chat_id' => $chat_id, "text" => "Você quer a última edição ou quer dar uma olhada em todas elas?",
      "reply_markup" => array(
                'keyboard' => array(
                    /* define uma coluna */
                        array('Última edição'),
                        array('Quero ver todas')
                    ),
                'one_time_keyboard' => true)));
    } else if ($texto === "Filiação") {
      enviaMensagem("sendMessage", array('chat_id' => $chat_id, "text" => "Ai, que bom ". $mensagem['from']['first_name']."!\n\nFico muito feliz. Para se filiar é bem simples: é só acessar o nosso Faça Parte e faze o cadastro.\n\nDepois disso alguém vai entrar em contato com você para terminar todo o processo \n\n https://facaparte.pstu.org.br"));
    } else if ($texto === "Doação") {
      enviaMensagem("sendMessage", array('chat_id' => $chat_id, "text" => "Ótimo! para fazer uma doação é só acessar o nosso portal \n\n https://doe.pstu.org.br"));
    } else if ($texto === "Última edição") {
      enviaMensagem("sendMessage", array('chat_id' => $chat_id, "text" => "Certo. E você prefere em PDF ou o link para ler online?",
      "reply_markup" => array(
                'keyboard' => array(
                    /* define uma coluna */
                        array('Quero o PDF'),
                        array('Prefiro ler online')
                    ),
                'one_time_keyboard' => true)));
    } else if ($texto === "Quero o PDF") {
      enviaMensagem("sendMessage", array('chat_id' => $chat_id, "text" =>  $mensagem['from']['first_name'].", estou anexando o PDF aqui. Talvez demore uns segundinhos."));
        $filepath = 'https://www.pstu.org.br/OSarquivo/PDF/OS622_Baixa.pdf';
        $post = array('chat_id' => $chat_id,'document'=>new CurlFile($filepath));    
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,"https://api.telegram.org/bot". BOT_TOKEN ."/sendDocument");
        curl_setopt($ch, CURLOPT_POST, 1);   
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_exec ($ch);
        curl_close ($ch);
    } else if ($texto === "Prefiro ler online") {
        
    $url = "https://pstu.org.br/wp-json/wp/v2/posts?tags=6182";
    $jsonOS = file_get_contents($url);
    $jsonOS = json_decode($jsonOS);
    
      enviaMensagem("sendMessage", array('chat_id' => $chat_id, "text" => $mensagem['from']['first_name'].", essa é a última edição no nosso jornal:\n\n".$jsonOS[0]->link));
    } else if ($texto === "Quero ver todas") {
      enviaMensagem("sendMessage", array('chat_id' => $chat_id, "text" => "Ok. Para ver todas as edições do Opinião Socialsita é só acessar esse link:\n\nhttps://www.pstu.org.br/opiniaosocialista/"));
    } else {
      enviaMensagem("sendMessage", array('chat_id' => $chat_id, "text" => 'Desculpe, mas não entendi essa mensagem. :('));
    }
  } else {
    enviaMensagem("sendMessage", array('chat_id' => $chat_id, "text" => 'Desculpe, mas só compreendo mensagens em texto'));
  }
}

/* ====================================== */

/* ========== RECEBE MENSAGENS ========== */

function enviaMensagem($method, $parameters) {
  $options = array(
  'http' => array(
    'method'  => 'POST',
    'content' => json_encode($parameters),
    'header'=>  "Content-Type: application/json\r\n" .
                "Accept: application/json\r\n"
    )
);

$context  = stream_context_create( $options );
file_get_contents(API_URL.$method, false, $context );
}

/* ====================================== */

/* ========== RECEBE MENSAGENS ========== */

$update_response = file_get_contents("php://input");
$update = json_decode($update_response, true);

if (isset($update["message"])) {
  processaMensagem($update["message"]);
}

/* ====================================== */

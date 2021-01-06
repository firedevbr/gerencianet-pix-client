<?php

/*
 * Este arquivo foi implementado pela Consultoria Técnica Gerencianet.
 * Data da versão do arquivo: 26/11/2020.
 *
 * Link para documentação técnica da API-Pix da Gerencianet: https://gerencianet.com.br/api-pix
 * Você pode entrar em contato conosco sempre que precisar.
 * Visite o site da Gerencianet: https://www.gerencianet.com.br
*/

require "./funcoes.php";

// Insira o identificador da transação (txID) que deseja consultar
$txID = "YWBo8ejPAOkDviJ5Lb21Jb6CQ8rcVFAWOsb";

// Insira no arquivo config.json suas credenciais e o nome do seu arquivo de certificado .pem
$arquivo_config = file_get_contents("./config.json");
$configuracoes = json_decode($arquivo_config, true);
$ambiente = ($configuracoes["sandbox"] === true) ? "homologacao" : "producao"; // Define o ambiente de acordo com o sadbox em configurações

$arq_certificado = "./certificado/" . $configuracoes[$ambiente]["nome_certificado"]; // Local onde está salvo o certificado .pem

// Busca informações do token de autenticação de acordo com suas credencias e certificado
$dadosToken = getAccessToken($configuracoes[$ambiente]["pix_url_auth"], $arq_certificado, $configuracoes[$ambiente]["client_id"], $configuracoes[$ambiente]["client_secret"]);
$tokenType = $dadosToken['token_type'];
$accessToken = $dadosToken['access_token'];

$pix_url_cob = $configuracoes[$ambiente]["pix_url_cob"] . "/" . $txID; // Monta a url para a requisição

$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => $pix_url_cob,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "GET",
  CURLOPT_SSLCERT => $arq_certificado,
  CURLOPT_SSLCERTPASSWD => "",
  CURLOPT_HTTPHEADER => array(
    "authorization: $tokenType $accessToken"
  ),
));

$dadosPix = json_decode(curl_exec($curl), true);
curl_close($curl);

verficaFalhas($dadosPix); // Se encontrar falhas, apresentará a mensagem de erro e encerrará a execução

echo "<pre>"; 
var_dump($dadosPix);
echo "</pre>";
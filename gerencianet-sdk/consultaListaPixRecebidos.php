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

// Escolha os filtros que deseja para buscar a lista de PIX recebidos
$parametros = mapped_implode("&", [
  "inicio" => "2020-11-16" . "T00:00:00Z", // [obrigatório] 
  "fim" => "2020-11-26" . "T23:59:59Z", // [obrigatório] 
  //"txId" => "CD1FE328C875481285A6F233AE41B66" // [opcional] Filtro pelo Identificador da transação
  //"cpf" => "12345678901", // [opcional] Filtro pelo CPF do pagador. 
  //"cnpj" => "12345678901234", // [opcional] Filtro pelo CNPJ do pagador.
  //"paginacao.paginaAtual" => 2, // [opcional] Default: 0. Página a ser retornada pela consulta.
  //"paginacao.itensPorPagina" => 2 // [opcional] Default: 100. Quantidade máxima de registros retornados em cada página.
], "=");

// Insira no arquivo config.json suas credenciais e o nome do seu arquivo de certificado .pem
$arquivo_config = file_get_contents("./config.json");
$configuracoes = json_decode($arquivo_config, true);
$ambiente = ($configuracoes["sandbox"] === true) ? "homologacao" : "producao"; // Define o ambiente de acordo com o sadbox em configurações

$arq_certificado = "./certificado/" . $configuracoes[$ambiente]["nome_certificado"]; // Local onde está salvo o certificado .pem

$pix_url = $configuracoes[$ambiente]["pix_url"] . "?" . $parametros; // Monta a url para a requisição

// Busca informações do token de autenticação de acordo com suas credencias e certificado
$dadosToken = getAccessToken($configuracoes[$ambiente]["pix_url_auth"], $arq_certificado, $configuracoes[$ambiente]["client_id"], $configuracoes[$ambiente]["client_secret"]);
$token_type = $dadosToken['token_type'];
$accessToken = $dadosToken['access_token'];

$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => $pix_url,
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
    "authorization: $token_type $accessToken"
  ),
));

$listPixRecebidos = json_decode(curl_exec($curl), true);
curl_close($curl);

verficaFalhas($listPixRecebidos); // Se encontrar falhas, apresentará a mensagem de erro e encerrará a execução

echo "<pre>";
var_dump($listPixRecebidos);
echo "</pre>";

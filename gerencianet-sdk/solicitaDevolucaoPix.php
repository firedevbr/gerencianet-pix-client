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

/**
 * Representa uma solicitação de devolução de um Pix realizado, cujos fundos já se encontrem disponíveis na conta transacional do usuário recebedor
 * Estados da devolução:
 * a) EM_PROCESSAMENTO: indica que a devolução foi solicitada, mas ainda está em processamento no SPI;
 * b) DEVOLVIDO: indica que a devolução foi liquidada pelo SPI; e
 * c) NAO_REALIZADO: indica que a devolução não pode ser realizada em função de algum erro durante a liquidação (exemplo: saldo insuficiente).
 */


$id = getTxID('devolucao'); // Função gera um ID aleatório para representar unicamente uma devolução.

// EndToEndId da transação (e2eid) que deseja consultar. Obs: O e2eid é gerado em cobranças que foram quitadas
$e2eid = "E9040088820201121002700006627428";

// Insira no arquivo config.json suas credenciais e o nome do seu arquivo de certificado .pem
$arquivo_config = file_get_contents("./config.json");
$configuracoes = json_decode($arquivo_config, true);
$ambiente = ($configuracoes["sandbox"] === true) ? "homologacao" : "producao"; // Define o ambiente de acordo com o sadbox em configurações

$arq_certificado = "./certificado/" . $configuracoes[$ambiente]["nome_certificado"]; // Local onde está salvo o certificado .pem

// Busca informações do token de autenticação de acordo com suas credencias e certificado
$dadosToken = getAccessToken($configuracoes[$ambiente]["pix_url_auth"], $arq_certificado, $configuracoes[$ambiente]["client_id"], $configuracoes[$ambiente]["client_secret"]);
$tokenType = $dadosToken['token_type'];
$accessToken = $dadosToken['access_token'];

$pix_url = $configuracoes[$ambiente]["pix_url"] . "/$e2eid/devolucao/$id"; // Monta a url para a requisição de solicitação de devolução

// Insira as informações que deseja atualizar. Comentar a linha caso não deseja atualizar algum campo
$body = json_encode([
    "valor" =>  "0.01"
]);


$curl = curl_init();

curl_setopt_array($curl, array(
    CURLOPT_URL => $pix_url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "PUT",
    CURLOPT_SSLCERT => $arq_certificado,
    CURLOPT_SSLCERTPASSWD => "",
    CURLOPT_POSTFIELDS => $body,
    CURLOPT_HTTPHEADER => array(
        "authorization: $tokenType $accessToken",
        "Content-Type: application/json"
    ),
));

$dadosPix = json_decode(curl_exec($curl), true);
curl_close($curl);

verficaFalhas($dadosPix); // Se encontrar falhas, apresentará a mensagem de erro e encerrará a execução

echo "<pre>";
var_dump($dadosPix);
echo "</pre>";

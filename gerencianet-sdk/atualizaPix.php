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
 * Cenários para atualizar uma cobrança
 * 1. O usuário recebedor percebe que, por alguma razão, precisa alterar uma cobrança que foi anteriormente gerada;
 * 2. o usuário recebedor solicita a alteração da cobrança via API Pix; Serviço invocado: PUT/cob/{txid} → Devem ser passadas todas as informações corrigidas, conforme especificação detalhada.
 * 3. a cobrança é alterada e quando o pagador fizer uma nova leitura do QR Code, as informações estarão atualizadas;
 * 4. não se pode alterar e nem remover uma cobrança cujo status esteja em CONCLUÍDA. O Status CONCLUÍDA é final; 
 * 5. uma cobrança terá seu status alterado para CONCLUÍDA quando um Pix associado ao txid da cobrança for recebido.
 */

// Insira o identificador da transação (txID) que deseja atualizar
$txID = "IKODL7F26VZBHDOP71H0J71MMVDVAKWDYX4";

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

// Insira as informações que deseja atualizar. Comentar a linha caso não deseja atualizar algum campo
$body = json_encode([
    "calendario" => [
        "expiracao" => "36000"
    ],
    "devedor" => [
        "cpf" => "12345678900",
        "nome" => "Nome Aqui"
    ],
    "status" => "REMOVIDA_PELO_USUARIO_RECEBEDOR", // [opcional] Opções: "REMOVIDA_PELO_USUARIO_RECEBEDOR", "REMOVIDA_PELO_PSP"
    "valor" => [
        "original" => "1.85"
    ],
    "chave" => $configuracoes["chave"],
    "solicitacaoPagador" => "Serviço realizado (valor atualizado).",
    "infoAdicionais" => [
        [
            "nome" => "Campo 1 (atualizado)",
            "valor" => "Informação Adicional1 do PSP-Recebedor (atualizado)"
        ],
        [
            "nome" => "Campo 2 (atualizado)",
            "valor" => "Informação Adicional2 do PSP-Recebedor (atualizado)"
        ]
    ]
]);


$curl = curl_init();

curl_setopt_array($curl, array(
    CURLOPT_URL => $pix_url_cob,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "PATCH",
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

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
require "./montaBrCode.php";

$tipo = "dinamico"; // Insira o tipo de QR Code que deseja gerar. Opções: dinamico ou estatico

$tamanhoQrCode = "250"; // Insira o tamanho em pixels do QR Code que será gerado. Obs: Tamanho deve ser m sou igual a 256px

$pagoUmaVez = false; // Insira se deseja que o QR Code pode ser pago uma única vez. Opções: true ou false

$valorLivre = false; // Insira se deseja que o valor da cobrança seja livre. Opções: true ou false

$cidade = "Ouro Preto"; // [obrigatório] cidade onde é efetuada a transação

$cep = "35400000"; // [opcional] CEP da localidade onde é efetuada a transação


// Insira no arquivo config.json suas credenciais e o nome do seu arquivo de certificado .pem
$arquivo_config = file_get_contents("./config.json");
$configuracoes = json_decode($arquivo_config, true);
$ambiente = ($configuracoes["sandbox"] === true) ? "homologacao" : "producao"; // Define o ambiente de acordo com o sadbox em configurações
$nomeRecebedor = $configuracoes["recebedor"];

$arq_certificado = "./certificado/" . $configuracoes[$ambiente]["nome_certificado"]; // Local onde está salvo o certificado .pem

// Busca informações do token de autenticação de acordo com suas credencias e certificado
$dadosToken = getAccessToken($configuracoes[$ambiente]["pix_url_auth"], $arq_certificado, $configuracoes[$ambiente]["client_id"], $configuracoes[$ambiente]["client_secret"]);
$tokenType = $dadosToken['token_type'];
$accessToken = $dadosToken['access_token'];

$txID = getTxID($tipo); // Função gera um txID aleatório obedecendo a regex de cada tipo de QR Code
$pix_url_cob = $configuracoes[$ambiente]["pix_url_cob"] . "/" . $txID; // Monta a url para a requisição que gera a cobrança

// Insira as informações da cobrança
$body = [
  "calendario" => [
    "expiracao" => 3600 // [opcional] Tempo de vida da cobrança, especificado em segundos a partir da data de criação. Caso não definido o padrão será de 86400 segundos ( 24 horas)
  ],
  "devedor" => [
    "cpf" => "12345678900", // [opcional] CPF do usuário pagador.string /^\d{11}$/
    //"cnpj" => "12345678000123",
    "nome" => "Nome Aqui" // [opcional] Nome do usuário pagador. Máximo: 25 caracteres.
  ],
  "valor" => [
    "original" => "1.49" // [obrigatório] Valor original da cobrança.string \d{1,10}.\d{2} Obs: Para QR Code dinâmico, valor mínimo é de 0.01. Para QR Code poderá ser 0.00 (Ficará aberto para o pagador definir o valor)
  ],
  "chave" => $configuracoes["chave"], // [obrigatório] Determina a chave Pix registrada no DICT que será utilizada para a cobrança.
  "solicitacaoPagador" => "Serviço realizado.", // [opcional] determina um texto a ser apresentado ao pagador para que ele possa digitar uma informação correlata, em formato livre, a ser enviada ao recebedor.
  "infoAdicionais" => [ // [opcional] Cada respectiva informação adicional contida na lista (nome e valor) deve ser apresentada ao pagador. Campo presente somente no QR Code dinâmico
    [
      "nome" => "Campo 1", // Nome do campo string (Nome) ≤ 50 characters
      "valor" => "Informação Adicional1 do PSP-Recebedor" // Dados do campo string (Valor) ≤ 200 characters
    ],
    [
      "nome" => "Campo 2",
      "valor" => "Informação Adicional2 do PSP-Recebedor"
    ]
  ]
];


if ($tipo === "dinamico") { // Somente será gerado o payload para o QR Code dinâmico
  $dadosPix = emitePix($pix_url_cob, $arq_certificado, json_encode($body), $tokenType, $accessToken);

  verficaFalhas($dadosPix); // Se encontrar falhas, apresentará a mensagem de erro e encerrará a execução
} else { // Caso seja estático enviará as informações contidas no $body
  $dadosPix = $body;
  $dadosPix["txid"] = $txID;
}

$imagemQrCode = montaBrCode($dadosPix, $tipo, $pagoUmaVez, $nomeRecebedor, $cidade, $cep, $valorLivre, $tamanhoQrCode); // Monta o pix Copia e Cola e a imagem do QR Code

echo $imagemQrCode;

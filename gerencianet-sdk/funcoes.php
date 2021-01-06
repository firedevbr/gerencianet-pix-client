<?php

/*
 * Este arquivo foi implementado pela Consultoria Técnica Gerencianet.
 * Data da versão do arquivo: 26/11/2020.
 *
 * Link para documentação técnica da API-Pix da Gerencianet: https://gerencianet.com.br/api-pix
 * Você pode entrar em contato conosco sempre que precisar.
 * Visite o site da Gerencianet: https://www.gerencianet.com.br
*/

function getTxID($tipo)
{
  /*
   # Esta rotina gera um txID de forma aleatória, atendendo à regex `[a-zA-Z0-9]{26,35}$` caso o QR Code seja dinâmico, e à regex [a-zA-Z0-9]{1,25} caso seja estático, conforme padrão bacen
   */

  $quantidade = ($tipo === "dinamico") ? 35 : 25;

  for (
    $id = '', $i = 0, $z = strlen($a = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789') - 1;
    $i != $quantidade;
    $x = rand(0, $z), $id .= $a{
      $x}, $i++
  );
  return $id;
}


function emitePix($pix_url_cob, $arq_certificado, $body, $tokenType, $accessToken)
{
  /*
* Esta rotina consome um endpoid PUT da Gerencianet para emissão da cobrança
*/
  $curl = curl_init();

  curl_setopt_array($curl, array(
    CURLOPT_URL => $pix_url_cob,
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

  return $dadosPix;
}


function getAccessToken($pix_url_auth, $arq_certificado, $client_id, $client_secret)
{
  /*
   # Esta rotina consome um endpoid POST da Gerencianet para realizar a geração do AccessToken
   */

  $curl = curl_init();

  $authorization =  base64_encode("$client_id:$client_secret");

  curl_setopt_array($curl, array(
    CURLOPT_URL => $pix_url_auth, // Rota base, desenvolvimento ou produção
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => '{"grant_type": "client_credentials"}',
    CURLOPT_SSLCERT => $arq_certificado, // Caminho do certificado
    CURLOPT_SSLCERTPASSWD => "",
    CURLOPT_HTTPHEADER => array(
      "Authorization: Basic $authorization",
      "Content-Type: application/json"
    ),
  ));

  $response = curl_exec($curl);

  curl_close($curl);

  return json_decode($response, true);
}


function getPayload($location)
{
  /*
   # Esta rotina consome a URL location da cobrança buscando as informações do JWS
   */
  $curl = curl_init();

  curl_setopt_array($curl, array(
    CURLOPT_URL => $location,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_USERAGENT => "Mozilla/5.0 (Windows NT 10.0;Win64;x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36"
  ));

  $objectJWS = curl_exec($curl);
  curl_close($curl);

  if (is_array(json_decode($objectJWS, true))) { // Se no retorno apresentar erro, será um array, então retornará a mensagem de erro ocorrida
    $message = json_decode($objectJWS, true);
    return $message;
  } else {
    $objectJWS = explode(".", $objectJWS);
    return json_decode(base64_decode($objectJWS[1]), true); // Obtendo suesso, retornará as informações da cobrança
  }
}

function preencheCampo($valor)
{
  /*
   * Esta função retorna a string preenchendo com 0 na esquerda, com tamanho o especificado, concatenando com o valor do campo
   */
  return str_pad(strlen($valor), 2, '0', STR_PAD_LEFT) . $valor;
}


function calculaChecksum($str)
{
  /*
   * Esta função auxiliar calcula o CRC-16/CCITT-FALSE
   */

  function charCodeAt($str, $i)
  {
    return ord(substr($str, $i, 1));
  }

  $crc = 0xFFFF;
  $strlen = strlen($str);
  for ($c = 0; $c < $strlen; $c++) {
    $crc ^= charCodeAt($str, $c) << 8;
    for ($i = 0; $i < 8; $i++) {
      if ($crc & 0x8000) {
        $crc = ($crc << 1) ^ 0x1021;
      } else {
        $crc = $crc << 1;
      }
    }
  }
  $hex = $crc & 0xFFFF;
  $hex = dechex($hex);
  $hex = strtoupper($hex);

  return $hex;
}

function gerarQrCode($payloadBrCode, $tamanhoQrCode)
{
  /*
   * Esta rotina consome uma api da Gerencianet que gera o QR Code
   * 
   * Link para Repositório da API: https://github.com/ceciliadeveza/gerarqrcodepix
   */
  $curl = curl_init();

  curl_setopt_array($curl, array(
    CURLOPT_URL => "https://gerarqrcodepix.com.br/api/v1?brcode=$payloadBrCode&tamanho=$tamanhoQrCode",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'GET',
  ));

  $imagemQrCode = curl_exec($curl);

  curl_close($curl);

  return base64_encode($imagemQrCode);
}


function verficaFalhas($dadosPix)
{
  if (isset($dadosPix["mensagem"]) || !$dadosPix) { // Se apresentar falha, irá mostrar a mensagem
    echo (!$dadosPix) ? "Falha ao gerar a cobrança, tente novamente" : "Falha PIX<br>Mensagem: " . $dadosPix["mensagem"];

    if (isset($dadosPix["erros"])) { // Se apresentar erro, irá mostrar o erro e o campo
      foreach ($dadosPix["erros"] as $key => $erro) {
        echo "<br><br>Erro " . ($key + 1) . "<br>Caminho: " . $erro["caminho"] . "<br>Mensagem de erro: " . $erro["mensagem"] . "<br><br>";
      }
    }
    exit;
  }
}


function mapped_implode($glue, $array, $symbol = '=')
{
  return implode($glue, array_map(
    function ($k, $v) use ($symbol) {
      return $k . $symbol . $v;
    },
    array_keys($array),
    array_values($array)
  ));
}

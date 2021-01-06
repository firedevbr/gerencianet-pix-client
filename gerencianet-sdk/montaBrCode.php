<?php

/*
 * Este arquivo foi implementado pela Consultoria Técnica Gerencianet.
 * Data da versão do arquivo: 26/11/2020.
 *
 * Link para documentação técnica da API-Pix da Gerencianet: https://gerencianet.com.br/api-pix
 * Você pode entrar em contato conosco sempre que precisar.
 * Visite o site da Gerencianet: https://www.gerencianet.com.br
*/

function montaBrCode($dadosPix, $tipo, $pagoUmaVez, $nomeRecebedor, $cidade, $cep, $valorLivre, $tamanhoQrCode)
{

  // Rotina montará a variável que correspondente ao payload no padrão EMV-QRCPS-MPM
  $payload_format_indicator = '01';
  $point_of_initiation_method = '12';
  $merchant_account_information = '00' . preencheCampo('BR.GOV.BCB.PIX');
  $merchant_category_code = '0000';
  $transaction_currency = '986';
  $country_code = 'BR';

  $payloadBrCode = "00" . preencheCampo($payload_format_indicator); // [obrigatório] Payload Format Indicator, valor fixo: 01

  if ($pagoUmaVez) { // Se o QR Code for para pagamento único (só puder ser utilizado uma vez), a variável $pagoUmaVez deverá ser true
    $payloadBrCode .= "01" . preencheCampo($point_of_initiation_method); // [opcional] Point of Initiation Method Se o valor 12 estiver presente, significa que o BR Code só pode ser utilizado uma vez.
  }

  if ($tipo === "dinamico") {
    $location = str_replace("https://", "", $dadosPix["loc"]["location"]); // [obrigatório] URL payload do PSP do recebedor que contém as informações da cobrança
    $merchant_account_information .= '25' . preencheCampo($location);
  } else { // Caso seja estático
    $merchant_account_information .= '01' . preencheCampo($dadosPix["chave"]); //Chave do destinatário do pix, pode ser EVP, e-mail, CPF ou CNPJ.
  }
  $payloadBrCode .= '26' .  preencheCampo($merchant_account_information); // [obrigatório] Indica arranjo específico; “00” (GUI) e valor fixo: br.gov.bcb.pix

  $payloadBrCode .= '52' . preencheCampo($merchant_category_code); // [obrigatório] Merchant Category Code “0000” ou MCC ISO18245

  $payloadBrCode .= '53' . preencheCampo($transaction_currency); // [obrigatório] Moeda, “986” = BRL: real brasileiro - ISO4217

  $payloadBrCode .= '54';  // [opcional] Valor da transação. Utilizar o . como separador decimal.
  $payloadBrCode .= ($valorLivre === true) ? preencheCampo('0.00') : preencheCampo($dadosPix["valor"]["original"]) ;

  $payloadBrCode .= '58' . preencheCampo($country_code); // [obrigatório] “BR” – Código de país ISO3166-1 alpha 2

  $payloadBrCode .= '59';
  $payloadBrCode .= preencheCampo($nomeRecebedor); // [obrigatório] Nome do beneficiário/recebedor. Máximo: 25 caracteres.

  $payloadBrCode .= '60' . preencheCampo($cidade); // [obrigatório] Nome cidade onde é efetuada a transação. Máximo 15 caracteres.

  $payloadBrCode .= '61' . preencheCampo($cep); // [opcional] CEP da cidade onde é efetuada a transação.

  $txID = ($tipo === "dinamico") ? '***' : $dadosPix["txid"]; // [opcional] Identificador da transação.
  $aditional_data_field_template = '05' . preencheCampo($txID); 
  $payloadBrCode .= '62' . preencheCampo($aditional_data_field_template);


  $payloadBrCode .= "6304"; // Adiciona o campo do CRC no fim da linha do pix.

  $payloadBrCode .= calculaChecksum($payloadBrCode); // Calcula o checksum CRC16 e acrescenta ao final.


  echo "Pix Copia e Cola: <input readonly='readonly' id='inputTest' value='$payloadBrCode' /> <button onclick='copiarTexto()''>Copiar</button> <br>";
  echo "<script>
              let copiarTexto = () =>{
                  //captura o elemento input
                  const inputTest = document.querySelector('#inputTest');
                  
                  //seleciona todo o texto do elemento
                  inputTest.select();
                  //executa o comando copy
                  //aqui é feito o ato de copiar para a area de trabalho com base na seleção
                  document.execCommand('copy');
              };
          </script>";

  $imageString = gerarQrCode($payloadBrCode, $tamanhoQrCode);

  // Exibe a imagem diretamente no navegador codificada em base64.
  return '<img src="data:image/png;base64,' . $imageString . '"></center>';
}

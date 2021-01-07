<?php


namespace Firedev\Pix\Gerencianet;


class QrCodeGenerator
{
    /**
     * @var \GuzzleHttp\Client
     */
    private $httpClient;

    /**
     * @var string
     */
    private $nomeRecebedor;

    /**
     * @var string
     */
    private $cidadeRecebedor;

    /**
     * @var string
     */
    private $cepRecebedor;

    /**
     * QrCodeClient constructor.
     * @param string $nomeRecebedor
     * @param string $cidadeRecebedor
     * @param string $cepRecebedor
     */
    public function __construct(string $nomeRecebedor, string $cidadeRecebedor, string $cepRecebedor)
    {
        $this->httpClient      = new \GuzzleHttp\Client([
            'base_uri' => "https://gerarqrcodepix.com.br/api/"
        ]);
        $this->nomeRecebedor   = $nomeRecebedor;
        $this->cidadeRecebedor = $cidadeRecebedor;
        $this->cepRecebedor    =  $cepRecebedor;
    }

    public function geraQrCodeEmBase64PelaCobranca(Cobranca $cobranca, $tamanhoQrCode = 250): string
    {
        $payloadBrCode = $this->montaBrCode($cobranca);
        $response = $this->httpClient->request(
            "GET",
            "v1?brcode={$payloadBrCode}&tamanho={$tamanhoQrCode}"
        );

        return base64_encode($response->getBody()->getContents());
    }

    private function montaBrCode(
        Cobranca $cobranca,
        $pagoUmaVez = true,
        $tipo = "dinamico",
        $valorLivre = false
    ): string {

        // Rotina montará a variável que correspondente ao payload no padrão EMV-QRCPS-MPM
        $payload_format_indicator = '01';
        $point_of_initiation_method = '12';
        $merchant_account_information = '00' . $this->preencheCampo('BR.GOV.BCB.PIX');
        $merchant_category_code = '0000';
        $transaction_currency = '986';
        $country_code = 'BR';

        $payloadBrCode = "00" . $this->preencheCampo($payload_format_indicator); // [obrigatório] Payload Format Indicator, valor fixo: 01

        if ($pagoUmaVez) { // Se o QR Code for para pagamento único (só puder ser utilizado uma vez), a variável $pagoUmaVez deverá ser true
            $payloadBrCode .= "01" . $this->preencheCampo($point_of_initiation_method); // [opcional] Point of Initiation Method Se o valor 12 estiver presente, significa que o BR Code só pode ser utilizado uma vez.
        }

        if ($tipo === "dinamico") {
            $location = str_replace("https://", "", $cobranca->getUrlPayloadQrCode()); // [obrigatório] URL payload do PSP do recebedor que contém as informações da cobrança
            $merchant_account_information .= '25' . $this->preencheCampo($location);
        } else { // Caso seja estático
            $merchant_account_information .= '01' . $this->preencheCampo($cobranca->getChaveCobrador()); //Chave do destinatário do pix, pode ser EVP, e-mail, CPF ou CNPJ.
        }
        $payloadBrCode .= '26' .  $this->preencheCampo($merchant_account_information); // [obrigatório] Indica arranjo específico; “00” (GUI) e valor fixo: br.gov.bcb.pix

        $payloadBrCode .= '52' . $this->preencheCampo($merchant_category_code); // [obrigatório] Merchant Category Code “0000” ou MCC ISO18245

        $payloadBrCode .= '53' . $this->preencheCampo($transaction_currency); // [obrigatório] Moeda, “986” = BRL: real brasileiro - ISO4217

        $payloadBrCode .= '54';  // [opcional] Valor da transação. Utilizar o . como separador decimal.
        $payloadBrCode .= ($valorLivre === true) ? $this->preencheCampo('0.00') : $this->preencheCampo($cobranca->getValorOriginal()) ;

        $payloadBrCode .= '58' . $this->preencheCampo($country_code); // [obrigatório] “BR” – Código de país ISO3166-1 alpha 2

        $payloadBrCode .= '59';
        $payloadBrCode .= $this->preencheCampo($this->nomeRecebedor); // [obrigatório] Nome do beneficiário/recebedor. Máximo: 25 caracteres.

        $payloadBrCode .= '60' . $this->preencheCampo($this->cidadeRecebedor); // [obrigatório] Nome cidade onde é efetuada a transação. Máximo 15 caracteres.

        $payloadBrCode .= '61' . $this->preencheCampo($this->cepRecebedor); // [opcional] CEP da cidade onde é efetuada a transação.

        $txID = ($tipo === "dinamico") ? '***' : $cobranca->getIdTransacao(); // [opcional] Identificador da transação.
        $aditional_data_field_template = '05' . $this->preencheCampo($txID);
        $payloadBrCode .= '62' . $this->preencheCampo($aditional_data_field_template);


        $payloadBrCode .= "6304"; // Adiciona o campo do CRC no fim da linha do pix.

        $payloadBrCode .= $this->calculaChecksum($payloadBrCode); // Calcula o checksum CRC16 e acrescenta ao final.

        return $payloadBrCode;
    }

    private function calculaChecksum($str)
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

    private function preencheCampo($valor)
    {
        /*
         * Esta função retorna a string preenchendo com 0 na esquerda, com tamanho o especificado, concatenando com o valor do campo
         */
        return str_pad(strlen($valor), 2, '0', STR_PAD_LEFT) . $valor;
    }
}
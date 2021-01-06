<?php


namespace Firedev\Pix\Gerencianet;


class Client
{
    /**
     * @var string
     */
    private $caminhoArquivoCertificado;

    /**
     * @var string
     */
    private $clientId;

    /**
     * @var string
     */
    private $clientSecret;

    /**
     * @var array
     */
    private $infoAmbientes;

    private $httpClient;



    /**
     * Client constructor.
     * @param $caminhoArquivoCertificado
     * @param $clientId
     * @param $clientSecret
     * @param $httpClient
     */
    public function __construct($caminhoArquivoCertificado, $clientId, $clientSecret)
    {
        $this->caminhoArquivoCertificado  = $caminhoArquivoCertificado;
        $this->clientId                   = $clientId;
        $this->clientSecret               = $clientSecret;
        $this->inicializar();
    }

    private function inicializar()
    {
        $this->infoAmbientes = json_decode(file_get_contents(__DIR__ . '/info-ambientes.json'), true);
        $this->httpClient    = new \GuzzleHttp\Client([
            'base_uri' => $this->infoAmbientes['homologacao']['pix_base_uri'],
            'timeout'  => 5
        ]);
    }
}
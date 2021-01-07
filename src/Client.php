<?php


namespace Firedev\Pix\Gerencianet;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Cache\CacheItemPoolInterface;

class Client
{
    const MODO_PRODUCAO    = 'producao';

    const MODO_HOMOLOGACAO = 'homologacao';

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

    /**
     * @var ClientInterface
     */
    private $httpClient;

    /**
     * @var string
     */
    private $modo;

    /**
     * @var CacheItemPoolInterface
     */
    private $cache;

    /**
     * Client constructor.
     * @param $caminhoArquivoCertificado
     * @param $clientId
     * @param $clientSecret
     * @param $httpClient
     */
    public function __construct(
        $caminhoArquivoCertificado,
        $clientId,
        $clientSecret,
        CacheItemPoolInterface $cache
    ) {
        $this->caminhoArquivoCertificado  = $caminhoArquivoCertificado;
        $this->clientId                   = $clientId;
        $this->clientSecret               = $clientSecret;
        $this->cache                      = $cache;
        $this->inicializar();
    }

    /**
     * Reescreve o client http com a url base da API de produção da gerencianet e seta o modo como produção.
     */
    public function ativaModoProducao(): void
    {
        $handler = new OauthHandler(
            $this->caminhoArquivoCertificado,
            $this->clientId,
            $this->clientSecret,
            $this->cache,
            $this->infoAmbientes['producao']['pix_url_auth']
        );

        $stack = HandlerStack::create();
        $stack->push(Middleware::mapRequest($handler));

        $this->httpClient = new \GuzzleHttp\Client([
            'base_uri' => $this->infoAmbientes['producao']['pix_url_base'],
            'timeout'  => 5,
            'handler'  => $handler
        ]);

        $this->modo = self::MODO_PRODUCAO;
    }

    /**
     * Lê o arquivo de configuração e inicializa o componente de comunicação http.
     * Por padrão o client é instanciado em modo de desenvolvimento,
     * se comunicando com a API de homologação da Gerencianet.
     */
    private function inicializar(): void
    {
        $this->infoAmbientes = json_decode(file_get_contents(__DIR__ . '/info-ambientes.json'), true);
        $handler = new OauthHandler(
            $this->caminhoArquivoCertificado,
            $this->clientId,
            $this->clientSecret,
            $this->cache,
            $this->infoAmbientes['homologacao']['pix_url_auth']
        );

        $stack = HandlerStack::create();
        $stack->push(Middleware::mapRequest($handler));

        $this->httpClient    = new \GuzzleHttp\Client([
            'base_uri' => $this->infoAmbientes['homologacao']['pix_url_base'],
            'timeout'  => 5,
            'cert'     => $this->caminhoArquivoCertificado,
            'handler'  => $stack
        ]);

        $this->modo = self::MODO_HOMOLOGACAO;
    }

    public function estaEmModoProducao(): bool
    {
        return $this->modo === self::MODO_PRODUCAO;
    }

    public function buscaDadosCobranca(string $chaveCobranca): Cobranca
    {
        $response = $this->httpClient->request('GET', 'cob/' . $chaveCobranca);
        return new Cobranca(json_decode($response->getBody()->getContents(), true));
    }

    public function geraNovaCobranca($idDeTransacao = null)
    {
        if (is_null($idDeTransacao)) {
            $idDeTransacao = $this->geraIdCobrancaAleatoria();
        }

    }

    public function geraIdCobrancaAleatoria($tipo = "dinamico"): string
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
}
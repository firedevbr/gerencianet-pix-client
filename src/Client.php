<?php


namespace Firedev\Pix\Gerencianet;


use Firedev\Pix\Gerencianet\Exception\BuscaAccessTokenException;
use GuzzleHttp\ClientInterface;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Contracts\Cache\ItemInterface;

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
        $this->httpClient = new \GuzzleHttp\Client([
            'base_uri' => $this->infoAmbientes['producao']['pix_url_base'],
            'timeout'  => 5
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
        $this->httpClient    = new \GuzzleHttp\Client([
            'base_uri' => $this->infoAmbientes['homologacao']['pix_url_base'],
            'timeout'  => 5
        ]);
        $this->modo = self::MODO_HOMOLOGACAO;
    }

    public function estaEmModoProducao(): bool
    {
        return $this->modo === self::MODO_PRODUCAO;
    }

    /**
     * @return AccessTokenInterface
     * @throws BuscaAccessTokenException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function buscaAccessToken(): AccessToken
    {
        /** @var ItemInterface $item */
        $item = $this->cache->getItem('gerencianet-pix-client-access-token');

        if (is_null($item->get())) {
            $newAccessToken = $this->pedeNovoTokenParaAPI();
            $item->set($newAccessToken->jsonSerialize());
            $this->cache->save($item);
            return $newAccessToken;
        }

        /** @var AccessToken $accessToken */
        $accessToken = new AccessToken($item->get());

        if ($accessToken->hasExpired()) {
            $newAccessToken = $this->pedeNovoTokenParaAPI();
            $item->set($newAccessToken->jsonSerialize());
            $this->cache->save($item);
            return $newAccessToken;
        }

        return $accessToken;
    }

    /**
     * @return AccessToken
     * @throws BuscaAccessTokenException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function pedeNovoTokenParaAPI(): AccessToken
    {
        try {
            $response = $this->httpClient->request('POST', $this->infoAmbientes[$this->modo]['pix_url_auth'], [
                'json' => [
                    'grant_type' => 'client_credentials'
                ],
                'auth' => [
                    $this->clientId,
                    $this->clientSecret
                ],
                'cert' => $this->caminhoArquivoCertificado
            ]);

            $accessToken = new AccessToken(
                json_decode($response->getBody()->getContents(), true)
            );

            return $accessToken;
        } catch (\Exception $exception) {
            throw new BuscaAccessTokenException($exception->getMessage());
        }
    }
}
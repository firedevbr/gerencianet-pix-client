<?php


namespace Firedev\Pix\Gerencianet;


use Firedev\Pix\Gerencianet\Exception\BuscaAccessTokenException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\RequestInterface;
use Symfony\Contracts\Cache\ItemInterface;

class OauthHandler
{
    /**
     * @var \GuzzleHttp\Client
     */
    private $httpClient;

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
     * @var CacheItemPoolInterface
     */
    private $cache;

    /**
     * @var string
     */
    private $oauthUrl;

    /**
     * OauthHandler constructor.
     * @param \GuzzleHttp\Client $httpClient
     * @param string $caminhoArquivoCertificado
     * @param string $clientId
     * @param string $clientSecret
     * @param CacheItemPoolInterface $cache
     * @param string $oauthUrl
     */
    public function __construct(
        string $caminhoArquivoCertificado,
        string $clientId,
        string $clientSecret,
        CacheItemPoolInterface $cache,
        string $oauthUrl
    ) {
        $this->httpClient                = new \GuzzleHttp\Client();
        $this->caminhoArquivoCertificado = $caminhoArquivoCertificado;
        $this->clientId                  = $clientId;
        $this->clientSecret              = $clientSecret;
        $this->cache                     = $cache;
        $this->oauthUrl                  = $oauthUrl;
    }

    /**
     * @param RequestInterface $request
     * @param array $options
     * @return RequestInterface
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function __invoke(RequestInterface $request, array $options = []): RequestInterface
    {
        return $request->withHeader('authorization', $this->getAuthorizationHeader());
    }

    /**
     * @return string
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getAuthorizationHeader()
    {
        return 'Bearer ' . $this->getBearerToken();
    }

    /**
     * @return AccessTokenInterface
     * @throws BuscaAccessTokenException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function getBearerToken(): string
    {
        /** @var ItemInterface $item */
        $item = $this->cache->getItem('gerencianet-pix-client-access-token');

        if (is_null($item->get())) {
            $newAccessToken = $this->pedeNovoTokenParaAPI();
            $item->set($newAccessToken->jsonSerialize());
            $this->cache->save($item);
            return $newAccessToken->getToken();
        }

        /** @var AccessToken $accessToken */
        $accessToken = new AccessToken($item->get());

        if ($accessToken->hasExpired()) {
            $newAccessToken = $this->pedeNovoTokenParaAPI();
            $item->set($newAccessToken->jsonSerialize());
            $this->cache->save($item);
            return $newAccessToken->getToken();
        }

        return $accessToken->getToken();
    }

    /**
     * @return AccessToken
     * @throws BuscaAccessTokenException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function pedeNovoTokenParaAPI(): AccessToken
    {
        try {
            $response = $this->httpClient->request('POST', $this->oauthUrl, [
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
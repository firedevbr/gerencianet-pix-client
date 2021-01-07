<?php


namespace Firedev\Pix\Gerencianet;


class Cobranca
{
    /**
     * @var string
     */
    private $idTransacao;

    /**
     * @var \DateTime
     */
    private $dataCriacao;

    /**
     * @var \DateTime
     */
    private $dataExpiracao;

    /**
     * @var int
     */
    private $versaoRevisao;

    /**
     * @var int
     */
    private $idCobranca;

    /**
     * @var
     */
    private $urlPayloadQrCode;

    /**
     * @var string
     */
    private $status;

    /**
     * @var float
     */
    private $valorOriginal;

    /**
     * @var string
     */
    private $chaveCobrador;

    /**
     * @var string
     */
    private $mensagemDaCobranca;

    /**
     * @var array
     */
    private $payloadOriginal;

    /**
     * Cobranca constructor.
     * Responsável por montar o objeto cobrança inteiro. Em futuras versões podemos melhorar esse construtor.
     *
     * @param array $payloadCobranca
     * @throws \Exception
     */
    public function __construct(array $payloadCobranca)
    {
        $this->idTransacao        = $payloadCobranca['txid'];
        $this->dataCriacao        = new \DateTime($payloadCobranca['calendario']['criacao']);
        $this->dataExpiracao      = (clone $this->dataCriacao)
            ->add(new \DateInterval("PT{$payloadCobranca['calendario']['expiracao']}S"));
        $this->versaoRevisao      = $payloadCobranca['revisao'];
        $this->idCobranca         = $payloadCobranca['loc']['id'];
        $this->urlPayloadQrCode   = $payloadCobranca['location'];
        $this->status             = $payloadCobranca['status'];
        $this->valorOriginal      = $payloadCobranca['valor']['original'];
        $this->chaveCobrador      = $payloadCobranca['chave'];
        $this->mensagemDaCobranca = $payloadCobranca['solicitacaoPagador'];
        $this->payloadOriginal    = $payloadCobranca;
    }

    /**
     * @return string
     */
    public function getIdTransacao(): string
    {
        return $this->idTransacao;
    }

    /**
     * @return \DateTime
     */
    public function getDataCriacao(): \DateTime
    {
        return $this->dataCriacao;
    }

    /**
     * @return \DateTime
     */
    public function getDataExpiracao(): \DateTime
    {
        return $this->dataExpiracao;
    }

    /**
     * @return int
     */
    public function getVersaoRevisao(): int
    {
        return $this->versaoRevisao;
    }

    /**
     * @return int
     */
    public function getIdCobranca(): int
    {
        return $this->idCobranca;
    }

    /**
     * @return mixed
     */
    public function getUrlPayloadQrCode()
    {
        return $this->urlPayloadQrCode;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @return float
     */
    public function getValorOriginal(): float
    {
        return $this->valorOriginal;
    }

    /**
     * @return string
     */
    public function getChaveCobrador(): string
    {
        return $this->chaveCobrador;
    }

    /**
     * @return string
     */
    public function getMensagemDaCobranca(): string
    {
        return $this->mensagemDaCobranca;
    }

    /**
     * @return array
     */
    public function getPayloadOriginal(): array
    {
        return $this->payloadOriginal;
    }
}
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
}
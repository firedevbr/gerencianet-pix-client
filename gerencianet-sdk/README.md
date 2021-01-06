# SDK PHP PARA API PIX GERENCIANET

Este repositório foi desenvolvido pela Consultoria Técnica Gerencianet e tem o objetivo de facilitar a integração com os serviços oferecidos no contexto do arranjo Pix, como criação de cobrança, verificação de Pix recebidos e devolução, bem como o entendimento do qrcode, que deve ser construído seguindo a especificação do [BrCode](https://www.bcb.gov.br/content/estabilidadefinanceira/spb_docs/ManualBRCode.pdf).

## Requisitos
* PHP >= 5.4


## Testado com as versões
```
php 5.4, php 7.0 e php 7.3
```

## Como usar?
Para começar, você deve configurar os parâmetros no arquivo config.json.
Instancie as informações usando sua `chave PIX` vinculada à sua conta Gerencianet, `recebedor` nome do recebedor, `client_id`, `client_secret` da sua aplicação e `sandbox` igual a *true*, caso seu ambiente seja de Homologação, ou *false*, caso seja Produção.

```json
{
    "sandbox": true,
    "chave": "chavepix@email.com",
    "recebedor": "Nome do recebedor",
    "homologacao": {
        "nome_certificado": "developmentCertificate.pem",
        "client_id": "Client_Id_...",
        "client_secret": "Client_Secret_...",
        "pix_url_auth": "https://api-pix-h.gerencianet.com.br/oauth/token",
        "pix_url_cob": "https://api-pix-h.gerencianet.com.br/v2/cob",
        "pix_url": "https://api-pix-h.gerencianet.com.br/v2/pix"
    },
    "producao": {
        "nome_certificado": "productionCertificate.pem",
        "client_id": "Client_Id_...",
        "client_secret": "Client_Secret_...",
        "pix_url_auth": "https://api-pix.gerencianet.com.br/oauth/token",
        "pix_url_cob": "https://api-pix.gerencianet.com.br/v2/cob",
        "pix_url": "https://api-pix.gerencianet.com.br/v2/pix"
    }
}
```

Todas as requisições devem conter um certificado de segurança que será fornecido pela Gerencianet dentro da sua conta. 

Para gerar o seu certificado abra um ticket em https://gerencianet.com.br/fale-conosco informando o numero de sua conta e as credenciais client_id e client_secret de desenvolvimento. Nossa equipe irá retornar com o certificado `.p12` para você realizar o consumo dos endpoints.

Para a utilização no PHP o certificado deverá ser convertido em `.pem`. Segue exemplos utilizando o OpenSSL para a conversão.

**Comando 1**
```
// Gerar certificado e chave em único arquivo
openssl pkcs12 -in certificado.p12 -out certificado.pem -nodes
```

**Comando 2**
```
// Gerar certificado e chave separadas
openssl pkcs12 -in path.p12 -out newfile.crt.pem -clcerts -nokeys #certificado
openssl pkcs12 -in path.p12 -out newfile.key.pem -nocerts -nodes #chave privada
```

Após a conversão dos certificados, salvá-los no diretório `./certificado` com os mesmos nomes contidos no `config.json`.


## Exemplos de execução
Você pode executar usando qualquer servidor web, como Apache ou nginx, ou simplesmente iniciar um servidor php da seguinte forma:

```php
php -S localhost:9000
```

Em seguida, abra qualquer exemplo em seu navegador.

:warning: Alguns exemplos requerem que você altere alguns parâmetros para funcionar, como por exemplo, o arquivo `emitirPix.php` onde você deve inserir os parâmetros da cobrança.


## Documentação adicional

A documentação completa com todos os endpoints disponíveis está em https://dev.gerencianet.com.br/docs.


## Licença ##
Este projeto está licenciado sob a Licença MIT - consulte o arquivo [LICENSE.md](LICENSE) para obter detalhes

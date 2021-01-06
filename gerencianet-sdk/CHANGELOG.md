## [1.0.0](https://github.com/gerencianet/gn-pix-sdk-php-exemplo/releases/tag/1.0.0) - 2020-12-09

### Adicionado

- Parâmetro "recebedor" no "config.json" e no "emitirPix.php" utilizado para montar o EMV.
- Função para gerar o id da devolução, em "solicitaDevolucaoPix.php".
 
### Alterado
- Correção da função `getTxID()`.
- Condição no "montaBrCode.php", caso QR Code for "dinamico" o ID 62-05 (ReferenceLabel) receberá `***`.
- Adicionado parâmetro "recebedor" na configuração de exemplo do README.
 
### Removido

- Verificação do "payload" ao montar o  BR Code.

## [0.1.0](https://github.com/gerencianet/gn-pix-sdk-php-exemplo/releases/tag/0.1.0) - 2020-11-26

- Release inicial.

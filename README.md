# Instruções iniciais
- Configure permissão de escrita para a pasta **auth**
- Configure o seu clientId e clientSecret nos arquivos **config-prod.json** e **config-dev.json**
- Leia e aprenda com os exemplos (pasta **samples**)

# Certificado(s)
- Caso você não tenha o **.pem** de seu certificado, inclua o arquivo **cert-prod.p12** ou **cert-dev.p12** na pasta **auth** e utilize a função **generatePemFile()**
- Caso você já tenha o arquivo **.pem** de seu certificado, basta incluir o arquivo dentro da pasta **auth** com o nome **cert-prod.pem** ou **cert-dev.pem**

# Atenção: informações de segurança
- Não deixe a pasta **auth** exposta publicamente na internet, caso contrário, além de seu certificado, seus tokens de autenticação ficarão disponíveis na internet
- Opcionalmente você pode customizar a localização da pasta usando o primeiro parâmetro da construção da classe (**__construct($authDir)**)

# Requisitos
Versão PHP
- 5.4+

Extensões
- mbstring (obrigatória)
- openssl (opcional, para gerar arquivo .pem)

# Funções
- __construct($authDir = '', $dev = false, $debug = false)
  - $authDir - deixe em branco para utilizar pasta padrão
- generatePemFile($p12Password = '')
  - $p12Password - deixe em branco se não tiver senha
- enableUniqueQrCode()
  - Opção padrão
- disableUniqueQrCode()
  - Utilize para permitir múltiplos pagamentos no mesmo QRCode
- generatePix($data, $txid)
  - $data (conforme https://dev.gerencianet.com.br/docs#section-criar-cobran-a-)
  - $txid (opcional, deixe em branco para ser gerado automaticamente)
- updatePix($data, $txid)
  - $data (conforme https://dev.gerencianet.com.br/docs#section-revisar-cobran-a)
  - $txid (txid informado e/ou retornado na geração da cobrança)
- selectPix($txid)
  - $txid (txid informado e/ou retornado na geração da cobrança)
- selectPixHistory($data)
  - $data (conforme https://dev.gerencianet.com.br/docs#section-consultar-lista-de-cobran-as)
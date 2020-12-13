<?php
class GNPixApi {
    private $baseEndpoint;
    private $clientId;
    private $clientSecret;
    private $authDir;
    private $configPath;
    private $oAuthTokenPath;
    private $certificatePemPath;
    
    private $uniqueQrCode = true;
    private $dev = false;

    private $debug = false;
    private $requestHeaders = array();
    private $responseHeaders = array();
    private $lastError = null;
    private $lastStatusCode = null;

    function __construct($authDir = '', $dev = false, $debug = false) {
        if ($authDir != '') { $this->authDir = $authDir; } else { $this->authDir = dirname(__FILE__).'/auth'; }

        $this->dev = $dev;
        $this->debug = $debug;
        $this->baseEndpoint = $dev ? 'https://api-pix-h.gerencianet.com.br' : 'https://api-pix.gerencianet.com.br';
        $this->configPath = $dev ? $this->authDir."/config-dev.json" : $this->authDir."/config-prod.json";
        $this->oAuthTokenPath = $dev ? $this->authDir."/oauth-dev.json" : $this->authDir."/oauth-prod.json";
        $this->certificatePemPath = $dev ? $this->authDir."/cert-dev.pem" : $this->authDir."/cert-prod.pem";

        if (!extension_loaded('mbstring')) {
            throw new Exception("Error: mbstring extension not found", 1);                
        }

        if (!file_exists($this->configPath)) {
            throw new Exception("Config file does not exist (".$this->configPath.")", 1);
        }
        else {
            $data = json_decode(file_get_contents($this->configPath), true);
            $this->clientId = $data['clientId'];
            $this->clientSecret = $data['clientSecret'];
            $this->merchantName = $data['merchantName'];
            $this->merchantCity = $data['merchantCity'];            
        }

        if (!file_exists($this->oAuthTokenPath)) {
            if (file_put_contents($this->oAuthTokenPath, '""') === false) {
                throw new Exception("Error: Error writing file (".$this->oAuthTokenPath.")", 1);  
            }
        }
    }    

    private function setHeader($header) {
        $header_attr = reset(explode(": ", mb_strtolower($header,'UTF-8'), 2));

        $found = false;
        foreach ($this->requestHeaders as $key => $value) {
            if (stristr($value, ": ") !== FALSE) {
                $c_header_attr = reset(explode(": ", mb_strtolower($value, 'UTF-8'), 2));
                if ($header_attr == $c_header_attr) {
                    $found = true;
                    $this->requestHeaders[$key] = $header;
                }
            }
        }

        if (!$found) {
            $this->requestHeaders[] = $header;
        }
    }

    private function statusCode() {
        return $this->lastStatusCode;
    }

    private function responseHeaders() {
        return $this->responseHeaders;
    }

    private function responseMimeType() {
        return $this->responseHeaders['content-type'];
    }

    private function readResponseHeaders($response, $header_size) {
        $headers = substr($response, 0, $header_size);
        $headers = explode("\r\n", $headers); 
        $headers = array_filter($headers);

        if (is_array($headers)) {
            foreach ($headers as $key => $value) {
                if (stristr($value, ": ") !== FALSE) {
                    $ex = explode(": ", $value, 2);
                    $responseHeaders[mb_strtolower($ex[0], 'UTF-8')] = $ex[1];
                }
            }
        }

        return $responseHeaders;
    }

    private function getOAuthToken() {
        $data = json_decode(@file_get_contents($this->oAuthTokenPath), true);

        if (!isset($data['expires_at']) || time() >= $data['expires_at']) {
            $data = $this->requestOAuthToken();
        }

        return $data['token_type'].' '.$data['access_token'];
    }
    
    private function requestOAuthToken() {
        $this->setHeader("Content-Type: application/json");
        $this->setHeader("Authorization: Basic ".base64_encode($this->clientId.':'.$this->clientSecret));
        $data['grant_type'] = 'client_credentials';
        $request = $this->curlRequest("POST", "/oauth/token", json_encode($data));        
        if ($this->lastStatusCode == 200 && $request !== false) {
            // Saving data for future use
            $data = $request;
            $data['expires_at'] = time() + ($data['expires_in'] - 60);
            $this->saveOAuthToken($data);
            return $request;
        }
        else {
            throw new Exception("Error Requesting oAuthToken", 1);              
        }
    }

    private function saveOAuthToken($data) {
        file_put_contents($this->oAuthTokenPath, json_encode($data));
    }

    private function curlRequest($method, $path, $data = '') { 
        if (!file_exists($this->certificatePemPath)) {
            throw new Exception("PEM Certificate file does not exist (".$this->certificatePemPath.")", 1);
        }      

        $ch = curl_init();
        if (!empty($data)) {
            if ($method=="GET") {
                if (is_array($data)) {
                    $path .= '?'.http_build_query($data);
                }
            }
            else {                
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }        
        }        
        $fullUrl = $this->baseEndpoint.$path;
        curl_setopt($ch, CURLOPT_URL, $fullUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_SSLCERT, $this->certificatePemPath);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->requestHeaders);
        $response = curl_exec($ch);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);        
        $body = substr($response, $header_size);
        $error = curl_error($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->lastStatusCode = $httpcode;
        $this->responseHeaders = $this->readResponseHeaders($response, $header_size);

        if ($this->debug) {
            echo '[REQUEST] URL: '.$fullUrl.' <br />';
            echo '[REQUEST] Method: '.$method.' <br />';
            if ($method != "GET") {
                echo '[REQUEST] Data: <pre>'.$data.'</pre> <br />';
            }
            echo '[REQUEST] requestHeaders: <pre>'.print_r($this->requestHeaders, true).'</pre> <br />';
        }

        if ($error) {
            if ($this->debug) {
                echo '[RESPONSE] curl Error: '.$error.' <br />';
            }
            $this->lastError = $error;
            return false;
        }
        else {
            if ($this->debug) {
                echo '[RESPONSE] StatusCode: '.$this->lastStatusCode.' <br />';
                echo '[RESPONSE] responseHeaders: <pre>'.print_r($this->responseHeaders, true).'</pre> <br />';
                echo '[RESPONSE] response body: <pre>'.print_r($body, true).'</pre> <br />';
            }
            switch ($this->responseMimeType()) {
                case 'application/json':
                case 'application/json; charset=utf-8':
                    return json_decode($body, true);                
                break;                
                default:
                    throw new Exception("Response MimeType Unexpected", 1);                        
                break;
            }      
        }
    }

    private function generateErrorDescription($message, $request) {
        if (isset($request['error'])) {
            $message .= " - ".$request['error'];
        }
        if (isset($request['error_description'])) {
            $message .= " - ".$request['error_description'];
        }
        if (isset($request['nome'])) {
            $message .= " - ".$request['nome'];
        }
         if (isset($request['mensagem'])) {
            $message .= " - ".$request['mensagem'];
        }
        if (isset($request["erros"][0]["caminho"])) {
            $message .= " - ".$request["erros"][0]["caminho"];
        }
        if (isset($request["erros"][0]["mensagem"])) {
            $message .= " - ".$request["erros"][0]["mensagem"];
        }
        return $message;
    }

    private function charCodeAt($str, $i) { 
        return ord(substr($str, $i, 1)); 
    }

    private function calcCRC16($str) {
        $crc = 0xFFFF;
        $strlen = strlen($str);
        for ($c = 0; $c < $strlen; $c++) {
            $crc ^= $this->charCodeAt($str, $c) << 8;
            for ($i = 0; $i < 8; $i++) {
                if ($crc & 0x8000) {
                    $crc = ($crc << 1) ^ 0x1021;
                } else {
                    $crc = $crc << 1;
                }
            }
        }
        $crc16 = $crc & 0xFFFF;
        $crc16 = dechex($crc16);
        $crc16 = strtoupper($crc16);
        return $crc16;
    }

    private function generateTxId() {
        $size = 29;
        for (
            $txid = date('His'), $i = 0, $z = strlen($a = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz') - 1;
            $i != $size;
            $x = rand(0, $z), 
            $txid .= $a{$x}, 
            $i++
        );
        return $txid;
    }

    private function brCode($request) {
        $point_of_initiation_method = ($this->uniqueQrCode) ? '12' : '11';
        $gui = 'br.gov.bcb.pix';
        $url = $request['location'];
        $merchant_account_information = 
            '00'.str_pad(strlen($gui), 2, '0', STR_PAD_LEFT).$gui
            .'25'.str_pad(strlen($url), 2, '0', STR_PAD_LEFT).$url;
        $merchant_name = mb_substr($this->merchantName, 0, 25);
        $merchant_city = mb_substr($this->merchantCity, 0, 15);
        $additional_data_field_1_id = '05';
        $additional_data_field_1_val = '***';
        $additional_data_field = $additional_data_field_1_id.str_pad(strlen($additional_data_field_1_val), 2, '0', STR_PAD_LEFT).$additional_data_field_1_val;

        $stringQrCode = '000201' // Header: pix version
            . '01'.str_pad(strlen($point_of_initiation_method), 2, '0', STR_PAD_LEFT).$point_of_initiation_method
            . '26'.str_pad(strlen($merchant_account_information), 2, '0', STR_PAD_LEFT).$merchant_account_information
            . '52040000' // Category code: none
            . '5303986' // Currency: BRL
            . '5802BR' // Country: BR
            . '59'.str_pad(strlen($merchant_name), 2, '0', STR_PAD_LEFT).$merchant_name
            . '60'.str_pad(strlen($merchant_city), 2, '0', STR_PAD_LEFT).$merchant_city
            . '62'.str_pad(strlen($additional_data_field), 2, '0', STR_PAD_LEFT).$additional_data_field
            . '6304';
        $crc16 = $this->calcCRC16($stringQrCode);

        return $stringQrCode.$crc16;
    }

    private function pix($data, $txid = '', $method = 'PUT') {
        if ($txid == '') { $txid = $this->generateTxId(); }

        $this->setHeader("Authorization: ".$this->getOAuthToken());
        $this->setHeader("Content-Type: application/json");

        $request = $this->curlRequest($method, "/v2/cob/".$txid, json_encode($data));

        if ($action == 'PUT' && $this->lastStatusCode != "201") {
            error_log($this->generateErrorDescription('Error Generating Pix', $request));
            return $request;
        }

        if ($action == 'PATCH' && $this->lastStatusCode != "200") {
            error_log($this->generateErrorDescription('Error Updating Pix', $request));
            return $request;
        }

        if ($action == 'GET' && $this->lastStatusCode != "200") {
            error_log($this->generateErrorDescription('Error Updating Pix', $request));
            return $request;
        }

        $brCode = $this->brCode($request);

        return [
            "brCode" => $brCode,
            "qrCode" => "https://chart.googleapis.com/chart?cht=qr&chs=250&chl=".$brCode,
            "data" => $request
        ];
    }

    public function generatePemFile($p12Password = '') {
        if (!extension_loaded('openssl')) { 
            throw new Exception("Error: openssl extension not found", 1);                
        }

        $p12Path = $this->dev ? $this->authDir."/cert-dev.p12" : $this->authDir."/cert-prod.p12";

        if (!file_exists($p12Path)) {
            throw new Exception("Error: File certificate does not exist (".$p12Path.")", 1);              
        }
        else {    
            if (!file_exists($this->certificatePemPath)) {                    
                $results = array();

                if (openssl_pkcs12_read(file_get_contents($p12Path), $results, $p12Password) === false) {
                    throw new Exception("Error: Certificate wrong password or certificate corrupted", 1); 
                }

                if (file_put_contents($this->certificatePemPath, $results['cert'].PHP_EOL.$results['pkey']) === false) {
                    throw new Exception("Error: Error writing file (".$this->certificatePemPath.")", 1);  
                }
            }
        }
        return true;
    }

    public function enableUniqueQrCode() {
        $this->uniqueQrCode = true;
    }

    public function disableUniqueQrCode() {
        $this->uniqueQrCode = false;
    }    

    public function generatePix($data, $txid = '') {
        return $this->pix($data, $txid, 'PUT');
    }

    public function updatePix($data, $txid) {
        return $this->pix($data, $txid, 'PATCH');
    }

    public function selectPix($txid) {
        return $this->pix("", $txid, 'GET');
    }

    public function selectPixHistory($data) {
        $this->setHeader("Authorization: ".$this->getOAuthToken());
        $this->setHeader("Content-Type: application/json");
        
        $request = $this->curlRequest('GET', "/v2/cob", $data);

        if ($this->lastStatusCode != "200") {
            error_log($this->generateErrorDescription('Error Selecting Pix History', $request));
        }

        return $request;
    }

    public function selectPixPayment($e2eid) {
        $this->setHeader("Authorization: ".$this->getOAuthToken());
        $this->setHeader("Content-Type: application/json");

        $request = $this->curlRequest('GET', "/v2/pix/".$e2eid);
        if ($this->lastStatusCode != "200") {
            error_log($this->generateErrorDescription('Error Selecting Pix Payment', $request));
        }

        return $request;
    }

    public function refundPixByTxid($txid, $value = '') {
        $request = $this->selectPix($txid);

        if (isset($request['data']['pix'][1]['endToEndId'])) {
            throw new Exception("Cant refund pix by txId (more than one endToEndId)", 1);            
        }

        $e2eid = $request['data']['pix'][0]['endToEndId'];
        if ($value == '') {
            $value = $request['data']['pix'][0]['valor'];
        }

        return $this->refundPix($e2eid, '1', $value);
    }

    public function refundPix($e2eid, $refundId = '1', $value = '') {
        if ($value == '') {
            $request = $this->selectPixPayment($e2eid);
            $value = $request['valor'];
        }

        $this->setHeader("Authorization: ".$this->getOAuthToken());
        $this->setHeader("Content-Type: application/json");
        
        $request = $this->curlRequest('PUT', "/v2/pix/".$e2eid."/devolucao/".$refundId, json_encode(array("valor" => $value)));
        if ($this->lastStatusCode != "200") {
            error_log($this->generateErrorDescription('Error Refunding Pix', $request));
        }

        return $request;
    }
}
?>
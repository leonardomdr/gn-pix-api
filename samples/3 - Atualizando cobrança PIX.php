<?php
	// Classe
    include("../class.php");

    // Inicia classe
    $gnpix = new GNPixApi();

    // Atualizando cobrança PIX
    echo '<h1>Atualizando cobrança PIX</h1>';
    $pix = $gnpix->updatePix(
        [
            "calendario" => [
                "expiracao" => 129600
            ],
            "valor" => [
                "original" => "1.00"
            ],
            "chave" => "ffcf4449-50e3-484d-b325-b667825b510d" // Informe sua chave aqui
        ], 
        'xxxxxxxxxxxxxxxxx' // txId recebido na criação de cobrança anterior
    );

    echo '<h1>BR Code</h1>';
    echo '<input type="text" value="'.$pix['brCode'].'" size="60" />';

    echo '<h1>QR Code</h1>';
    echo '<img src="'.$pix['qrCode'].'" />';

    echo '<h1>Resposta</h1>';
    echo '<pre>'.print_r($pix, true).'</pre>';
?>
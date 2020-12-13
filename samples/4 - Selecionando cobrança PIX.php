<?php
	// Classe
    include("../class.php");

    // Inicia classe
    $gnpix = new GNPixApi();

    // Selecionando cobrança PIX
    echo '<h1>Selecionando cobrança PIX</h1>';
    $pix = $gnpix->selectPix('xxxxxxxxxxxxxxxxx'); // txId recebido na criação de cobrança anterior

    echo '<h1>BR Code</h1>';
    echo '<input type="text" value="'.$pix['brCode'].'" size="60" />';

    echo '<h1>QR Code</h1>';
    echo '<img src="'.$pix['qrCode'].'" />';

    echo '<h1>Resposta</h1>';
    echo '<pre>'.print_r($pix, true).'</pre>';
?>
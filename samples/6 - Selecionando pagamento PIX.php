<?php
	// Classe
    include("../class.php");

    // Inicia classe
    $gnpix = new GNPixApi();

    // Selecionando pagamento PIX
    echo '<h1>Selecionando pagamento PIX</h1>';
    $pix = $gnpix->selectPixPayment('e2eid'); // Preencha o endToEndId do pagamento

    echo '<h1>Resposta</h1>';
    echo '<pre>'.print_r($pix, true).'</pre>';
?>
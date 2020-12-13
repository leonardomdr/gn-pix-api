<?php
	// Classe
    include("../class.php");

    // Inicia classe
    $gnpix = new GNPixApi();

    // Reembolsando pagamento PIX (por txId)
    echo '<h1>Reembolsando pagamento PIX (por txId)</h1>';
    $pix = $gnpix->refundPixByTxid('txId'); // Preencha o txId da cobrança

    echo '<h1>Resposta</h1>';
    echo '<pre>'.print_r($pix, true).'</pre>';
?>
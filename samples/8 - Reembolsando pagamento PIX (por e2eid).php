<?php
	// Classe
    include("../class.php");

    // Inicia classe
    $gnpix = new GNPixApi();

    // Reembolsando pagamento PIX (por e2eid)
    echo '<h1>Reembolsando pagamento PIX (por e2eid)</h1>';
    $pix = $gnpix->refundPix('e2eid'); // Preencha o endToEndId do pagamento

    echo '<h1>Resposta</h1>';
    echo '<pre>'.print_r($pix, true).'</pre>';
?>
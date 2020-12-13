<?php
	// Classe
    include("../class.php");

    // Gerando certificado (produção)
    $gnpix = new GNPixApi();
    if ($gnpix->generatePemFile() === true) {
    	echo 'Certificado gerado com sucesso!';
    }

    // Gerando certificado (dev)
    $gnpix = new GNPixApi('', true);
    if ($gnpix->generatePemFile() === true) {
    	echo 'Certificado gerado com sucesso!';
    }
?>
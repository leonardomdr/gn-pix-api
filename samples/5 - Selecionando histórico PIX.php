<?php
	// Classe
    include("../class.php");

    // Inicia classe
    $gnpix = new GNPixApi();

    // Selecionando histórico PIX
    echo '<h1>Selecionando histórico PIX</h1>';
    $pix = $gnpix->selectPixHistory(
        [
            //"cpf" => "",
            //"cnpj" => "",
            //"status" => "",
            //"paginacao.paginaAtual" => "",
            "paginacao.itensPorPagina" => "1",
            "inicio" => "2019-11-20T00:00:00Z",
            "fim" => "2022-11-22T16:01:35Z"
        ]
    );

    echo '<h1>Resposta</h1>';
    echo '<pre>'.print_r($pix, true).'</pre>';
?>
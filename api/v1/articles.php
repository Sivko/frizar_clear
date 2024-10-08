<?php
//require($_SERVER["DOCUMENT_ROOT"] . "/api/v1/headers.php");
header('Content-Type: application/json');
$data = <<<DATA
{"pagination": ""}
DATA;


// echo $data;
// echo json_encode($data, JSON_UNESCAPED_UNICODE);
echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

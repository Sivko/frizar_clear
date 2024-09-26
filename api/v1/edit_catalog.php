<?php
require($_SERVER["DOCUMENT_ROOT"] . "/api/v1/headers.php");
\Bitrix\Main\Loader::includeModule('iblock');
header('Content-Type: application/json');

$sectionId = isset($_GET['section_id']) ? intval($_GET['section_id']) : null;
$arFields= isset($_GET['arFields']) ? intval($_GET['arFields']) : null;

$ob = new CIBlockSection;
$ob->Update($sectionId, $arFields);
echo json_encode(['status'=>200], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
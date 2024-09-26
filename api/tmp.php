<?php
use Bitrix\Iblock\PropertyTable;

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header("Access-Control-Allow-Headers: X-Requested-With");
header('Content-Type: application/json'); // Устанавливаем заголовок Content-Type для JSON
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

if (!\Bitrix\Main\Loader::includeModule('iblock')) {
    echo json_encode(['error' => 'Ошибка: модуль инфоблоков не подключен']);
    exit;
}

// Замените 17 на ID вашего инфоблока и 'BREND' на код вашего свойства
$iblockId = 17; 
$propertyCode = 'BREND';

// Получение данных о свойствах инфоблока
$property = PropertyTable::getList([
    'filter' => ['IBLOCK_ID' => $iblockId, 'CODE' => $propertyCode],
])->fetch();

if ($property && $property['PROPERTY_TYPE'] == 'L') {
    // Получение возможных значений для свойства типа "Список"
    $values = [];
    $enumList = \CIBlockPropertyEnum::GetList(
        ['SORT' => 'ASC'],
        ['PROPERTY_ID' => $property['ID']]
    );

    while ($enum = $enumList->Fetch()) {
        // Используем ID как ключ и VALUE как значение для объекта JSON
        $values[$enum['ID']] = $enum['VALUE'];
    }

    header('Content-Type: application/json');
    // Вывод значений в формате JSON-объекта
    echo json_encode($values, JSON_UNESCAPED_UNICODE);
} else {
    // Если свойство не найдено или не является списком, вернуть ошибку
    echo json_encode(['error' => 'Свойство не найдено или не является списком.']);
}
?>

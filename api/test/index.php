<?php
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");


$filter = array();
// $arParams["SELECT"] = array("UF_JWT_TOKEN");
// $arParams['nPageSize'] = 4;
$params = ['nPageSize' => 4, 'iNumPage' => 1, "SELECT" => ["UF_JWT_TOKEN"]];
// $rsUsers = CUser::GetList(($by="ID"), ($order="desc"), $filter, $params); // выбираем пользователей
$rsUsers = CUser::GetList(
  ['ID' => 'DESC'],
  [],
  false,
  ["NAV_PARAMS" => ['nTopCount' => 1, 'nOffset' => 1], "SELECT" => ["UF_JWT_TOKEN"], "FIELDS" => ["ID"]],
  ['ID']
); // выбираем пользователей


// $res = Array("res"=>$rsUsers->fetch());
// $res = [];
$row = $rsUsers->fetch();
// while ($row = $rsUsers->fetch()) {
//   $res[]=$row;
// }

// while ($data = $rsUsers->GetNext()) {
//   $res[] = $data;
// }
// $is_filtered = $rsUsers->is_filtered; // отфильтрована ли выборка ?
// $rsUsers->NavStart(50); // разбиваем постранично по 50 записей
// echo $rsUsers->NavPrint(GetMessage("PAGES")); // печатаем постраничную навигацию
// while($rsUsers->NavNext(true, "f_")) :
// 	echo "[".$f_ID."] (".$f_LOGIN.") ".$f_NAME." ".$f_LAST_NAME."<br>";	
// endwhile;  

// echo "<pre>";
// var_dump($res);
header('Content-Type: application/json');

echo json_encode(["test"=>"ok"], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

// echo "</pre>";
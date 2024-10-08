<?php
$res = \Bitrix\Sale\Location\TypeTable::add(array(
	'CODE' => 'CITY',
	'SORT' => '100', // уровень вложенности
	'DISPLAY_SORT' => '200', // приоритет показа при поиске
	'NAME' => array( // языковые названия
		'ru' => array(
			'NAME' => 'Город'
		),
		'en' => array(
			'NAME' => 'City'
		),
	)
));
if($res->isSuccess())
{
	print('Type added with ID = '.$res->getId());
}
<?php
/**
 * author Sergey Khrystenko
 * файл для отправки статистики по крону
 */
include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

CModule::IncludeModule("itcube.dynamica");
$CItcubeDynamyca = new CItcubeDynamyca();
$CItcubeDynamyca->sendStatistics();
exit();
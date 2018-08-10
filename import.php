<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

use lib\Autonix;
use Bitrix\Main\Loader;

Loader::includeModule('iblock');

$APPLICATION->SetTitle('Импорт остатков');

$aTabs = [
    [
        'DIV' => 'edit1',
        'TAB' => 'Импорт',
    ]
];

$tabControl = new CAdminTabControl("tabControl", $aTabs);

$uploadDir = $_SERVER['DOCUMENT_ROOT'].'/import/autonux/';
$uploadFile = $uploadDir.basename($_FILES['file']['name']);

$sectRes = CIBlockSection::GetList(
    [],
    [
        'IBLOCK_ID' => 6,
        'ACTIVE' => 'Y',
        'DEPTH_LEVEL' => 1
    ],
    false,
    [
        'ID',
        'NAME',
    ]
);
while ($arSectRes = $sectRes->Fetch()) {
    $arSections[] = $arSectRes;
}

if (!empty($_REQUEST['save'])) {
    if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadFile)) {
        $autonix = new Autonix($uploadFile, $_REQUEST['section']);
        $autonix->run();
        $result['message']['text'] = 'Файл корректен и был успешно загружен';
        $result['message']['type'] = 'OK';

    } else {
        $result['message']['text'] = 'Файл не загружен';
        $result['message']['type'] = 'ERROR';
    }
}

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

if (!empty($result['message'])) {
    CAdminMessage::ShowMessage(
        [
            'MESSAGE' => $result['message']['text'],
            'TYPE'    => $result['message']['type'],
        ]
    );
}


$tabControl->Begin();

echo '<form action="" method="post" enctype="multipart/form-data">';

$tabControl->BeginNextTab();


?>
    <tr>
        <td width="10%">Выбрать раздел</td>
        <td width="90%">
            <select name="section" id="">
                <option value="">(не установлено)</option>
                <?foreach ($arSections as $arSection):?>
                    <option value="<?=$arSection['ID']?>"><?=$arSection['NAME']?></option>
                <?endforeach?>
            </select>
        </td>
    </tr>
    <tr>
        <td width="10%">Файл csv</td>
        <td width="90%">
            <input type="file" name="file">
        </td>
    </tr>
<?
$tabControl->Buttons();

echo '<input class="adm-btn-save" type="submit" name="save" value="Загрузить" title="Загрузить" />';

$tabControl->End();

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
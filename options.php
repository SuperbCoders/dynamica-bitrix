<?
/**
 * author Sergey Khrystenko
 * файл с настройками модул€ дл€ админки
 * он автоматом подт€гиваетс€ битриксом и его показывает
 */

if(!$USER->IsAdmin())
    return;

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/options.php");
IncludeModuleLangFile(__FILE__);

$module_id = "itcube.dynamica";

$arAllOptions = array(
    array("activate_module", GetMessage("itcube.dynamica_ACTIVATE"), "Y", array("checkbox", "Y")),
    array("api_token", GetMessage("itcube.dynamica_API_TOKEN"), "", array("text", "50")),
    array("project_id", GetMessage("itcube.dynamica_PROJECT_ID"), "", array("text", "50")),
);
$aTabs = array(
    array("DIV" => "edit1", "TAB" => GetMessage("MAIN_TAB_SET"), "ICON" => "ib_settings", "TITLE" => GetMessage("MAIN_TAB_TITLE_SET")),
);
$tabControl = new CAdminTabControl("tabControl", $aTabs);

if( $REQUEST_METHOD=="POST" && strlen($Update.$Apply)>0 && check_bitrix_sessid() )
{


    foreach($arAllOptions as $arOption)
    {
        $name=$arOption[0];
        $val=$_REQUEST[$name];
        if($arOption[2][0]=="checkbox" && $val!="Y") {
            $val = "N";
        }
        COption::SetOptionString($module_id, $name, $val, $arOption[1]);
    }

    COption::SetOptionString($module_id, "period_count", $_REQUEST["period_count"]);
    COption::SetOptionString($module_id, "period_type", $_REQUEST["period_type"]);

    CModule::IncludeModule("itcube.dynamica");
    $CItcubeDynamyca = new CItcubeDynamyca();

    if( !file_exists($_SERVER["DOCUMENT_ROOT"].'/upload/dynamica_firstexec.php') ){
        $CItcubeDynamyca->firstSave();
    }

    $CItcubeDynamyca->sendStatistics();
    $msg = array("TYPE"=>"OK", "MESSAGE"=>GetMessage("itcube.dynamica_RUN_MES"));

    /*if(strlen($Update)>0 && strlen($_REQUEST["back_url_settings"])>0) {
        LocalRedirect($_REQUEST["back_url_settings"]);
    }else {
        LocalRedirect($APPLICATION->GetCurPage() . "?mid=" . urlencode($mid) . "&lang=" . urlencode(LANGUAGE_ID) . "&back_url_settings=" . urlencode($_REQUEST["back_url_settings"]) . "&" . $tabControl->ActiveTabParam());
    }*/
}elseif( $REQUEST_METHOD=="POST" && strlen($Run)>0 && check_bitrix_sessid() ){
    CModule::IncludeModule("itcube.dynamica");
    $CItcubeDynamyca = new CItcubeDynamyca();
    $CItcubeDynamyca->sendStatistics();
    $msg = array("TYPE"=>"OK", "MESSAGE"=>GetMessage("itcube.dynamica_RUN_MES"));
}

if( isset($msg) ){
    CAdminMessage::ShowMessage($msg);
}
?>

<?
$tabControl->Begin();
?>
<form method="post" action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=urlencode($mid)?>&amp;lang=<?echo LANGUAGE_ID?>">
    <?$tabControl->BeginNextTab();?>
    <h4><?=GetMessage("itcube.dynamica_DESC")?></h4>
    <?
    foreach($arAllOptions as $arOption):
        $val = COption::GetOptionString($module_id, $arOption[0], $arOption[2]);
        $type = $arOption[3];
        ?>
        <tr>
            <td width="40%" nowrap <?if($type[0]=="textarea") echo 'class="adm-detail-valign-top"'?>>
                <label for="<?echo htmlspecialcharsbx($arOption[0])?>"><?echo $arOption[1]?>:</label>
            <td width="60%">
                <?if($type[0]=="checkbox"):?>
                    <input type="checkbox" id="<?echo htmlspecialcharsbx($arOption[0])?>" name="<?echo htmlspecialcharsbx($arOption[0])?>" value="Y"<?if($val=="Y")echo" checked";?>>
                <?elseif($type[0]=="text"):?>
                    <input type="text" size="<?echo $type[1]?>" maxlength="255" value="<?echo htmlspecialcharsbx($val)?>" name="<?echo htmlspecialcharsbx($arOption[0])?>">
                <?elseif($type[0]=="textarea"):?>
                    <textarea rows="<?echo $type[1]?>" cols="<?echo $type[2]?>" name="<?echo htmlspecialcharsbx($arOption[0])?>"><?echo htmlspecialcharsbx($val)?></textarea>
                <?endif?>
            </td>
        </tr>
    <?endforeach?>
    <tr>
        <td>
            <label for="period_type"><?echo GetMessage("itcube.dynamica_PERIOD");?>:</label>
        </td>
        <td>
            <input type="text" size="37" maxlength="255" value="<?echo COption::GetOptionString($module_id, "period_count", "10");?>" name="period_count">
            <select name="period_type" id="period_type">
                <option value="hour"<?if(COption::GetOptionString($module_id, "period_type", "day")=="hour")echo " selected"?>><?= GetMessage('itcube.dynamica_PERIOD_HOUR')?></option>
                <option value="day"<?if(COption::GetOptionString($module_id, "period_type", "day")=="day")echo " selected"?>><?= GetMessage('itcube.dynamica_PERIOD_DAY')?></option>
                <option value="week"<?if(COption::GetOptionString($module_id, "period_type", "day")=="week")echo " selected"?>><?= GetMessage('itcube.dynamica_PERIOD_WEEK')?></option>
                <option value="month"<?if(COption::GetOptionString($module_id, "period_type", "day")=="month")echo " selected"?>><?= GetMessage('itcube.dynamica_PERIOD_MONTH')?></option>
                <option value="quater"<?if(COption::GetOptionString($module_id, "period_type", "day")=="quater")echo " selected"?>><?= GetMessage('itcube.dynamica_PERIOD_QUATER')?></option>
                <option value="year"<?if(COption::GetOptionString($module_id, "period_type", "day")=="year")echo " selected"?>><?= GetMessage('itcube.dynamica_PERIOD_YEAR')?></option>
            </select>
        </td>
    </tr>
    <?$tabControl->Buttons();?>
    <input type="submit" name="Update" value="<?=GetMessage("MAIN_SAVE")?>" title="<?=GetMessage("MAIN_OPT_SAVE_TITLE")?>" class="adm-btn-save">
    <input type="submit" name="Apply" value="<?=GetMessage("MAIN_OPT_APPLY")?>" title="<?=GetMessage("MAIN_OPT_APPLY_TITLE")?>">
    <?if( COption::GetOptionString($module_id, "activate_module") == "Y" && COption::GetOptionString($module_id, "api_token") != "" && COption::GetOptionString($module_id, "project_id") != "" ):?>
        <input type="submit" name="Run" value="<?=GetMessage("itcube.dynamica_RUN_TITLE")?>" title="<?=GetMessage("itcube.dynamica_RUN_TITLE")?>" class="adm-btn-save">
    <?endif;?>
    <?if(strlen($_REQUEST["back_url_settings"])>0):?>
        <input type="button" name="Cancel" value="<?=GetMessage("MAIN_OPT_CANCEL")?>" title="<?=GetMessage("MAIN_OPT_CANCEL_TITLE")?>" onclick="window.location='<?echo htmlspecialcharsbx(CUtil::addslashes($_REQUEST["back_url_settings"]))?>'">
        <input type="hidden" name="back_url_settings" value="<?=htmlspecialcharsbx($_REQUEST["back_url_settings"])?>">
    <?endif?>
    <?=bitrix_sessid_post();?>
    <?$tabControl->End();?>
</form>
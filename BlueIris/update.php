<?
    if($_IPS['SENDER'] == "TimerEvent") {
        $moduleID = IPS_GetParent($_IPS['SELF']);

        BLUEIRIS_Update($moduleID);
    }
?>
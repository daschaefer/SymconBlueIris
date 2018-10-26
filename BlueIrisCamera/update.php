<?
    if($_IPS['SENDER'] == "TimerEvent") {
        $moduleID = IPS_GetParent($_IPS['SELF']);

        BLUEIRISCAMERA_Update($moduleID);
    }
?>
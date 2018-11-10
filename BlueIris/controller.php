<?
    $Sender = IPS_GetObject($_IPS['VARIABLE']);
    
    $exp = explode("_", $Sender['ObjectIdent']);

    switch ($exp[0]) {
        case 'isEnabled':
            if((bool)$_IPS['VALUE'] == false)
                BLUEIRIS_DisableCamera(IPS_GetParent($_IPS['SELF']), $exp[1]);
            else
                BLUEIRIS_EnableCamera(IPS_GetParent($_IPS['SELF']), $exp[1]);
            break;
        case 'motion':
                if((bool)$_IPS['VALUE'] == false)
                BLUEIRIS_DisableMotionDetection(IPS_GetParent($_IPS['SELF']), $exp[1]);
            else
                BLUEIRIS_EnableMotionDetection(IPS_GetParent($_IPS['SELF']), $exp[1]);
            break;
        default:
            break;
    }
?>
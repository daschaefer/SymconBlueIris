<?

class BlueIrisCamera extends IPSModule
{       
    public function Create()
    {
        parent::Create();
        
        $this->RegisterPropertyString("CamID", "");
        $this->RegisterPropertyString("LoadCameraVariables", "minimal");
    }
    
    public function ApplyChanges()
    {
        parent::ApplyChanges();
        
        $this->ConnectParent("{695BAF6A-A6CB-4989-9659-1C55B81FD458}");
    }

    public function RequestAction($Ident, $Value) 
    { 
        switch ($Ident) 
        { 
            case "controlptz":
                $this->PTZ($Value);
                break;
            case "isEnabled":
                if($Value == true)
                    $this->Enable();
                else
                    $this->Disable();
                break;
            case "isPaused":
                $this->Pause($Value);
                break;
            case "isRecording":
                $this->Record($Value);
                break;
            case "isMotion":
                $this->MotionDetection($Value);
                break;
            default:
                break;
        } 
    }

    // PUBLIC ACCESSIBLE FUNCTIONS
    public function PTZ($value) {
        switch ($value) {
            case 0: // left
                $value = 0;
                break;
            case 1: // up
                $value = 2;
                break;
            case 2: // center
                $value = 4;
                break;
            case 3: // down
                $value = 3;
                break;
            case 4: // right
                $value = 1;
                break;
            case 5: // zoom in
                $value = 5;
                break;
            case 6: // zoom out
                $value = 6;
                break;
            default:
                break;
        }

        $param = array();
        $param['cmd'] = "ptz";
        $param['button'] = $value;
        $param['updown'] = 0;

        $this->QueryParent($param);
    }

    public function Enable() {
        $param = array();
        $param['cmd'] = 'camconfig';
        $param['enable'] = true;

        $this->QueryParent($param);
    }

    public function Disable() {
        $param = array();
        $param['cmd'] = 'camconfig';
        $param['enable'] = false;

        $this->QueryParent($param);
    }

    public function Pause($pause) {
        if(is_bool($pause) && $pause == true)
            $pause = -1;
        elseif(is_bool($pause) && $pause == false)
            $pause = 0;
        elseif(is_numeric($pause))
            $pause = $pause;
        else
            $pause = 0;

        $param = array();
        $param['cmd'] = 'camconfig';
        $param['pause'] = $pause;

        $this->QueryParent($param);
    }

    public function MotionDetection($state) {
        $var = IPS_GetObjectIDByIdent("isMotion", $this->InstanceID);
        SetValue($var, $state);

        $param = array();
        $param['cmd'] = 'camconfig';
        $param['motion'] = $state;

        $this->QueryParent($param);
    }

    public function Reset() {
        $param = array();
        $param['cmd'] = 'camconfig';
        $param['reset'] = true;

        $this->QueryParent($param);
    } 

    public function ReceiveData($JSONString) {
        $data = json_decode($JSONString, true);

        if($data['DataID'] == "{ED01C3C3-22CF-4F37-9FF4-9D366973853D}") {
            $data = $data['Buffer'];
            foreach($data as $cam) { // only update corresponding camera
                if($cam['optionValue'] == IPS_GetProperty($this->InstanceID, "CamID")) {
                    if(isset($cam['cmd'])) {
                        switch ($cam['cmd']) {
                            case 'UpdateCheck':
                                $this->UpdateCheck($cam['interval']);
                                break;
                            case '_getMedia':
                                $this->UpdateMedia($cam);
                                break;
                            default:
                                $this->UpdateVariables($cam);
                                break;
                        }
                    }
                }
            }
        }
    }

    public function Update() {
        $this->QueryParent(array('cmd' => 'camlist'));
        $this->QueryParent(array('cmd' => 'camconfig'));
        $this->QueryParent(array('cmd' => '_getMedia'));
    }

    // PRIVATE FUNCTIONS
    private function UpdateVariables($in) {
        /*
        camlist Array
        (
            [optionDisplay] => Haustuer
            [optionValue] => Cam1
            [active] => 1
            [FPS] => 6.28
            [color] => 8151097
            [ptz] => 
            [audio] => 
            [width] => 1920
            [height] => 1080
            [newalerts] => 0
            [lastalert] => -1
            [alertutc] => 1527526323
            [webcast] => 1
            [isEnabled] => 1
            [isOnline] => 1
            [hidden] => 
            [tempfull] => 
            [type] => 4
            [profile] => -1
            [pause] => 0
            [isPaused] => 
            [isRecording] => 
            [isManRec] => 
            [ManRecElapsed] => 0
            [ManRecLimit] => 0
            [isYellow] => 
            [isMotion] => 
            [isTriggered] => 
            [isNoSignal] => 
            [isAlerting] => 
            [nAlerts] => 0
            [nTriggers] => 0
            [nClips] => 0
            [nNoSignal] => 13
            [error] => 
        )

        camconfig Array
        (
            [pause] => 0
            [push] => 
            [audio] => 
            [motion] => 
            [schedule] => 
            [ptzcycle] => 
            [ptzevents] => 
            [alerts] => 0
            [output] => 
            [setmotion] => Array
                (
                    [audio_trigger] => 
                    [audio_sense] => 10000
                    [usemask] => 1
                    [sense] => 6500
                    [contrast] => 40
                    [showmotion] => 0
                    [shadows] => 1
                    [luminance] => 
                    [objects] => 1
                    [maketime] => 10
                    [breaktime] => 100
                )

            [record] => 2
            [cmd] => camconfig
            [optionValue] => Cam2
        )*/

        $cam = null;

        if($in['cmd'] == "camlist") {
            foreach($in as $key => $value) {
                if(!is_array($value))
                    continue;

                if($value['optionValue'] == $this->GetCamID()) {
                    $cam = $value;
                    break;
                }
            }
        }
        else if($in['cmd'] == "camconfig") {
            $cam = $in;
        }

        $variablesNeverCreate =     array(  "cmd",
                                    );

        $variablesCreateInMinimal = array(  "optionDisplay"
                                            ,"optionValue"
                                            ,"alertutc" 
                                            ,"isEnabled"
                                            ,"isMotion"
                                            ,"isOnline"
                                            ,"isPaused"
                                            ,"isRecording"
                                            ,"isTriggered"
                                            ,"lastalert"
                                    );

        $variablesCreate = array();
        if(IPS_GetProperty($this->InstanceID, "LoadCameraVariables") == "minimal") {
            $variablesCreate = $variablesCreateInMinimal;
        }

        $userControlledProperties = array(
                                            'isEnabled' => 'BLUEIRIS.Switch',
                                            'isMotion' => 'BLUEIRIS.Switch',
                                            'isPaused' => 'BLUEIRIS.Switch'
                                    );

        foreach($cam as $key => $value) {
            if(is_array($value))
                continue;

            if(in_array($key, $variablesNeverCreate))
                continue;

            if(count($variablesCreate) > 0 && !in_array($key, $variablesCreate)) {
                // delete possible existing variable
                $var = @IPS_GetObjectIDByIdent($key, $this->InstanceID);
                if($var) {
                    IPS_DeleteVariable($var);
                }
                continue;
            }

            $type = 3;
            switch (gettype($value)) {
                case 'boolean':
                    $type = 0;
                    break;
                case 'integer':
                    $type = 1;
                    break;
                case 'double':
                    $type = 2;
                    break;
                default:
                    $type = 3;
                    break;
            }


            $var = @IPS_GetObjectIDByIdent($key, $this->InstanceID);
            if(!$var) {
                $var = IPS_CreateVariable($type);
                IPS_SetName($var, $key);
                IPS_SetIdent($var, $key);

                IPS_SetParent($var, $this->InstanceID);
            }

            if(array_key_exists($key, $userControlledProperties) !== false) {
                IPS_SetVariableCustomProfile($var, $userControlledProperties[$key]);
                $this->EnableAction($key);
            } else {
                @$this->DisableAction($key);
            }

            if(GetValue($var) != $value) {
                if($key == "isMotion")
                    continue;

                SetValue($var, $value);
            }

            if($key == "motion") {
                $var = IPS_GetObjectIDByIdent("isMotion", $this->InstanceID);
                SetValue($var, $value);
            }

            if($key == "isEnabled")
                $this->HideControls($value);
        }
    }

    private function UpdateMedia($param) {
        //media elements
        $media = @IPS_GetMediaIDByName("Kamera Stream", $this->InstanceID);
        if(!$media) {
            $media = IPS_CreateMedia(3);
            IPS_SetName($media, "Kamera Stream");
            IPS_SetMediaFile($media, $param['mediaURL'], true);
            IPS_SetParent($media, $this->InstanceID);
        }
        else {
            if(md5(IPS_GetMedia($media)['MediaFile']) != md5($param['mediaURL'])) {
                IPS_SetMediaFile($media, $param['mediaURL'], true);
            }
        }

        // $media = @IPS_GetMediaIDByName("Kamera Standbild", $this->InstanceID);
        // if(!$media) {
        //     $media = IPS_CreateMedia(3);
        //     IPS_SetName($media, "Kamera Standbild");
        //     IPS_SetMediaFile($media, $param['pictureURL'], true);
        //     IPS_SetParent($media, $this->InstanceID);
        // }
        // else {
        //     if(md5(IPS_GetMedia($media)['MediaFile']) != md5($param['pictureURL'])) {
        //         IPS_SetMediaFile($media, $param['pictureURL'], true);
        //     }
        // }

        // $pictureHTML = @IPS_GetObjectIDByIdent("pictureHTML", $this->InstanceID);
        // if(!$pictureHTML) {
        //     $pictureHTML = IPS_CreateVariable(3);
        //     IPS_SetIdent($pictureHTML, "pictureHTML");
        //     IPS_SetName($pictureHTML, "pictureHTML");  
        //     IPS_SetParent($pictureHTML, $this->InstanceID);
        //     IPS_SetVariableCustomProfile($pictureHTML, "~HTMLBox");   
        // }
        // SetValue($pictureHTML, "<img src='".$param['pictureURL']."'>");
    }


    private function UpdateCheck($interval) {
        // Create Update script
        $updateScriptID = @$this->GetIDForIdent("update");
        if($updateScriptID === false) {
            $updateScriptID = $this->RegisterScript("update", "update", file_get_contents(__DIR__ . "/update.php"), 100);
        } else {
            IPS_SetScriptContent($updateScriptID, file_get_contents(__DIR__ . "/update.php"));
        }
        IPS_SetHidden($updateScriptID, true);

        $updateCheck = @IPS_GetEventIDByName("updatecheck", $updateScriptID);
        if(!$updateCheck) {
            $updateCheck = IPS_CreateEvent(1);
            IPS_SetParent($updateCheck, $updateScriptID);
            IPS_SetName($updateCheck, "updatecheck");
        }
        IPS_SetEventCyclic($updateCheck, 0, 0, 0, 2, 1, $interval);
        if($interval > 0)
            IPS_SetEventActive($updateCheck, true);
        else
            IPS_SetEventActive($updateCheck, false);
    }

    private function HideControls($boolean) {
        foreach(IPS_GetChildrenIDs($this->InstanceID) as $childID) {
            $Child = IPS_GetObject($childID);

            $hide = false;
            if($boolean == false)
                $hide = true;

            if($Child['ObjectIdent'] != 'isEnabled') {
                if($Child['ObjectIsHidden'] != $hide)
                    IPS_SetHidden($childID, $hide);
            }
        }
    }

    private function GetCamID() {
        return IPS_GetProperty($this->InstanceID, "CamID");
    }

    private function QueryParent(array $param) {
        if(!array_key_exists('camera', $param))
            $param['camera'] = $this->GetCamID();

        $message = array();
        $message['DataID'] = "{0AD5DC4B-6CE8-4979-8064-33B7895D6ACA}";
        $message['Buffer'] = $param;

        $this->SendDataToParent(json_encode($message));
    }

    
    // HELPER FUNCTIONS
    protected function GetParent()
    {
        $instance = IPS_GetInstance($this->InstanceID);
        return ($instance['ConnectionID'] > 0) ? $instance['ConnectionID'] : false;
    }

    protected function ModuleLogMessage($message) {
        IPS_LogMessage(IPS_GetObject($this->InstanceID)['ObjectName'], $message);
    }
}

?>
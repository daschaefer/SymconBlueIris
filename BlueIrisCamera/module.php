<?

class BlueIrisCamera extends IPSModule
{       
    public function Create()
    {
        parent::Create();
        
        $this->RegisterPropertyString("CamID", "");
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
            case "isMotionDetecting":
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
        $var = IPS_GetObjectIDByIdent("isMotionDetecting", $this->InstanceID);
        SetValue($var, $state);

        $param = array();
        $param['cmd'] = 'camconfig';
        $param['motion'] = $state;

        $this->QueryParent($param);
    }

    public function Record($state) {
        $param = array();
        $param['cmd'] = 'camconfig';
        $param['record'] = $state;

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
            // IPS_LogMessage("BlueIrisCamera", "JSON: ".$JSONString);
            $data = $data['Buffer'];
            foreach($data as $cam) {
                if($cam['optionValue'] == IPS_GetProperty($this->InstanceID, "CamID")) {
                    $this->Update($cam);
                }
            }
        }
    }

    // PRIVATE FUNCTIONS
    private function GetCamID() {
        return IPS_GetProperty($this->InstanceID, "CamID");
    }

    private function Update($cam) {
        $userControlledProperties =     array(
                                                'isEnabled' => 'BLUEIRIS.Switch',
                                                'isPaused' => 'BLUEIRIS.Switch',
                                                'isRecording' => 'BLUEIRIS.Record'
                                        );

        $skipAutoCreation = array("mediaURL", "pictureURL");

        //camera properties as variables
        foreach($cam as $key => $value) {
            if(in_array($key, $skipAutoCreation))
                continue;

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

            if(GetValue($var) != $value) 
                SetValue($var, $value);
        }

        //additional control elements
        if($cam["ptz"] == true) {
            $ident = "controlptz";
            $var = @IPS_GetObjectIDByIdent($ident, $this->InstanceID);
            if(!$var) {
                $var = IPS_CreateVariable(1);
                IPS_SetIdent($var, $ident);
                IPS_SetName($var, "PTZ Steuerung");
                IPS_SetVariableCustomProfile($var, "BLUEIRIS.PTZ");   
                IPS_SetParent($var, $this->InstanceID);
                SetValue($var, 2);
            }
            $this->EnableAction($ident);
        }
        else {
            $ident = "ptz_".$cam["optionValue"];
            $var = @IPS_GetObjectIDByIdent($ident, $this->InstanceID);
            if($var) {
                $this->DisableAction($ident);
                IPS_DeleteVariable($var);
            }
        }

        $var = @IPS_GetObjectIDByIdent("isMotionDetecting", $this->InstanceID);
        if(!$var) {
            $var = IPS_CreateVariable(0);
            IPS_SetIdent($var, "isMotionDetecting");
            IPS_SetName($var, "isMotionDetecting");
            IPS_SetVariableCustomProfile($var, "BLUEIRIS.Switch");   
            IPS_SetParent($var, $this->InstanceID);
            SetValue($var, false);
            $this->MotionDetection(false);
        }
        $this->EnableAction("isMotionDetecting");

        //media elements
        $media = @IPS_GetMediaIDByName("Kamera Stream", $this->InstanceID);
        if(!$media) {
            $media = IPS_CreateMedia(3);
            IPS_SetName($media, "Kamera Stream");
            IPS_SetMediaFile($media, $cam['mediaURL'], true);
            IPS_SetParent($media, $this->InstanceID);
        }
        else {
            if(md5(IPS_GetMedia($media)['MediaFile']) != md5($cam['mediaURL'])) {
                IPS_SetMediaFile($media, $cam['mediaURL'], true);
            }
        }

        $media = @IPS_GetMediaIDByName("Kamera Standbild", $this->InstanceID);
        if(!$media) {
            $media = IPS_CreateMedia(3);
            IPS_SetName($media, "Kamera Standbild");
            IPS_SetMediaFile($media, $cam['pictureURL'], true);
            IPS_SetParent($media, $this->InstanceID);
        }
        else {
            if(md5(IPS_GetMedia($media)['MediaFile']) != md5($cam['pictureURL'])) {
                IPS_SetMediaFile($media, $cam['pictureURL'], true);
            }
        }

        $pictureHTML = @IPS_GetObjectIDByIdent("pictureHTML", $this->InstanceID);
        if(!$pictureHTML) {
            $pictureHTML = IPS_CreateVariable(3);
            IPS_SetIdent($pictureHTML, "pictureHTML");
            IPS_SetName($pictureHTML, "Kamera Standbild");  
            IPS_SetParent($pictureHTML, $this->InstanceID);
            IPS_SetVariableCustomProfile($pictureHTML, "~HTMLBox");   
        }
        SetValue($pictureHTML, "<img src='".$cam['pictureURL']."'>");

        // hide/unhide variables upon enabled state
        foreach(IPS_GetChildrenIDs($this->InstanceID) as $childID) {
            $Child = IPS_GetObject($childID);

            if($Child['ObjectIdent'] != 'isEnabled') {
                if($cam['isEnabled'] == false)
                    IPS_SetHidden($childID, true);
                else
                    IPS_SetHidden($childID, false);
            }
        }
    }

    private function QueryParent(array $param) {
        if(GetValue(IPS_GetObjectIDByIdent("isEnabled", $this->InstanceID)) == true || ($param['cmd'] == "camconfig" && isset($param['enable']))) {
            if(!array_key_exists('camera', $param))
                $param['camera'] = $this->GetCamID();

            $message = array();
            $message['DataID'] = "{0AD5DC4B-6CE8-4979-8064-33B7895D6ACA}";
            $message['Buffer'] = $param;

            // IPS_LogMessage("BlueIrisCamera", print_r($message, true));

            $this->SendDataToParent(json_encode($message));
        }
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

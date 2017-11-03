<?

class BlueIris extends IPSModule
{       
    public function Create()
    {
        parent::Create();
        
        // Public properties
        $this->RegisterPropertyString("IPAddress", "");
        $this->RegisterPropertyInteger("Port", 81);
        $this->RegisterPropertyInteger("Timeout", 3);
        $this->RegisterPropertyInteger("Interval", 5);
        $this->RegisterPropertyString("Username", "admin");
        $this->RegisterPropertyString("Password", "");
        $this->RegisterPropertyString("TriggerVariable", "isAlarming");
    }
    
    public function ApplyChanges()
    {
        $intervalLimit = 3;

        parent::ApplyChanges();

        $this->ConnectParent("{695BAF6A-A6CB-4989-9659-1C55B81FD458}");

        // do not allow update interval to be less than 5
        $interval = IPS_GetProperty($this->InstanceID, "Interval");
        if($interval < $intervalLimit) {
            IPS_SetProperty($this->InstanceID, "Interval", $intervalLimit);
            IPS_ApplyChanges($this->InstanceID);
            $interval = $intervalLimit;
        }


        // Create SocketController script
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

        // Create variable profiles
        $this->RegisterProfileBooleanEx("BLUEIRIS.AlarmState", "", "", "", Array(
                                                                                Array(false, "Deaktiviert", "", -1),
                                                                                Array(true, "Aktiviert", "", 0x00FF00)
                                                                         ));

        $this->RegisterProfileIntegerEx("BLUEIRIS.Alarm", "", "", "", Array(
                                                                                Array(0, "kein Alarm ausgelöst", "", 0x00FF00)
                                                                         ));

        $this->RegisterProfileBooleanEx("BLUEIRIS.Switch", "", "", "", Array(
                                                                                Array(false, "Aus", "", -1),
                                                                                Array(true, "Ein", "", 0x00FF00)
                                                                         ));

        $this->RegisterProfileBooleanEx("BLUEIRIS.Record", "", "", "", Array(
                                                                                Array(false, "Aus", "", -1),
                                                                                Array(true, "Ein", "", 0xFF0000)
                                                                         ));

        $this->RegisterProfileIntegerEx("BLUEIRIS.PTZ", "", "", "", Array(
                                                                            Array(0, "Links", "", -1),
                                                                            Array(1, "Hoch", "", -1),
                                                                            Array(2, "-", "", -1),
                                                                            Array(3, "Runter", "", -1),
                                                                            Array(4, "Rechts", "", -1),
                                                                            Array(5, "Zoom +", "", -1),
                                                                            Array(6, "Zoom -", "", -1)
                                                                    ));

        // create popups
        $alarmListPopUp = @$this->GetIDForIdent("AlarmListPopUp");
        if(!$alarmListPopUp) {
            $alarmListPopUp = IPS_CreateInstance("{5EA439B8-FB5C-4B81-AA35-1D14F4EA9821}");
            IPS_SetName($alarmListPopUp, "Alarmliste");
            IPS_SetIdent($alarmListPopUp, "AlarmListPopUp");
            IPS_SetIcon($alarmListPopUp, "Alert");
            IPS_SetParent($alarmListPopUp, $this->InstanceID);
        }

        // create variables
        $var = @IPS_GetObjectIDByIdent("CameraEventListHTML", $alarmListPopUp);
        if(!$var) {
            $var = IPS_CreateVariable(3);
            IPS_SetName($var, "letztes Alarmereignis");
            IPS_SetIdent($var, "CameraEventListHTML");
            IPS_SetVariableCustomProfile($var, "~HTMLBox");
            IPS_SetParent($var, $alarmListPopUp);

            SetValue($var, "");
        }

        $var = @IPS_GetObjectIDByIdent("AlarmState", $this->InstanceID);
        if(!$var) {
            $var = IPS_CreateVariable(0);
            IPS_SetName($var, "Alarmierung");
            IPS_SetIdent($var, "AlarmState");
            IPS_SetVariableCustomProfile($var, "BLUEIRIS.AlarmState");
            IPS_SetParent($var, $this->InstanceID);
            $this->EnableAction("AlarmState");

            SetValue($var, false);
        }

        
        $var = @IPS_GetObjectIDByIdent("Alarm", $this->InstanceID);
        if(!$var) {
            $var = IPS_CreateVariable(1);
            IPS_SetName($var, "Alarm");
            IPS_SetIdent($var, "Alarm");
            IPS_SetVariableCustomProfile($var, "BLUEIRIS.Alarm");
            IPS_SetParent($var, $this->InstanceID);
            $this->EnableAction("Alarm");

            SetValue($var, 0);
        }

        $var = @IPS_GetObjectIDByIdent("AlarmTrigger", $this->InstanceID);
        if(!$var) {
            $var = IPS_CreateVariable(3);
            IPS_SetName($var, "Alarm Auslöser");
            IPS_SetIdent($var, "AlarmTrigger");
            IPS_SetParent($var, $this->InstanceID);
        }        

        $this->Update();
    }

    public function RequestAction($Ident, $Value) 
    { 
        if(strlen(trim(IPS_GetProperty($this->InstanceID, "Username"))) == 0 || strlen(trim(IPS_GetProperty($this->InstanceID, "Password"))) == 0) 
        {
            $this->ModuleLogMessage("Fehler: Benutzername oder Passwort nicht gesetzt!");
            return false;
        }

        $camid = null;
        if(stripos($Ident, "_") !== false) {
            $ex = explode("-", $Ident);
            $Ident = $ex[0];
            $camid = $ex[1];
        }

        switch ($Ident) 
        { 
            case "controlptz":
                $this->PTZ($camid, $Value);
                break;
            case "AlarmState":
                if($Value == true)
                    $this->EnableAlarm();
                else
                    $this->DisableAlarm();
                break;
            case "Alarm":
                if($Value == 1000)
                    $this->ResetAlarm();
            default:
                break;
        } 
    }

    public function ReceiveData($JSONString) {
        $data = json_decode($JSONString, true);
        if(isset($data['DataID'])) {
            if($data['DataID'] == "{0AD5DC4B-6CE8-4979-8064-33B7895D6ACA}") {
                // IPS_LogMessage("BlueIris", "JSON: ".$JSONString);
                $buffer = $data['Buffer'];
                if($buffer['cmd'] == "camconfig" || $buffer['cmd'] == "ptz") {
                    $result = $this->Query($buffer);
                }
            }
        }
    }

    public function GetAlertList($time=0) {
        $param = array();
        $param['cmd'] = 'alertlist';
        $param['camera'] = 'index';
        $param['startdate'] = $time;

        $result = json_decode($this->Query($param), true);

        return $result;
    }   

    public function GetClipList($time=0) {
        $param = array();
        $param['cmd'] = 'cliplist';
        $param['camera'] = 'index';
        $param['startdate'] = $time;
        $param['enddate'] = time();
        $param['tiles'] = false;

        $result = json_decode($this->Query($param), true);

        return $result;
    } 

    public function GetCamList() {
        $result = $this->Query(array("cmd" => "camlist"));

        return $result;
    }

    public function Update() {
        if(strlen($this->ReadPropertyString("IPAddress")) > 0 && strlen($this->ReadPropertyInteger("Port")) > 0 && strlen(IPS_GetProperty($this->InstanceID, "Username")) > 0) {
            $result = json_decode($this->GetCamList(), true);

            $message = array();
            $message['Buffer'] = array();

            if($result != null) {
                foreach($result["data"] as $cam) {
                    if($cam["optionValue"] == "index" || $cam["optionValue"] == "@index")
                        continue;

                    $cameraInstance = @IPS_GetInstanceIDByName("Kamera: ".$cam["optionDisplay"], $this->InstanceID);
                    if(!$cameraInstance) {
                        $cameraInstance = IPS_CreateInstance("{4AA1CD94-BA5B-4484-B377-B9F6FBFD22D3}");
                        IPS_SetName($cameraInstance, "Kamera: ".$cam["optionDisplay"]);
                        IPS_SetIdent($cameraInstance, $cam["optionValue"]);
                        IPS_SetParent($cameraInstance, $this->InstanceID);
                    } else {
                        IPS_SetHidden($cameraInstance, false);
                    }

                    if(IPS_GetProperty($cameraInstance, "CamID") != $cam["optionValue"]) {
                        IPS_SetProperty($cameraInstance, "CamID", $cam["optionValue"]);
                        IPS_ApplyChanges($cameraInstance);    
                    }

                    $cam['mediaURL'] = $this->BuildMediaURL($cam["optionValue"]);
                    $cam['pictureURL'] = $this->BuildPictureURL($cam["optionValue"]);

                    $message['DataID'] = "{ED01C3C3-22CF-4F37-9FF4-9D366973853D}";
                    $message['Buffer'][] = $cam;

                    // alerting
                    if($cam['isEnabled'] == true) {
                        if($cam[IPS_GetProperty($this->InstanceID, "TriggerVariable")] == true) {
                            if(GetValue(IPS_GetObjectIDByIdent("AlarmTrigger", $this->InstanceID)) == "") { 
                                $this->SetAlarm($cam);
                            }
                        }
                    }
                }
            }

            try {
                $this->SendDataToParent(json_encode($message));    
            } catch (Exception $e) {
            }
            
        }
    }

    public function Query(array $param) {
        $result = null;

        $session = $this->Connect();
        if($session != null) {
            $param["session"] = $session;
            $result = $this->SendToBlueIrisServer($param);
            $this->Disconnect($session);
        }

        return $result;
    }

    public function EnableAlarm() {
        $var = IPS_GetObjectIDByIdent("AlarmState", $this->InstanceID);

        SetValue($var, true);
    }

    public function DisableAlarm() {
        $var = IPS_GetObjectIDByIdent("AlarmState", $this->InstanceID);

        SetValue($var, false);
    }

    public function ResetAlarm() {
        $var = IPS_GetObjectIDByIdent("Alarm", $this->InstanceID);

        $profileName = "BLUEIRIS.Alarm";
        IPS_DeleteVariableProfile($profileName);
        IPS_CreateVariableProfile($profileName, 1);
        IPS_SetVariableProfileAssociation($profileName, 0, "kein Alarm ausgelöst", "", 0x00FF00);
        IPS_SetVariableCustomProfile($var, $profileName);

        @$this->DisableAction("Alarm");

        $alarmListPopUp = $this->GetIDForIdent("AlarmListPopUp");
        SetValue(IPS_GetObjectIDByIdent("CameraEventListHTML", $alarmListPopUp), "");

        SetValue($var, 0);
        SetValue(IPS_GetObjectIDByIdent("AlarmTrigger", $this->InstanceID), "");
    }

    // PRIVATE FUNCTIONS
    private function BuildURL() {
        if(strlen($this->ReadPropertyString("IPAddress")) > 0 && strlen($this->ReadPropertyInteger("Port")) > 0)
            return 'http://'.$this->ReadPropertyString("IPAddress").':'.$this->ReadPropertyInteger("Port").'/json';
        else
            return null;
    }

    private function BuildMediaURL($camid) {
        if(strlen($this->ReadPropertyString("IPAddress")) > 0 && strlen($this->ReadPropertyInteger("Port")) > 0 && strlen($camid) > 0) {
            $return = 'http://'.$this->ReadPropertyString("IPAddress").':'.$this->ReadPropertyInteger("Port").'/mjpg/'.$camid.'/video.mjpg';
            
            if(strlen($this->ReadPropertyString("Username")) > 0 && strlen($this->ReadPropertyString("Password")) > 0)
                $return .= '?user='.$this->ReadPropertyString("Username").'&pw='.$this->ReadPropertyString("Password");

            return $return;
        }
        else
            return null;
    }

    private function BuildPictureURL($camid) {
        if(strlen($this->ReadPropertyString("IPAddress")) > 0 && strlen($this->ReadPropertyInteger("Port")) > 0 && strlen($camid) > 0) {
            $return = 'http://'.$this->ReadPropertyString("IPAddress").':'.$this->ReadPropertyInteger("Port").'/image/'.$camid.'?time=0&d='.time();
            
            if(strlen($this->ReadPropertyString("Username")) > 0 && strlen($this->ReadPropertyString("Password")) > 0)
                $return .= '&user='.$this->ReadPropertyString("Username").'&pw='.$this->ReadPropertyString("Password");

            return $return;
        }
        else
            return null;
    }

    private function BuildClipURL($clip) {
        if(strlen($this->ReadPropertyString("IPAddress")) > 0 && strlen($this->ReadPropertyInteger("Port")) > 0 && strlen($clip) > 0) {
            if(strstr($clip, '@') === false)
                $clip = '@'.$clip;

            $return = 'http://'.$this->ReadPropertyString("IPAddress").':'.$this->ReadPropertyInteger("Port").'/file/clips/'.$clip.'?time=0';
            
            if(strlen($this->ReadPropertyString("Username")) > 0 && strlen($this->ReadPropertyString("Password")) > 0)
                $return .= '&user='.$this->ReadPropertyString("Username").'&pw='.$this->ReadPropertyString("Password");

            $return = $return."&d=".time();

            return $return;
        }
        else
            return null;   
    }

    private function SendToBlueIrisServer(array $param) {
        $result = null;

        $url = $this->BuildURL(); 

        if($url != null) {
            $ch = curl_init($url);  
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");                                                                       
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
            curl_setopt($ch,CURLOPT_CONNECTTIMEOUT, $this->ReadPropertyInteger("Timeout"));
            if(count($param) > 0) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($param));
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
                    'Content-Type: application/json',                                                                                
                    'Content-Length: ' . strlen(json_encode($param)))                                                                       
                );   
            }
            $result = curl_exec($ch);

            if(curl_errno($ch))
            {
                if($ch == curl_errno($ch)) $this->SetStatus(204); else echo 'Curl error: ' . curl_error($ch);
                return false;
            }
            curl_close($ch);
        }

        return $result;
    }

    private function Connect() {
        $session = null;

        $result = $this->SendToBlueIrisServer(array("cmd" => "login"));
        $result = json_decode($result, true);

        if(isset($result["session"])) {
            $response = md5(IPS_GetProperty($this->InstanceID, "Username").":".$result["session"].":".IPS_GetProperty($this->InstanceID, "Password"));

            $result = $this->SendToBlueIrisServer(array("cmd" => "login", "session" => $result["session"], "response" => $response));
            $result = json_decode($result, true);

            if($result["result"] == "success")
                $session = $result["session"];
        }

        return $session;
    }

    private function Disconnect($session) {
        if(is_null($session)){
            return false;
        } 

        $result = $this->SendToBlueIrisServer(array("cmd" => "logout", "session" => $session));

        $output = json_decode($result, true);
        if($output["result"] == "success") {
            return true;
        } else {
            return false;
        }
    }

    private function SetAlarm($cam) {
        $var = IPS_GetObjectIDByIdent("Alarm", $this->InstanceID);

        if(GetValue(IPS_GetObjectIDByIdent("AlarmState", $this->InstanceID)) == true) {
            if(GetValue($var) == 0) {
                $profileName = "BLUEIRIS.Alarm";
                IPS_DeleteVariableProfile($profileName);
                IPS_CreateVariableProfile($profileName, 1);
                IPS_SetVariableProfileAssociation($profileName, 1, "Alarm ausgelöst", "", 0xFF0000);
                IPS_SetVariableProfileAssociation($profileName, 1000, "Reset", "", null);
                IPS_SetVariableCustomProfile($var, $profileName);

                $this->EnableAction("Alarm");

                SetValue($var, 1);
                SetValue(IPS_GetObjectIDByIdent("AlarmTrigger", $this->InstanceID), $cam['optionValue']); 

                $this->RenderAlarmList($cam);
            }
        }
    }

    private function RenderAlarmList($cam) {
        $html = "<img src='".$this->BuildClipURL($cam['lastalert'])."'>";

        $camID = GetValue(IPS_GetObjectIDByIdent("AlarmTrigger", $this->InstanceID));
        $cameraInstance = IPS_GetObjectIDByIdent($camID, $this->InstanceID);

        $alarmListPopUp = $this->GetIDForIdent("AlarmListPopUp");
        $htmlVar = IPS_GetObjectIDByIdent("CameraEventListHTML", $alarmListPopUp);

        SetValue($htmlVar, $html);
    }

    // HELPER FUNCTIONS
    protected function RegisterProfileInteger($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize) {
        if(!IPS_VariableProfileExists($Name)) {
            IPS_CreateVariableProfile($Name, 1);
        } else {
            $profile = IPS_GetVariableProfile($Name);
            if($profile['ProfileType'] != 1)
            throw new Exception("Variable profile type does not match for profile ".$Name);
        }
        
        IPS_SetVariableProfileIcon($Name, $Icon);
        IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
        IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);
        
    }

    protected function RegisterProfileIntegerEx($Name, $Icon, $Prefix, $Suffix, $Associations) {
        if ( sizeof($Associations) === 0 ){
            $MinValue = 0;
            $MaxValue = 0;
        } else {
            $MinValue = $Associations[0][0];
            $MaxValue = $Associations[sizeof($Associations)-1][0];
        }
        
        $this->RegisterProfileInteger($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, 0);
        
        foreach($Associations as $Association) {
            IPS_SetVariableProfileAssociation($Name, $Association[0], $Association[1], $Association[2], $Association[3]);
        }
        
    }

    protected function RegisterProfileBoolean($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize) {
        if(!IPS_VariableProfileExists($Name)) {
            IPS_CreateVariableProfile($Name, 0);
        } else {
            $profile = IPS_GetVariableProfile($Name);
            if($profile['ProfileType'] != 0)
            throw new Exception("Variable profile type does not match for profile ".$Name);
        }
        
        IPS_SetVariableProfileIcon($Name, $Icon);
        IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
        IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);  
    }
    
    protected function RegisterProfileBooleanEx($Name, $Icon, $Prefix, $Suffix, $Associations) {
        if ( sizeof($Associations) === 0 ){
            $MinValue = 0;
            $MaxValue = 0;
        } else {
            $MinValue = $Associations[0][0];
            $MaxValue = $Associations[sizeof($Associations)-1][0];
        }
        
        $this->RegisterProfileBoolean($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, 0);
        
        foreach($Associations as $Association) {
            IPS_SetVariableProfileAssociation($Name, $Association[0], $Association[1], $Association[2], $Association[3]);
        }
        
    }
    
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

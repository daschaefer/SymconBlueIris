<?php

class BlueIris extends IPSModule
{       
    public function Create()
    {
        parent::Create();

        // Public properties
        $this->RegisterPropertyString("IPAddress", "");
        $this->RegisterPropertyInteger("Port", 81);
        $this->RegisterPropertyString("Username", "admin");
        $this->RegisterPropertyString("Password", "");
        $this->RegisterPropertyString("LoadCameraVariables", "minimal");
        $this->RegisterPropertyString("HookUsername", "blueiris");
		$this->RegisterPropertyString("HookPassword", $this->GeneratePassphrase(18));
        $this->RegisterPropertyString('MQTTTopic', 'SymconBlueIris');
        $this->RegisterPropertyInteger('ControllerScriptID', -1);
        $this->RegisterPropertyBoolean('IG_Enabled', false);
        $this->RegisterPropertyInteger('IG_RefreshInterval', 10);
        $this->RegisterPropertyInteger('GridMaxX', 600);
    }
    
    public function ApplyChanges()
    {
        $intervalLimit = 3;

        parent::ApplyChanges();

        // // Create variable profiles
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

        $var = @IPS_GetObjectIDByIdent("CameraGridHTML", $this->InstanceID);
        if(!$var) {
            $var = IPS_CreateVariable(3);
            IPS_SetName($var, "Kamera Raster");
            IPS_SetIdent($var, "CameraGridHTML");
            IPS_SetVariableCustomProfile($var, "~HTMLBox");
            IPS_SetParent($var, $this->InstanceID);

            SetValue($var, "");
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

        // Create additional scripts
        $ScriptID = @$this->GetIDForIdent("controller");
        if($ScriptID === false) {
            $ScriptID = $this->RegisterScript("controller", "controller", file_get_contents(__DIR__ . "/controller.php"), 100);
        } else {
            IPS_SetScriptContent($ScriptID, file_get_contents(__DIR__ . "/controller.php"));
        }
        IPS_SetHidden($ScriptID, true);
        IPS_SetProperty($this->InstanceID, "ControllerScriptID", $ScriptID);

        // register hook
        $this->RegisterHook("/hook/blueiris");

        // cyclic events
        $ScriptID = @$this->GetIDForIdent("update");
        if($ScriptID === false) {
            $ScriptID = $this->RegisterScript("update", "update", file_get_contents(__DIR__ . "/update.php"), 100);
        } else {
            IPS_SetScriptContent($ScriptID, file_get_contents(__DIR__ . "/update.php"));
        }
        IPS_SetHidden($ScriptID, true);
        $updateCheck = @IPS_GetEventIDByName("Update", $ScriptID);
        if(!$updateCheck) {
            $updateCheck = IPS_CreateEvent(1);
            IPS_SetParent($updateCheck, $ScriptID);
            IPS_SetName($updateCheck, "Update");
            IPS_SetEventCyclic($updateCheck, 0, 0, 0, 2, 2, 1);
            IPS_SetEventActive($updateCheck, true);
        }

        $this->RenderCameraGrid();
        $this->Update();
    }

    public function Update() {
        $this->UpdateCameraList();
    }

    public function RequestAction($Ident, $Value) 
    { 
        switch ($Ident) 
        { 
            case "Alarm":
                if($Value == 1000) // do reset
                    $this->ResetAlarm();
                break;
            case "AlarmState":
                if($Value == true)
                    $this->EnableAlarm();
                else
                    $this->DisableAlarm();
                break;
            default:
                break;
        } 
    }

    public function EnableCamera(string $cameraID) {
        $cameraCategory = IPS_GetObjectIDByIdent($cameraID, $this->InstanceID);

        $this->Query(array("cmd" => "camconfig", "camera" => $cameraID, "enable" => true));

        sleep(5);
        $this->Update();
    }

    public function DisableCamera(string $cameraID) {
        $cameraCategory = IPS_GetObjectIDByIdent($cameraID, $this->InstanceID);

        $this->Query(array("cmd" => "camconfig", "camera" => $cameraID, "enable" => false));

        sleep(5);
        $this->Update();
    }

    public function EnableMotionDetection(string $cameraID) {
        $cameraCategory = IPS_GetObjectIDByIdent($cameraID, $this->InstanceID);

        $this->Query(array("cmd" => "camconfig", "camera" => $cameraID, "motion" => true));

        sleep(2);
        $this->Update();
    }

    public function DisableMotionDetection(string $cameraID) {
        $cameraCategory = IPS_GetObjectIDByIdent($cameraID, $this->InstanceID);

        $this->Query(array("cmd" => "camconfig", "camera" => $cameraID, "motion" => false));

        sleep(2);
        $this->Update();
    }

    public function PTZ(string $cameraID, int $ptz_command) {
        /*
            0: Pan left
            1: Pan right
            2: Tilt up
            3: Tilt down
            4: Center or home (if supported by camera)
            5: Zoom in
            6: Zoom out
            8..10: Power mode, 50, 60, or outdoor
            11..26: Brightness 0-15
            27..33: Contrast 0-6
            34..35: IR on, off
            101..120: Go to preset position 1..20
        */

        $result = $this->Query(array("cmd" => "ptz", "camera" => $cameraID, "button" => $ptz_command, "updown" => 1));
        $result = $this->Query(array("cmd" => "ptz", "camera" => $cameraID, "button" => $ptz_command, "updown" => 1));
    }

    protected function UpdateCameraList() {
        if(strlen($this->ReadPropertyString("IPAddress")) > 0 && strlen($this->ReadPropertyInteger("Port")) > 0 && strlen(IPS_GetProperty($this->InstanceID, "Username")) > 0) {
            $result = json_decode($this->GetCamList(), true);

            if($result != null) {
                foreach($result["data"] as $cam) {
                    if($cam["optionValue"] == "index" || $cam["optionValue"] == "@index" || $cam["optionValue"] == "Index" || $cam["optionValue"] == "@Index")
                        continue;

                    $cameraCategory = @IPS_GetObjectIDByIdent($cam["optionValue"], $this->InstanceID);
                    if(!$cameraCategory) {
                        $cameraCategory = IPS_CreateCategory();
                        IPS_SetName($cameraCategory, "Kamera: ".$cam["optionDisplay"]);
                        IPS_SetIdent($cameraCategory, $cam["optionValue"]);
                        IPS_SetParent($cameraCategory, $this->InstanceID);
                    } else {
                        IPS_SetHidden($cameraCategory, false);
                    }

                    //media elements
                    $mediaData = $this->GetMedia($cam["optionValue"]);
                    $media = @IPS_GetMediaIDByName("Kamera Stream", $cameraCategory);
                    if(!$media) {
                        $media = IPS_CreateMedia(3);
                        IPS_SetName($media, "Kamera Stream");
                        IPS_SetMediaFile($media, $mediaData['mediaURL'], true);
                        IPS_SetParent($media, $cameraCategory);
                    }
                    else {
                        if(md5(IPS_GetMedia($media)['MediaFile']) != md5($mediaData['mediaURL'])) {
                            IPS_SetMediaFile($media, $mediaData['mediaURL'], true);
                        }
                    }

                    //image grabber
                    if($this->ReadPropertyBoolean("IG_Enabled") == true) {
                        $grabber = @IPS_GetObjectIDByIdent("ImageGrabber", $cameraCategory);
                        if(!$grabber) {
                            $grabber = IPS_CreateInstance("{5A5D5DBD-53AB-4826-8B09-71E9E4E981E5}");
                            IPS_SetName($grabber, "Kamera ImageGrab");
                            IPS_SetIdent($grabber, "ImageGrabber");
                            IPS_SetIcon($grabber, "Image");
                            IPS_SetParent($grabber, $cameraCategory);

                            IPS_SetProperty($grabber, 'ImageType', 1);
                            IPS_SetProperty($grabber, 'AuthUser', IPS_GetProperty($this->InstanceID, "Username"));
                            IPS_SetProperty($grabber, 'AuthPass', IPS_GetProperty($this->InstanceID, "Password"));
                            IPS_SetProperty($grabber, 'UseBasicAuth', true);
                            IPS_SetProperty($grabber, 'ImageAddress', $mediaData['cleanPictureURL']);
                            IPS_SetProperty($grabber, 'Interval', $this->ReadPropertyInteger("IG_RefreshInterval"));
                            IPS_ApplyChanges($grabber);

                            @IG_UpdateImage($grabber);
                        } else {
                            if($this->ReadPropertyInteger("IG_RefreshInterval") != IPS_GetProperty($grabber, 'Interval')) {
                                IPS_SetProperty($grabber, 'Interval', $this->ReadPropertyInteger("IG_RefreshInterval"));
                                IPS_ApplyChanges($grabber);
                            }
                        }
                    }
                    else {
                        $grabber = @IPS_GetObjectIDByIdent("ImageGrabber", $cameraCategory);
                        if($grabber) {
                            $children = IPS_GetChildrenIDs($grabber);
                            foreach($children as $child) {
                                IPS_DeleteMedia($child, true);
                            }
                            IPS_DeleteInstance($grabber);
                        }
                    }
                    

                    //variables
                    $this->UpdateCameraVariables($cam, $cameraCategory);

                    $cameraConfig = json_decode($this->GetCamConfig($cam['optionValue']), true);
                    $this->UpdateCameraVariables($cameraConfig['data'], $cameraCategory);
                }
            }
        }
    }

    private function GetCamList() {
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
        */
        $result = $this->Query(array("cmd" => "camlist"));

        return $result;
    }

    private function GetCamConfig($cameraID) {
        /*
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
        $result = $this->Query(array("camera" =>$cameraID, "cmd" => "camconfig"));

        return $result;
    }

    private function UpdateCameraVariables($cameraData, $cameraCategory) {
        $cameraIdent = IPS_GetObject($cameraCategory)['ObjectIdent'];

        $variablesCreateInMinimal = array(  "optionDisplay"
                                            ,"optionValue"
                                            ,"alertutc" 
                                            ,"isEnabled"
                                            ,"motion"
                                            ,"isOnline"
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
                                            'motion' => 'BLUEIRIS.Switch',
                                            'isPaused' => 'BLUEIRIS.Switch'
                                    );

        foreach($cameraData as $key => $value) {
            if(is_array($value))
                continue;

            if(count($variablesCreate) > 0 && !in_array($key, $variablesCreate)) {
                // delete possible existing variable
                $var = @IPS_GetObjectIDByIdent($key, $cameraCategory);
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

            $var = @IPS_GetObjectIDByIdent($key."_".$cameraIdent, $cameraCategory);
            if(!$var) {
                $var = IPS_CreateVariable($type);
                IPS_SetName($var, $key);
                IPS_SetIdent($var, $key."_".$cameraIdent);

                IPS_SetParent($var, $cameraCategory);
            }

            if(array_key_exists($key, $userControlledProperties) === true) {
                IPS_SetVariableCustomProfile($var, $userControlledProperties[$key]);
                IPS_SetVariableCustomAction($var, IPS_GetProperty($this->InstanceID, "ControllerScriptID"));
            }

            if(GetValue($var) != $value)
                SetValue($var, $value);

            if($key == "isEnabled")
                 $this->HideControls($cameraCategory, $value);
        }
    }

    private function HideControls($cameraCategory, $boolean) {
        foreach(IPS_GetChildrenIDs($cameraCategory) as $childID) {
            $Child = IPS_GetObject($childID);

            $hide = false;
            if($boolean == false)
                $hide = true;
            
            if(stristr($Child['ObjectIdent'], "isEnabled") === FALSE) {
                if($Child['ObjectIsHidden'] != $hide)
                    IPS_SetHidden($childID, $hide);
            }
        }
    }

    public function GetMedia($camID) {
        $media['mediaURL'] = $this->BuildMediaURL($camID);
        $media['pictureURL'] = $this->BuildPictureURL($camID);
        $media['cleanPictureURL'] = $this->BuildCleanPictureURL($camID);

        return $media;
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

    public function GenerateNewHookPassword() {
        $password = $this->GeneratePassphrase(18);
        IPS_SetProperty($this->InstanceID, "HookPassword", $password);
    }

    public function ProcessHookData() {
        // IPS_LogMessage("WebHook GET", print_r($_GET, true));

        if($_IPS['SENDER'] == "Execute") {
            echo "This script cannot be used this way.";
            return;
        } else {
            $instanceID = $this->InstanceID;
            if(isset($_GET['instanceid']) && $_GET['instanceid'] > 0) {
                if(IPS_GetObject($_GET['instanceid'])['ObjectType'] == 1) {
                    $instanceID = $_GET['instanceid'];
                }
            }

            if((IPS_GetProperty($instanceID, "HookUsername") != "") || (IPS_GetProperty($instanceID, "HookPassword") != "")) {
				if(!isset($_SERVER['PHP_AUTH_USER']))
					$_SERVER['PHP_AUTH_USER'] = "";
				if(!isset($_SERVER['PHP_AUTH_PW']))
					$_SERVER['PHP_AUTH_PW'] = "";
					
				if(($_SERVER['PHP_AUTH_USER'] != IPS_GetProperty($instanceID, "HookUsername")) || ($_SERVER['PHP_AUTH_PW'] != IPS_GetProperty($instanceID, "HookPassword"))) {
					header('WWW-Authenticate: Basic Realm="BlueIris WebHook"');
					header('HTTP/1.0 401 Unauthorized');
					echo "Authorization required";
					return;
				}
			}
            
            if(isset($_GET)) {
                if(isset($_GET['cam']) && isset($_GET['action'])) {
                    if($_GET['action'] == "trigger") { // when cam is triggered by event
                        $this->Update();

                        $this->SetAlarm($_GET['cam']);
                    }
                }
            }
        }
    }



    // PRIVATE FUNCTIONS

    private function RegisterHook($WebHook) {
        $ids = IPS_GetInstanceListByModuleID("{015A6EB8-D6E5-4B93-B496-0D3F77AE9FE1}");
        if(sizeof($ids) > 0) {
            $hooks = json_decode(IPS_GetProperty($ids[0], "Hooks"), true);
            $found = false;
            foreach($hooks as $index => $hook) {
                if($hook['Hook'] == $WebHook) {
                    if($hook['TargetID'] == $this->InstanceID)
                        return;
                    $hooks[$index]['TargetID'] = $this->InstanceID;
                    $found = true;
                }
            }
            if(!$found) {
                $hooks[] = Array("Hook" => $WebHook, "TargetID" => $this->InstanceID);
            }
            IPS_SetProperty($ids[0], "Hooks", json_encode($hooks));
            IPS_ApplyChanges($ids[0]);
        }
    }

    private function Query(array $param) {
        $result = null;

        $session = $this->ConnectToBlueIrisServer();
        if($session != null) {
            $param['session'] = $session;
            $result = $this->SendToBlueIrisServer($param);

            $this->DisconnectFromBlueIrisServer($session);
        }

        return $result;
    }

    private function ConnectToBlueIrisServer() {
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

    private function DisconnectFromBlueIrisServer($session) {
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

    private function SendToBlueIrisServer(array $param) {
        $result = null;

        $url = $this->BuildURL();

        if($url != null) {
            $ch = curl_init($url); 
             
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");                                                                       
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
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

    private function BuildURL() {
        if(strlen($this->ReadPropertyString("IPAddress")) > 0 && strlen($this->ReadPropertyInteger("Port")) > 0)
            return 'http://'.$this->ReadPropertyString("IPAddress").':'.$this->ReadPropertyInteger("Port").'/json';
        else
            return null;
    }

    private function BuildMediaURL($camID) {
        if(strlen($this->ReadPropertyString("IPAddress")) > 0 && strlen($this->ReadPropertyInteger("Port")) > 0 && strlen($camID) > 0) {
            $return = 'http://'.$this->ReadPropertyString("IPAddress").':'.$this->ReadPropertyInteger("Port").'/mjpg/'.$camID.'/video.mjpg';
            
            if(strlen($this->ReadPropertyString("Username")) > 0 && strlen($this->ReadPropertyString("Password")) > 0)
                $return .= '?user='.$this->ReadPropertyString("Username").'&pw='.$this->ReadPropertyString("Password");

            return $return;
        }
        else
            return null;
    }

    private function BuildPictureURL($camID) {
        if(strlen($this->ReadPropertyString("IPAddress")) > 0 && strlen($this->ReadPropertyInteger("Port")) > 0 && strlen($camID) > 0) {
            $return = 'http://'.$this->ReadPropertyString("IPAddress").':'.$this->ReadPropertyInteger("Port").'/image/'.$camID.'?time=0&d='.time();
            
            if(strlen($this->ReadPropertyString("Username")) > 0 && strlen($this->ReadPropertyString("Password")) > 0)
                $return .= '&user='.$this->ReadPropertyString("Username").'&pw='.$this->ReadPropertyString("Password");

            return $return;
        }
        else
            return null;
    }

    private function BuildCleanPictureURL($camID) {
        if(strlen($this->ReadPropertyString("IPAddress")) > 0 && strlen($this->ReadPropertyInteger("Port")) > 0 && strlen($camID) > 0) {
            $return = 'http://'.$this->ReadPropertyString("IPAddress").':'.$this->ReadPropertyInteger("Port").'/image/'.$camID.'?time=0&d='.time();

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
                SetValue(IPS_GetObjectIDByIdent("AlarmTrigger", $this->InstanceID), $cam); 

                $this->RenderAlarmList($cam);
            }
        }
    }

    private function RenderAlarmList($cam) {
        sleep(2);
        $cameraCategory = IPS_GetObjectIDByIdent($cam, $this->InstanceID);
        $lastAlert = IPS_GetObjectIDByIdent("lastalert_".$cam, $cameraCategory);

        $html = "<img src='".$this->BuildClipURL(GetValue($lastAlert)."'>");

        $camID = GetValue(IPS_GetObjectIDByIdent("AlarmTrigger", $this->InstanceID));
        $cameraInstance = IPS_GetObjectIDByIdent($camID, $this->InstanceID);

        $alarmListPopUp = $this->GetIDForIdent("AlarmListPopUp");
        $htmlVar = IPS_GetObjectIDByIdent("CameraEventListHTML", $alarmListPopUp);

        SetValue($htmlVar, $html);
    }

    private function RenderCameraGrid() {
        $grid = @IPS_GetObjectIDByIdent("CameraGridHTML", $this->InstanceID);

        if(!$grid)
            return;

            if($this->ReadPropertyBoolean("IG_Enabled") == true) {
                SetValue($grid, "");
                return;
            }

            $out = "   <style type='text/css'>
                            .bi_grid_parent {
                                display: flex;
                                justify-content: space-evenly;
                                flex-wrap: wrap;
                            }
                            
                            .bi_grid_media {
                                padding: 10px;
                                margin: 10px 10px;
                                flex-basis: 15%;
                            }

                            .bi_grid_media img {
                                max-width: ".$this->ReadPropertyInteger("GridMaxX")."px;
                                border: 1px solid rgba(255,255,255,0.15);
                            }
                        </style>";
            
            $result = json_decode($this->GetCamList(), true);

            if($result != null) {
                $out .= "<div class=\"bi_grid_parent\">";

                foreach($result["data"] as $cam) {
                    if($cam["optionValue"] == "index" || $cam["optionValue"] == "@index" || $cam["optionValue"] == "Index" || $cam["optionValue"] == "@Index")
                        continue;

                    if($cam['isEnabled'] == false)
                        continue;
                        
                    $mediaData = $this->GetMedia($cam["optionValue"]);
                    
                    $out .= "<div class=\"bi_grid_media\"><img src=\"".$mediaData['mediaURL']."\" type=\"video/mpeg\"/></div>";
                }

                $out .= "</div>";
            }


            SetValue($grid, $out);
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

    protected function GeneratePassphrase($length) {
        $passphrase = "";
            $chars = array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '!', '$', '#', '-', '_');
            $charLastIndex = 0;
            for($i=0; $i < $length; $i++) {
               $randIndex = rand(0, (count($chars)-1));
               while (abs($randIndex - $charLastIndex) < 10) {
                   $randIndex = rand(0, (count($chars)-1));
               }
               $charLastIndex = $randIndex;
               $passphrase .= $chars[$randIndex];
            }
        
        return $passphrase;
    }

    protected function ModuleLogMessage($message) {
        IPS_LogMessage(IPS_GetObject($this->InstanceID)['ObjectName'], $message);
    }
}

?>
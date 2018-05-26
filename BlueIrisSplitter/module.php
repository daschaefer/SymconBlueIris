<?

/*
    Message ID's:
    {01AABE59-055A-4B4E-BCE0-C7B1217FF29C} = Message addressed to BlueIrisSplitter
    {0AD5DC4B-6CE8-4979-8064-33B7895D6ACA} = Message addressed to BlueIris instance
    {ED01C3C3-22CF-4F37-9FF4-9D366973853D} = Message addressed to BlueIrisCamera instances
*/

class BlueIrisSplitter extends IPSModule
{       
    public function Create()
    {
        parent::Create();
    }
    
    public function ApplyChanges()
    {
        parent::ApplyChanges();

        $this->RequireParent("{6179ED6A-FC31-413C-BB8E-1204150CF376}");
    }

    public function ForwardData($JSONString)
    {
        $data = json_decode($JSONString);
        // IPS_LogMessage("IOSplitter FRWD", utf8_decode($JSONString));

        // if not directly addressed to splitter itself, then forward whole message
        if(property_exists($data, 'DataID')) {
            if($data->DataID != "{01AABE59-055A-4B4E-BCE0-C7B1217FF29C}") 
                $this->SendDataToChildren($JSONString);
        } else {
            $this->ModuleLogMessage("Unbekannter Verbindungsfehler - Häufige Ursachen: Username oder Passwort falsch, IP-Adresse wurde wegen Falschanmeldung gesperrt.");
        }

        //Normally we would wait here for ReceiveData getting called asynchronically and buffer some data
        //Then we should extract the relevant feedback/data and return it to the caller
        return true;
    }
    
    // public function ReceiveData($JSONString)
    // {
    //     $data = json_decode($JSONString);
    //     IPS_LogMessage("IOSplitter RECV", utf8_decode($data->Buffer));
    //     //We would parse our payload here before sending it further...
    //     //Lets just forward to our children
    //     $this->SendDataToChildren(json_encode(Array("DataID" => "{ED01C3C3-22CF-4F37-9FF4-9D366973853D}", "Buffer" => $data->Buffer)));
    // }

    protected function ModuleLogMessage($message) {
        IPS_LogMessage(IPS_GetObject($this->InstanceID)['ObjectName'], $message);
    }
}

?>
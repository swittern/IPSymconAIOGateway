<?

require_once(__DIR__ . "/../AIOGatewayClass.php");  // diverse Klassen

class AIOITDevice extends IPSModule
{

   
    public function Create()
    {
        //Never delete this line!
        parent::Create();

        // 1. Verf�gbarer AIOSplitter wird verbunden oder neu erzeugt, wenn nicht vorhanden.
        $this->ConnectParent("{7E03C651-E5BF-4EC6-B1E8-397234992DB4}");
		
		$this->RegisterPropertyString("ITFamilyCode", "");
		$this->RegisterPropertyString("ITDeviceCode", "");
		$this->RegisterPropertyString("ITType", "Switch");
		$this->RegisterPropertyBoolean("LearnITCode", false);
		
		
    }


    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();
		
		// ITFamilyCode und ITDeviceCode pr�fen
        $ITFamilyCode = $this->ReadPropertyString('ITFamilyCode');
		$ITDeviceCode = $this->ReadPropertyString('ITDeviceCode');
  		$LearnITCode = $this->ReadPropertyBoolean('LearnITCode');
		
        if ($LearnITCode)
		{
			$this->Learn();
		}
		elseif ( $ITFamilyCode == '' or $ITDeviceCode == '')
        {
            // Status Error Felder d�rfen nicht leer sein
            $this->SetStatus(202);
        }
		else 
		{
			//Eingabe �berpr�fen
			if (strlen($ITFamilyCode)<1 or strlen($ITFamilyCode)>1)
				{
					$this->SetStatus(203);	
				}
			elseif (strlen($ITDeviceCode)<1 or strlen($ITDeviceCode)>2)
				{
					$this->SetStatus(204);	
				}
			elseif (!ctype_digit($ITDeviceCode))
				{
					$this->SetStatus(205);
				}
			else
				{
					// Status aktiv
					$this->SetStatus(102);
					$this->SetupVar();
					$this->SetupProfiles();
				}		
			
		}	
		
		//Beschreibung in Modulsfeld setzen
		//IPS_SetInfo(47381, "Intertechno mit AIO Gateway schalten");
		
		

       
		
	
	}
		
	/**
    * Die folgenden Funktionen stehen automatisch zur Verf�gung, wenn das Modul �ber die "Module Control" eingef�gt wurden.
    * Die Funktionen werden, mit dem selbst eingerichteten Prefix, in PHP und JSON-RPC wiefolgt zur Verf�gung gestellt:
    *
	* PUBLIC
    */
	protected function SetupVar()
	{
		//Generelle-Variablen anlegen
		$stateId = $this->RegisterVariableBoolean("STATE", "Status", "~Switch", 1);
		$this->EnableAction("STATE");
					
		// Variablen bei Dimmer anlegen
		$ITType = $this->ReadPropertyString('ITType');
		if ($ITType === "Dimmer")
			{
			$this->RegisterVariableInteger("Dimmer", "Dimmer", "IntertechnoDimmer.AIOIT", 2);
			$this->EnableAction("Dimmer");
			}
	}
	
	protected function SetupProfiles()
	{
		// Profile anlegen
		$this->RegisterProfileIntegerEx("IntertechnoDimmer.AIOIT", "Intensity", "", "", Array
			(
				Array(0, "0 %",  "", -1),
				Array(1, "10 %",  "", -1),
				Array(2, "20 %",  "", -1),
				Array(3, "30 %",  "", -1),
				Array(4, "40 %",  "", -1),
				Array(5, "50 %",  "", -1),
				Array(6, "60 %",  "", -1),
				Array(7, "70 %",  "", -1),
				Array(8, "80 %",  "", -1),
				Array(9, "90 %",  "", -1),
				Array(10, "100 %", "", -1)
			));
	}
	
	
	public function RequestAction($Ident, $Value)
    {
        switch($Ident) {
            case "STATE":
                $this->SetPowerState($Value);
				break;
			case "Dimmer":
                switch($Value) {
                    case 0: //0
						$this->PowerOff();
                        break;
                    case 1: //10
                        $this->Set10();
						break;
                    case 2: //20
                        $this->Set20();
						break;
                    case 3: //30
                        $this->Set30();
                        break;
                    case 4: //40
                        $this->Set40();
                        break;
					case 5: //50
                        $this->Set50();
                        break;
					case 6: //60
                        $this->Set60();
                        break;
					case 7: //70
                        $this->Set70();
                        break;
					case 8: //80
                        $this->Set80();
                        break;
					case 9: //90
                        $this->Set90();
                        break;
					case 10: //100
                        $this->PowerOn();
                        break;		
                }
                break;	
            default:
                throw new Exception("Invalid ident");
        }
    }
	
	
	
	protected function GetParent()
    {
        $instance = IPS_GetInstance($this->InstanceID);//array
		return ($instance['ConnectionID'] > 0) ? $instance['ConnectionID'] : false;//ConnectionID
    }
	
	//Berechnet den Sendecode aus Familien und Devicecode 
	// Umrechnung in Hexadezimal Code A entspricht 0, Device 1 entspricht 0
	
	protected function Calculate(){
		$ITFamilyCode = $this->ReadPropertyString('ITFamilyCode');
		$ITDeviceCode = $this->ReadPropertyString('ITDeviceCode');
		$ITFamilyCode = ord(strtoupper($ITFamilyCode)) - ord('A');
		$ITDeviceCode = intval($ITDeviceCode)-1;
		$IT_send = $ITFamilyCode.$ITDeviceCode;
		return $IT_send;
	}
	
	//IP Gateway 
	protected function GetIPGateway(){
		$ParentID = $this->GetParent();
		$IPGateway = IPS_GetProperty($ParentID, 'Host');
		return $IPGateway;
	}
	
	protected function GetPassword(){
		$ParentID = $this->GetParent();
		$GatewayPassword = IPS_GetProperty($ParentID, 'Passwort');
		return $GatewayPassword;
	}
	
		
	protected function SetPowerState($state) {
		$ITType = $this->ReadPropertyString('ITType');
		if ($state === true && $ITType === "Dimmer")
			{
			SetValueInteger($this->GetIDForIdent('Dimmer'), 10);
			}
		elseif ($state === false && $ITType === "Dimmer")
			{
			SetValueInteger($this->GetIDForIdent('Dimmer'), 0);
			}
		SetValueBoolean($this->GetIDForIdent('STATE'), $state);
		if ($state === true)
		{
		$action = "E";
		return $this->SendCommand($action);
		}
		else
		{
		$action = "6";
		return $this->SendCommand($action);
		}
	}
	
	   	
	//IT Befehl E schaltet an
	public function PowerOn() {
		if ($this->ReadPropertyString('ITType') == "Dimmer")
		{
			SetValueInteger($this->GetIDForIdent('Dimmer'), 10);
		}
		SetValueBoolean($this->GetIDForIdent('STATE'), true);
		$action = "E";
		return $this->SendCommand($action);
		}
		
	//IT Befehl 6 schaltet aus
	public function PowerOff() {                
		if ($this->ReadPropertyString('ITType') == "Dimmer")
		{
			SetValueInteger($this->GetIDForIdent('Dimmer'), 0);
		}
		SetValueBoolean($this->GetIDForIdent('STATE'), false);
		$action = "6";
		return $this->SendCommand($action);
		}
		
	//Senden eines Befehls an Intertechno
	// Sendestring IT /command?XC_FNC=SendSC&type=IT&data=
	private $response = false;
	protected function SendCommand($action)
	{
		$IT_send = $this->Calculate();
		$GatewayPassword = $this->GetPassword();
		
		if ($GatewayPassword !== "")
		{
			$gwcheck = file_get_contents("http://".$this->GetIPGateway()."/command?XC_USER=user&XC_PASS=".$GatewayPassword."&XC_FNC=SendSC&type=IT&data=".$IT_send.$action);
			IPS_LogMessage( "Adresse:" , $IT_send );
			IPS_LogMessage( "AIOGateway:" , "Senden an Gateway mit Passwort" );
		}
		else
		{
			$gwcheck = file_get_contents("http://".$this->GetIPGateway()."/command?XC_FNC=SendSC&type=IT&data=".$IT_send.$action);
			IPS_LogMessage( "Adresse:" , $IT_send );
		}
		
		if ($gwcheck == "{XC_SUC}")
			{
			$this->response = true;	
			}
		elseif ($gwcheck == "{XC_AUT}")
		{
			//Passwort falsch
			echo "Keine Authentizifierung m�glich. Gateway Passwort ist falsch.";
			IPS_LogMessage( "Adresse:" , $address );
		IPS_LogMessage( "RTS Command:" , $command );
		}
		return $this->response;
	}
	
	//Dimmen Anschaltbefehl + 00, 10, 20 - F0
	// 00, 10, 20, 30, 40, 50, 60, 70, 80, 90, A0, B0, C0, D0, E0, F0 ? Welche Dimmstufe
	
	// ? - Auf 10% dimmen
	public function Set10() {
		SetValueInteger($this->GetIDForIdent('Dimmer'), 1);
		$command = "E00";
        return $this->SendCommand($command);
        }
	
	// ? - Auf 20% dimmen
	public function Set20() {
		SetValueInteger($this->GetIDForIdent('Dimmer'), 2);
		$command = "E10";
        return $this->SendCommand($command);
        }
		
	// ? - Auf 30% dimmen
	public function Set30() {
		SetValueInteger($this->GetIDForIdent('Dimmer'), 3);
		$command = "E20";
        return $this->SendCommand($command);
        }

	// ? - Auf 40% dimmen
	public function Set40() {
		SetValueInteger($this->GetIDForIdent('Dimmer'), 4);
		$command = "E30";
        return $this->SendCommand($command);
        }

	// ? - Auf 50% dimmen
	public function Set50() {
		SetValueInteger($this->GetIDForIdent('Dimmer'), 5);
		$command = "E40";
        return $this->SendCommand($command);
        }

	// ? - Auf 60% dimmen
	public function Set60() {
		SetValueInteger($this->GetIDForIdent('Dimmer'), 6);
		$command = "E50";
        return $this->SendCommand($command);
        }

	// ? - Auf 70% dimmen
	public function Set70() {
		SetValueInteger($this->GetIDForIdent('Dimmer'), 7);
		$command = "E60";
        return $this->SendCommand($command);
        }

	// ? - Auf 80% dimmen
	public function Set80() {
		SetValueInteger($this->GetIDForIdent('Dimmer'), 8);
		$command = "E70";
        return $this->SendCommand($command);
        }

	// ? - Auf 90% dimmen
	public function Set90() {
		SetValueInteger($this->GetIDForIdent('Dimmer'), 9);
		$command = "E80";
        return $this->SendCommand($command);
        }	
	
	
	//Anmelden eines IT Ger�ts an das a.i.o. gateway:
	//http://{IP-Adresse-des-Gateways}/command?XC_FNC=LearnSC&type=IT
	public function Learn()
		{
		$GatewayPassword = $this->GetPassword();
		if ($GatewayPassword !== "")
		{
			$address = file_get_contents("http://".$this->GetIPGateway()."/command?XC_USER=user&XC_PASS=".$GatewayPassword."&XC_FNC=LearnSC&type=IT");
		}
		else
		{
			$address = file_get_contents("http://".$this->GetIPGateway()."/command?XC_FNC=LearnSC&type=IT");
		}
		
		//kurze Pause w�hrend das Gateway im Lernmodus ist
		IPS_Sleep(1000); //1000 ms
		if ($address == "{XC_ERR}Failed to learn code")//Bei Fehler
			{
			$this->response = false;
			$instance = IPS_GetInstance($this->InstanceID)["InstanceID"];
			$address = "Das Gateway konnte keine Adresse empfangen.";
			IPS_LogMessage( "IT Adresse:" , $address );
			echo "Die Adresse vom IT Ger�t konnte nicht angelernt werden.";
			IPS_SetProperty($instance, "LearnITCode", false); //Haken entfernen.			
			}
		else
			{
			//Adresse auswerten {XC_SUC}
			//bei Erfolg {XC_SUC}{"CODE":"03"}
			//bei machen R�ckmeldung {XC_SUC}{"CODE":"010006"}	 //FC 01 = B DC 00 = 1 und an/aus
			$length = strlen($address);
			if ($length == 25)
				{
				(string)$address = substr($address, 17, 4);
				IPS_LogMessage( "IT Adresse:" , $address );
				// Anpassen der Daten
				$address = str_split($address);
				$ITDeviceCode = $address[2].$address[3]; //Devicecode 
				$ITFamilyCode = $address[0].$address[1]; // Familencode
				$hexsfc = array("00", "01", "02", "03", "04", "05", "06", "07", "08", "09");
				$itfc = array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J");
				$ITFamilyCode = str_replace($hexsfc, $itfc, $ITFamilyCode);
				$hexsdc = array("00", "01", "02", "03", "04", "05", "06", "07", "08", "09");
				$itdc = array("1", "2", "3", "4", "5", "6", "7", "8", "9", "10");
				$ITDeviceCode = str_replace($hexsdc, $itdc, $ITDeviceCode);
				}
			elseif ($length == 21)
				{
				(string)$address = substr($address, 17, 2);
				IPS_LogMessage( "IT Adresse:" , $address );
				// Anpassen der Daten
				$address = str_split($address);
				$ITDeviceCode = ($address[1])+1; //Devicecode auf Original umrechen +1
				$ITFamilyCode = $address[0]; // Zahlencode in Buchstaben Familencode umwandeln
				$hexsend = array("0", "1", "2", "3", "4", "5", "6", "7", "8", "9");
				$itfc = array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J");
				$ITFamilyCode = str_replace($hexsend, $itfc, $ITFamilyCode);
				}
			$this->AddAddress($ITFamilyCode, $ITDeviceCode);
			$this->response = true;	
			}
		
		return $this->response;
		}
	
	//IT Adresse hinzuf�gen
	protected function AddAddress($ITFamilyCode, $ITDeviceCode)
	{
		$instance = IPS_GetInstance($this->InstanceID)["InstanceID"];
		IPS_SetProperty($instance, "ITFamilyCode", $ITFamilyCode); //ITFamilyCode setzten.
		IPS_SetProperty($instance, "ITDeviceCode", $ITDeviceCode); //ITDeviceCode setzten.
		IPS_SetProperty($instance, "LearnITCode", false); //Haken entfernen.
		IPS_ApplyChanges($instance); //Neue Konfiguration �bernehmen
		IPS_LogMessage( "IT FamilyCode hinzugef�gt:" , $ITFamilyCode );
		IPS_LogMessage( "IT DeviceCode hinzugef�gt:" , $ITDeviceCode );
		// Status aktiv
		$this->SetStatus(102);
		$this->SetupVar();
		$this->SetupProfiles();	
	}

	
	/*
	public function Request($path) {
		$host = $this->ReadPropertyString('Host');
		if ($host == '') {
		  $this->SetStatus(104);
		  return false;
		}
		$client = curl_init();
		curl_setopt($client, CURLOPT_URL, "http://{$host}$path");
		curl_setopt($client, CURLOPT_POST, false);
		curl_setopt($client, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($client, CURLOPT_USERAGENT, "SymconAIO");
		curl_setopt($client, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($client, CURLOPT_TIMEOUT, 5);
		$result = curl_exec($client);
		$status = curl_getinfo($client, CURLINFO_HTTP_CODE);
		curl_close($client);
		if ($status == '0') {
		  $this->SetStatus(201);
		  return false;
		} elseif ($status != '200') {
		  $this->SetStatus(201);
		  return false;
		} else {
		  $this->SetStatus(102);
		  return simplexml_load_string($result);
		}
		}
		*/
	
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
		

}

?>
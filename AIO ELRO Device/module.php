<?

require_once(__DIR__ . "/../AIOGatewayClass.php");  // diverse Klassen

class AIOELRODevice extends IPSModule
{

   
    public function Create()
    {
        //Never delete this line!
        parent::Create();

        // 1. Verf�gbarer AIOSplitter wird verbunden oder neu erzeugt, wenn nicht vorhanden.
        $this->ConnectParent("{7E03C651-E5BF-4EC6-B1E8-397234992DB4}");
		
		$this->RegisterPropertyString("ELROAddress", "");
		$this->RegisterPropertyBoolean("LearnAddressELRO", false);
		
    }


    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();
		
		// ELROAddress pr�fen
        $ELROAddress = $this->ReadPropertyString('ELROAddress');
        $LearnAddressELRO = $this->ReadPropertyBoolean('LearnAddressELRO');
				
		if ($LearnAddressELRO)
		{
			$this->Learn();
		}
		elseif ( $ELROAddress == '')
        {
            // Status inaktiv
            $this->SetStatus(104);
        }
		else 
		{
			//Eingabe �berpr�fen
			
			
			// Status aktiv
            $this->SetStatus(102);
			//Status-Variablen anlegen
			$stateId = $this->RegisterVariableBoolean("STATE", "Status", "~Switch", 1);
			$this->EnableAction("STATE");
		}	
		
				
        // Profile anlegen

        
	}
	
	/**
    * Die folgenden Funktionen stehen automatisch zur Verf�gung, wenn das Modul �ber die "Module Control" eingef�gt wurden.
    * Die Funktionen werden, mit dem selbst eingerichteten Prefix, in PHP und JSON-RPC wiefolgt zur Verf�gung gestellt:
    *
    * ABC_MeineErsteEigeneFunktion($id);
    *
    */
		
	public function RequestAction($Ident, $Value)
		{
			switch($Ident) {
				case "STATE":
					$this->ELROPowerSetState($Value);
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
	
	public function PowerOn() {
            $ELROAddress = $this->ReadPropertyString("ELROAddress");
			return $this->Send_ELRO($ELROAddress, true, $this->GetIPGateway());
        }
		
	public function PowerOff() {
			$ELROAddress = $this->ReadPropertyString("ELROAddress");
			return $this->Send_ELRO($ELROAddress, false, $this->GetIPGateway());
        }
	
	//Berechnet den Sendecode aus Familien und Devicecode 
	// Umrechnung in Hexadezimal Code A entspricht 0, Device 1 entspricht 0
	
	protected function ELRO_calculate(){
		$ELROFamilyCode = $this->ReadPropertyString('ELROFamilyCode');
		$ELRODeviceCode = $this->ReadPropertyString('ELRODeviceCode');
		$ELROFamilyCode = ord(strtoupper($ELROFamilyCode)) - ord('A');
		$ELRODeviceCode = intval($ELRODeviceCode)-1;
		$ELRO_send = $ELROFamilyCode.$ELRODeviceCode;
		return $ELRO_send;
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
	
	protected function ELROPowerSetState ($state){
	SetValueBoolean($this->GetIDForIdent('STATE'), $state);
	return $this->SetPowerState($state);	
	}
	
	protected function SetPowerState($state) {
		$ELROAddress = $this->ReadPropertyString("ELROAddress");
		if ($state === true)
		{
		$action = "E";
		return $this->Send_ELRO($ELROAddress, $action);	
		}
		else
		{
		$action = "6";
		return $this->Send_ELRO($ELROAddress, $action);	
		}
	}
	
		
	//Senden eines Befehls an Elro
	protected function Send_ELRO($ELRO_send, $action)
		{
		$GatewayPassword = $this->GetPassword();	
		if ($action === "E")
			{
			// Sendestring ELRO /command?XC_FNC=SendSC&type=ELRO&data=
			//ELRO Befehl schaltet an letzte Stelle -5 ?
			if ($GatewayPassword !== "")
			{
				$gwcheck = file_get_contents("http://".$this->GetIPGateway()."/command?XC_USER=user&XC_PASS=".$GatewayPassword."&XC_FNC=SendSC&type=ELRO&data=".$ELRO_send."E");
			}
			else
			{
				$gwcheck = file_get_contents("http://".$this->GetIPGateway()."/command?XC_FNC=SendSC&type=ELRO&data=".$ELRO_send."E");
			}
			
			$status = true;
			return $status;
			}
		else
			{
			//ELRO Befehl schaltet aus letze Stelle -2 ?
			if ($GatewayPassword != "")
			{
				$gwcheck = file_get_contents("http://".$this->GetIPGateway()."/command?XC_USER=user&XC_PASS=".$GatewayPassword."&XC_FNC=SendSC&type=ELRO&data=".$ELRO_send."6");
			}
			else
			{
				$gwcheck = file_get_contents("http://".$this->GetIPGateway()."/command?XC_FNC=SendSC&type=ELRO&data=".$ELRO_send."6");
			}
			
			$status = false;
			return $status;
			}

		}
	
	private $response = false;
	//Anmelden eines ELRO Ger�ts an das a.i.o. gateway:
	//http://{IP-Adresse-des-Gateways}/command?XC_FNC=LearnSC&type=ELRO
	public function Learn()
		{
		$GatewayPassword = $this->GetPassword();	
		if ($GatewayPassword !== "")
			{
				$address = file_get_contents("http://".$this->GetIPGateway()."/command?XC_USER=user&XC_PASS=".$GatewayPassword."&XC_FNC=LearnSC&type=ELRO");
			}
			else
			{
				$address = file_get_contents("http://".$this->GetIPGateway()."/command?XC_FNC=LearnSC&type=ELRO");
			}
		//kurze Pause w�hrend das Gateway im Lernmodus ist
		IPS_Sleep(1000); //1000 ms
		if ($address == "{XC_ERR}Failed to learn code")//Bei Fehler
			{
			$this->response = false;
			$instance = IPS_GetInstance($this->InstanceID)["InstanceID"];
			$address = "Das Gateway konnte keine Adresse empfangen.";
			IPS_LogMessage( "ELRO Adresse:" , $address );
			echo "Die Adresse vom ELRO Ger�t konnte nicht angelernt werden.";
			IPS_SetProperty($instance, "LearnAddressELRO", false); //Haken entfernen.			
			}
		else
			{
				//Adresse auswerten {XC_SUC}
				//bei Erfolg {XC_SUC}{"CODE":"414551"} 
				(string)$address = substr($address, 17, 6);
				IPS_LogMessage( "ELRO Adresse:" , $address );
				//echo "Adresse des ELRO Ger�ts: ".$address;
				$this->AddAddress($address);
				$this->response = true;	
			}
		
		return $this->response;
		}
	
	//Adresse hinzuf�gen
	protected function AddAddress($address)
	{
		$instance = IPS_GetInstance($this->InstanceID)["InstanceID"];
		IPS_SetProperty($instance, "ELROAddress", $address); //Adresse setzten.
		IPS_SetProperty($instance, "LearnAddressELRO", false); //Haken entfernen.
		IPS_ApplyChanges($instance); //Neue Konfiguration �bernehmen
		IPS_LogMessage( "ELRO Adresse hinzugef�gt:" , $address );
		// Status aktiv
        $this->SetStatus(102);
		//Status-Variablen anlegen
		$stateId = $this->RegisterVariableBoolean("STATE", "Status", "~Switch", 1);
		$this->EnableAction("STATE");	
	}

}

?>
<?php

declare(strict_types=1);

include_once __DIR__ . '/helper/autoload.php';

class BB_Lichtsteuerung extends IPSModule
{
    use HelperSwitchDevice;
    use HelperDimDevice;
    use HelperStartScript;
    
    public function Create()
    {
        //Never delete this line!
        parent::Create();

        //Properties
        $this->RegisterPropertyString('InputTriggers', '[]');
        $this->RegisterPropertyString('OutputVariables', '[]');
        $this->RegisterPropertyString('ManualOnTriggers', '[]');
        $this->RegisterPropertyFloat('Duration', 1);
        $this->RegisterPropertyFloat('Duration2', 60);
        $this->RegisterPropertyBoolean('DisplayRemaining', false);
        $this->RegisterPropertyInteger('UpdateInterval', 10);
        $this->RegisterPropertyBoolean('ResendAction', false);
        $this->RegisterPropertyString('NightMode', 'off');
        $this->RegisterPropertyInteger('NightModeSource', 0);
        $this->RegisterPropertyBoolean('NightModeInverted', false);
        $this->RegisterPropertyInteger('NightModeValue', 30);
        $this->RegisterPropertyInteger('DayModeValue', 100);
        $this->RegisterPropertyInteger('NightModeSourceInteger', 0);
        $this->RegisterPropertyInteger('AmbientBrightnessThreshold', 0);
    
        //Register Output Script
        $this->RegisterPropertyInteger('OutScriptID', 0);

        //Registering legacy properties to transfer the data
        $this->RegisterPropertyInteger('InputTriggerID', 0);
        $this->RegisterPropertyInteger('ManualOnTriggerID', 0);
        $this->RegisterPropertyInteger('OutputID', 0);

        //Timers
        $this->RegisterTimer('OffTimer', 0, "BBL_Stop(\$_IPS['TARGET']);");
        $this->RegisterTimer('UpdateRemainingTimer', 0, "BBL_UpdateRemaining(\$_IPS['TARGET']);");

        //Variables
        $this->RegisterVariableBoolean('Active', 'Treppenhauslichtsteuerung aktiv', '~Switch');
        $this->RegisterVariableBoolean('ManualOn', 'Dauerlicht', '~Switch');
        $this->RegisterVariableBoolean('Status', 'Licht Status', '~Switch');
        $this->EnableAction('Active');
        $this->EnableAction('ManualOn');
        $this->EnableAction('Status');

        //Attributes
    }
    /****************************************************************************** */
    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();
      

        //Register variable if enabled
        $this->MaintainVariable('Remaining', $this->Translate('Remaining time'), VARIABLETYPE_STRING, '', 10, $this->ReadPropertyBoolean('DisplayRemaining'));
    
        //Delete all references in order to readd them
        foreach ($this->GetReferenceList() as $referenceID) {
            $this->UnregisterReference($referenceID);
        }

        //Delete all registrations in order to readd them
        foreach ($this->GetMessageList() as $senderID => $messages) {
            foreach ($messages as $message) {
                $this->UnregisterMessage($senderID, $message);
            }
        }

        //Register update messages and references
        $inputTriggers = json_decode($this->ReadPropertyString('InputTriggers'), true);
        foreach ($inputTriggers as $inputTrigger) {
            $triggerID = $inputTrigger['VariableID'];
            $this->RegisterMessage($triggerID, VM_UPDATE);
            $this->RegisterReference($triggerID);
        }
        
        //Register ManualOn messages and references
        $ManualOnTriggers = json_decode($this->ReadPropertyString('ManualOnTriggers'), true);
        foreach ($ManualOnTriggers as $ManualOnTrigger) {
            $ManualOntriggerID = $ManualOnTrigger['VariableID'];
            $this->RegisterMessage($ManualOntriggerID, VM_UPDATE);
            $this->RegisterReference($ManualOntriggerID);
        }

        $outputVariables = json_decode($this->ReadPropertyString('OutputVariables'), true);
        foreach ($outputVariables as $outputVariable) {
            $outputID = $outputVariable['VariableID'];
            $this->RegisterReference($outputID);
        }
    }
    /****************************************************************************** */
    public function GetConfigurationForm()
    {
        //Add options to form
        $jsonForm = json_decode(file_get_contents(__DIR__ . '/form.json'), true);

        //Set status column for inputs
        $inputTriggers = json_decode($this->ReadPropertyString('InputTriggers'), true);
        foreach ($inputTriggers as $inputTrigger) {
            $jsonForm['elements'][0]['values'][] = [
                'Status' => $this->GetTriggerStatus($inputTrigger['VariableID'])
            ];
        }

        //Set status column for ManualOn
        $ManualOnTriggers = json_decode($this->ReadPropertyString('ManualOnTriggers'), true);
        foreach ($ManualOnTriggers as $ManualOnTrigger) {
            $jsonForm['elements'][2]['values'][] = [
                'Status' => $this->GetTriggerStatus($ManualOnTrigger['VariableID'])
            ];
        }

        //Set status column for outputs
        $outputVariables = json_decode($this->ReadPropertyString('OutputVariables'), true);
        foreach ($outputVariables as $outputVariable) {
            $jsonForm['elements'][1]['values'][] = [
                'Status' => $this->GetOutputStatus($outputVariable['VariableID'])
            ];
        }

        $nightMode = $this->ReadPropertyString('NightMode');
        $boolVisible = $nightMode == 'boolean';
        $jsonForm['elements'][3]['items'][1]['visible'] = $boolVisible;
        $jsonForm['elements'][3]['items'][2]['visible'] = $boolVisible;
        $jsonForm['elements'][3]['items'][3]['visible'] = $boolVisible;
        $jsonForm['elements'][3]['items'][4]['visible'] = $boolVisible;

        $intVisible = $nightMode == 'integer';
        $jsonForm['elements'][3]['items'][5]['visible'] = $intVisible;
        $jsonForm['elements'][3]['items'][6]['visible'] = $intVisible;
        $jsonForm['elements'][3]['items'][7]['visible'] = $intVisible;
        $jsonForm['elements'][3]['items'][8]['visible'] = $intVisible;

        $brightnessVisible = in_array($nightMode, ['boolean', 'integer']);
        $jsonForm['elements'][3]['items'][9]['visible'] = $brightnessVisible;
        $jsonForm['elements'][3]['items'][10]['visible'] = $brightnessVisible;

        //Set visibility of remaining time options
        $jsonForm['elements'][7]['visible'] = $this->ReadPropertyBoolean('DisplayRemaining');

        return json_encode($jsonForm);
    }

    /****************************************************************************** */
    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {
        
        #IPS_LogMessage("MessageSink", "Message from SenderID ".$SenderID." with Message ".$Message."\r\n Data: ".print_r($Data, true));
      
        if ($Message == VM_UPDATE) {
            $StepOut = false;
            $ManualOnTriggers = json_decode($this->ReadPropertyString('ManualOnTriggers'), true);
            foreach ($ManualOnTriggers as $ManualOnTrigger) {
                if ($SenderID == $ManualOnTrigger['VariableID']) {
                    if ($ManualOnTrigger['SensorType'] == 0) {
                        $this->RequestAction('ManualOn', GetValue($SenderID));
                    }
                    elseif ($ManualOnTrigger['SensorType'] == 1) {
                        $this->RequestAction('ManualOn', !$this->GetValue('ManualOn'));
                    }
                    else {
                        throw new Exception('Invalid Switch Type selected');
                    }


                    $this->SendDebug("Message", "Manual-ON trigger reveived from: ". IPS_GetName(IPS_GetParent($SenderID)) . " (ID:" .$SenderID.")", 0);
                    $StepOut = true;
                }
            }
            if ($StepOut == false) {
                $getProfileName = function ($variableID) {
                    $variable = IPS_GetVariable($variableID);
                    if ($variable['VariableCustomProfile'] != '') {
                        return $variable['VariableCustomProfile'];
                    } else {
                        return $variable['VariableProfile'];
                    }
                };

                $isProfileReversed = function ($VariableID) use ($getProfileName) {
                    return preg_match('/\.Reversed$/', $getProfileName($VariableID));
                };

                if (boolval($Data[0]) ^ $isProfileReversed($SenderID)) {
                    $this->SendDebug("Message", "ON trigger received from: ".IPS_GetName(IPS_GetParent($SenderID)). " (ID:" .$SenderID.")", 0);
                    $this->Start();
                }
            }
        }
    }

    /****************************************************************************** */
    public function RequestAction($Ident, $Value)
    {
        switch ($Ident) {
            case 'Active':
                $this->SetActive($Value);
                $this->SendDebug("Module-Status set to: ", $Value, 0);
                break;
        
            case 'Status':
                $this->SetValue('Status', $Value);
                $this->SendDebug("Light-Status set to:", $Value, 0);
                if ($Value == true) {
                    $this->Start();
                } else {
                    $this->Stop();
                }
                break;
            
            case "ManualOn":
                $this->SetValue('ManualOn', $Value);
                $this->SendDebug("ManualOn-Status set to: ", $Value, 0);
                if ($Value == true) {
                    $this->Start();
                } else {
                    $this->Stop();
                }
                break;
            default:
                throw new Exception('Invalid ident');
        }
    }

    /****************************************************************************** */
    public function ToggleDisplayInterval(bool $visible)
    {
        $this->UpdateFormField('UpdateInterval', 'visible', $visible);
    }

    /****************************************************************************** */
    public function SetActive(bool $Value)
    {
        $this->SetValue('Active', $Value);
    }

    /****************************************************************************** */
    public function Start()
    {
        if (!$this->GetValue('Active')) {
            return;
        }

        $this->SwitchVariable(true);
        self::startScript($this->ReadPropertyInteger('OutScriptID'), true);

        //Start OffTimer
        
        if ($this->GetValue('ManualOn') == false) {
            $duration = $this->ReadPropertyFloat('Duration');
            $this->SetTimerInterval('OffTimer', round($duration * 60 * 1000, 0));
        } else {
            $duration = $this->ReadPropertyFloat('Duration2');
            $this->SetTimerInterval('OffTimer', round($duration * 60 * 1000, ));
        }
        $this->SendDebug("Timer interval set to:", $duration . " minutes", 0);
        
        //Update display variable periodically if enabled
        if ($this->ReadPropertyBoolean('DisplayRemaining')) {
            $this->SetTimerInterval('UpdateRemainingTimer', 1000 * $this->ReadPropertyInteger('UpdateInterval'));
            $this->UpdateRemaining();
        }
    }
    /****************************************************************************** */
    public function Stop()
    {
        $this->SwitchVariable(false);
        self::startScript($this->ReadPropertyInteger('OutScriptID'), false);

        $this->SetValue('ManualOn', false);

        //Disable OffTimer
        $this->SetTimerInterval('OffTimer', 0);

        //Disable updating of display variable
        if ($this->ReadPropertyBoolean('DisplayRemaining')) {
            $this->SetTimerInterval('UpdateRemainingTimer', 0);
            $this->SetValue('Remaining', '00:00:00');
        }
    }
    /****************************************************************************** */
    public function UpdateRemaining()
    {
        $secondsRemaining = 0;
        foreach (IPS_GetTimerList() as $timerID) {
            $timer = IPS_GetTimer($timerID);
            if (($timer['InstanceID'] == $this->InstanceID) && ($timer['Name'] == 'OffTimer')) {
                $secondsRemaining = $timer['NextRun'] - time();
                break;
            }
        }

        //Display remaining time as string
        $this->SetValue('Remaining', sprintf('%02d:%02d:%02d', ($secondsRemaining / 3600), ($secondsRemaining / 60 % 60), $secondsRemaining % 60));
    }
    /****************************************************************************** */
    public function SetNightMode(string $NightMode)
    {
        $boolVisible = $NightMode == 'boolean';
        $this->UpdateFormField('LabelNightModeSource', 'visible', $boolVisible);
        $this->UpdateFormField('NightModeSource', 'visible', $boolVisible);
        $this->UpdateFormField('LabelNightModeSourceInverted', 'visible', $boolVisible);
        $this->UpdateFormField('NightModeInverted', 'visible', $boolVisible);

        $intVisible = $NightMode == 'integer';
        $this->UpdateFormField('LabelNightModeSourceInteger', 'visible', $intVisible);
        $this->UpdateFormField('NightModeSourceInteger', 'visible', $intVisible);
        $this->UpdateFormField('LabelNightModeSourceIntegerThreshold', 'visible', $intVisible);
        $this->UpdateFormField('AmbientBrightnessThreshold', 'visible', $intVisible);

        $brightnessVisible = in_array($NightMode, ['boolean', 'integer']);
        $this->UpdateFormField('NightModeValue', 'visible', $brightnessVisible);
        $this->UpdateFormField('DayModeValue', 'visible', $brightnessVisible);
    }
    /****************************************************************************** */
 
    private function GetTriggerStatus($triggerID)
    {
        if (!IPS_VariableExists($triggerID)) {
            return 'Missing';
        } elseif (IPS_GetVariable($triggerID)['VariableType'] == VARIABLETYPE_STRING) {
            return 'Bool/Int/Float required';
        } else {
            return 'OK';
        }
    }
    /****************************************************************************** */
    private function GetOutputStatus($outputID)
    {
        if (!IPS_VariableExists($outputID)) {
            return 'Missing';
        } else {
            switch (IPS_GetVariable($outputID)['VariableType']) {
                case VARIABLETYPE_BOOLEAN:
                    return self::getSwitchCompatibility($outputID);
                case VARIABLETYPE_INTEGER:
                case VARIABLETYPE_FLOAT:
                    return self::getDimCompatibility($outputID);
                default:
                    return 'Bool/Int/Float required';
            }
        }
    }
    /****************************************************************************** */
    private function SwitchVariable(bool $Value)
    {
        $isTrigger = function (int $outputID) {
            $inputTriggers = json_decode($this->ReadPropertyString('InputTriggers'), true);
            foreach ($inputTriggers as $variable) {
                if ($variable['VariableID'] == $outputID) {
                    return true;
                }
            }
            return false;
        };
        $this->SetValue('Status', $Value);
        $outputVariables = json_decode($this->ReadPropertyString('OutputVariables'), true);
        foreach ($outputVariables as $outputVariable) {
            $outputID = $outputVariable['VariableID'];

            $doResend = $this->ReadPropertyBoolean('ResendAction');

            //Prevent endless loops and do not allow resends if outputID is also a trigger
            if ($doResend) {
                if ($isTrigger($outputID)) {
                    $doResend = false;
                }
            }

            //Depending on the type we need to switch differently
            switch (IPS_GetVariable($outputID)['VariableType']) {
                case VARIABLETYPE_BOOLEAN:
                    if ($doResend || (self::getSwitchValue($outputID) != $Value)) {
                        if ($Value== true) {
                            if ($this->GetValue('ManualOn') == true) {
                                self::switchDevice($outputID, true);
                            } else {
                                if ($this->ReadPropertyString('NightMode') == 'off') {     // everything normal if NighMode is OFF
                                    self::switchDevice($outputID, true);
                                } else {
                                    if ($this->ReadPropertyInteger('DayModeValue') > 0) {   // Switch only if DayValue is not 0
                                        self::switchDevice($outputID, true);
                                    }
                                }
                            }
                        } else {
                            self::switchDevice($outputID, false);
                        }
                    }
                    $this->SendDebug("Variable " .$outputID ." ". IPS_GetName(IPS_GetParent($outputID)). "/" .IPS_GetName($outputID) . " set to:", self::getSwitchValue($outputID), 0);
                break;

                case VARIABLETYPE_INTEGER:
                
                case VARIABLETYPE_FLOAT:
                    $dimDevice = function ($Value) use ($outputID, $doResend) {
                        if ($doResend || (self::getDimValue($outputID) != $Value)) {
                            self::dimDevice($outputID, $Value);
                            $this->SendDebug("Variable " .$outputID ." ". IPS_GetName(IPS_GetParent($outputID)). "/" .IPS_GetName($outputID) . " set to:", $Value, 0);
                        }
                    };

                    if ($Value) {
                        //We might need to set a different value if night-mode is in use
                        switch ($this->ReadPropertyString('NightMode')) {
                            case 'boolean':
                                  if (IPS_VariableExists($this->ReadPropertyInteger('NightModeSource'))
                                     && (GetValue($this->ReadPropertyInteger('NightModeSource')) ^ $this->ReadPropertyBoolean('NightModeInverted'))) {
                                      $dimDevice($this->ReadPropertyInteger('NightModeValue'));
                                  } else {
                                      $dimDevice($this->ReadPropertyInteger('DayModeValue'));
                                  }
                                break;

                            case 'integer':
                                    if (IPS_VariableExists($this->ReadPropertyInteger('NightModeSourceInteger'))
                                    && (GetValue($this->ReadPropertyInteger('NightModeSourceInteger')) < $this->ReadPropertyInteger('AmbientBrightnessThreshold'))) {
                                        $dimDevice($this->ReadPropertyInteger('NightModeValue'));
                                    } else {
                                        $dimDevice($this->ReadPropertyInteger('DayModeValue'));
                                    }
                                break;

                            case 'off':
                                $dimDevice(100);
                                break;

                            default:
                                //Unsupported. Do nothing
                                break;
                        }
                    } else {
                        $dimDevice(0);
                    }
                    break;
                    $this->SendDebug("Variable " .$outputID ." ". IPS_GetName(IPS_GetParent($outputID)). "/" .IPS_GetName($outputID) . " set to:", self::getSwitchValue($outputID), 0);
                   
                default:
                    //Unsupported. Do nothing
            }
        }
    }
}

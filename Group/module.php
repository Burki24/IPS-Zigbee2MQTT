<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/libs/ModulBase.php';

class Zigbee2MQTTGroup extends \Zigbee2MQTT\ModulBase
{
    /** @var mixed $ExtensionTopic Topic für den ReceiveFilter */
    protected static $ExtensionTopic = 'getGroupInfo/';

    /**
     * Create
     *
     * @return void
     */
    public function Create()
    {
        // Never delete this line!
        parent::Create();
        $this->RegisterPropertyInteger('GroupId', 0);
    }

    /**
     * ApplyChanges
     *
     * @return void
     */
    public function ApplyChanges()
    {
        $GroupId = $this->ReadPropertyInteger('GroupId');
        $GroupId = $GroupId ? 'Group Id: ' . $GroupId : '';
        $this->SetSummary($GroupId);
        //Never delete this line!
        parent::ApplyChanges();
    }

    /**
     * UpdateDeviceInfo
     *
     * Exposes von der Erweiterung in Z2M anfordern und verarbeiten.
     *
     * @return bool
     */
    protected function UpdateDeviceInfo(): bool
    {
        $mqttTopic = $this->ReadPropertyString('MQTTTopic');
        if (empty($mqttTopic)) {
            $this->LogMessage(__CLASS__ . "MQTTTopic ist nicht gesetzt.", KL_ERROR);
            return false;
        }

        $Result = $this->SendData('/SymconExtension/request/getGroupInfo/' . $mqttTopic);
        $this->SendDebug(__FUNCTION__ . ' :: ' . __LINE__ . ' result', json_encode($Result), 0);

        if ($Result === false) {
            $this->LogMessage(__CLASS__ . "SendData für MQTTTopic '$mqttTopic' fehlgeschlagen.", KL_ERROR);
            return false;
        }

        if (array_key_exists('foundGroup', $Result)) {
            unset($Result['foundGroup']);
            // Aufruf der Methode aus der ModulBase-Klasse
            $this->mapExposesToVariables($Result);

            // Aggregiere alle 'features' in ein einheitliches 'exposes' Array
            $exposes = [];
            foreach ($Result as $type => $data) {
                if (isset($data['features']) && is_array($data['features'])) {
                    foreach ($data['features'] as $feature) {
                        $exposes[] = $feature;
                    }
                }
            }

            $dataToSave = [
                'GroupId' => $this->ReadPropertyInteger('GroupId'),
                'exposes' => $exposes
            ];
            $this->SaveExposesToJson($dataToSave, 'group');
            return true;
        }

        trigger_error($this->Translate('Group not found. Check topic'), E_USER_NOTICE);
        return false;
    }
}

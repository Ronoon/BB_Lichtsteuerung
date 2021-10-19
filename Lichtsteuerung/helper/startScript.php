<?php

declare(strict_types=1);

trait HelperStartScript
{
    private static function getScriptCompatibility($scriptID)
    {
        if (!IPS_ScriptExists($scriptID)) {
            return 'Missing';
        }

        return 'OK';
    }

    private static function startScript($scriptID, $Status = true)
    {
        if (!IPS_ScriptExists($scriptID)) {
            return false;
        }

        return IPS_RunScriptEx($scriptID, ['VALUE' => $Status, 'SENDER' => 'BB_Lichtsteuerung']);
    }
}

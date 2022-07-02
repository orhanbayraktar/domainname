<?php

// BASE CONNECTION (ABSTRACT)
abstract class APIConnection
{

    abstract public function CheckAvailability($parameters);

    abstract public function ModifyNameServer($parameters);
    
	abstract public function GetList($parameters);
    abstract public function GetDetails($parameters);
    
    abstract public function EnableTheftProtectionLock($parameters);
    abstract public function DisableTheftProtectionLock($parameters);
    
    abstract public function ModifyPrivacyProtectionStatus($parameters);
	
    abstract public function AddChildNameServer($parameters);
    abstract public function DeleteChildNameServer($parameters);
    abstract public function ModifyChildNameServer($parameters);
    
    abstract public function GetContacts($parameters);
    abstract public function SaveContacts($parameters);

    abstract public function Transfer($parameters);
    abstract public function CancelTransfer($parameters);

    abstract public function RegisterWithContactInfo($parameters);
    abstract public function Renew($parameters);
    abstract public function Delete($parameters);
    
	abstract public function SyncFromRegistry($parameters);
	abstract public function CheckTransfer($parameters);
}
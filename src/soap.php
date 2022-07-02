<?php
namespace domainapi;

// SOAP CONNECTION
class APIConnection_SOAP extends APIConnection
{
    // VARIABLES
    private $service = null;
    private $URL_SERVICE = "";

    // CONSTRUCTORS
    function __construct($Service_URL) 
    {
        // Set settings
        $this->URL_SERVICE = $Service_URL;

        // Set WSDL caching enabled     
        ini_set('soap.wsdl_cache_enabled', '1'); ini_set('soap.wsdl_cache_ttl', '86400'); 

        // Create unique connection
        $this->service = new SoapClient($this->URL_SERVICE . "?singlewsdl", array("cache_wsdl" => WSDL_CACHE_MEMORY,  "encoding"=>"UTF-8")); 
    }
    function APIConnection_SOAP($Service_URL) 
    {
        // Set settings
        $this->URL_SERVICE = $Service_URL;
        
        // Set WSDL caching enabled
        ini_set('soap.wsdl_cache_enabled', '1');  ini_set('soap.wsdl_cache_ttl', '86400'); 

        // Create unique connection
        $this->service = new SoapClient($this->URL_SERVICE . "?singlewsdl", array("cache_wsdl" => WSDL_CACHE_MEMORY,  "encoding"=>"UTF-8")); 
    }
    
    // Convert object to array
    private function objectToArray($o) 
    {
        try { $o = json_decode(json_encode($o), true); } catch(Exception $ex) { }
        return $o;
    }
    
    // Get error if exists
    private function parseError($response)
    {
        $result = false;

        if(is_null($response))
        {
            // Set error data
            $result = array();
            $result["Code"] = "INVALID_RESPONSE";
            $result["Message"] = "Invalid response or no response received from server!";
            $result["Details"] = "SOAP Connection returned null value!";
        }
        elseif(!is_array($response))
        {
            // Set error data
            $result = array();
            $result["Code"] = "INVALID_RESPONSE";
            $result["Message"] = "Invalid response or no response received from server!";
            $result["Details"] = "SOAP Connection returned non-array value!";
        }
        elseif(strtolower(key($response)) == "faultstring")
        {
            // Handle soap fault
            
            $result = array();
            $result["Code"] = "";
            $result["Message"] = "";
            $result["Details"] = "";
            
            // Set error data
            if(isset($response["faultcode"])) { $result["Code"] = $response["faultcode"]; }
            if(isset($response["faultstring"])) { $result["Message"] = $response["faultstring"]; }
            if(isset($response["detail"])) {
                if(is_array($response["detail"])) {
                    if(isset($response["detail"]["ExceptionDetail"])) {
                        if(is_array($response["detail"]["ExceptionDetail"])) {
                            if(isset($response["detail"]["ExceptionDetail"]["StackTrace"]))
                            { $result["Details"] = $response["detail"]["ExceptionDetail"]["StackTrace"]; }
                        }
                    }
                
                }
            }
            
        }
        elseif(count($response) != 1)
        {
            // Set error data
            $result = array();
            $result["Code"] = "INVALID_RESPONSE";
            $result["Message"] = "Invalid response or no response received from server!";
            $result["Details"] = "Response data contains more than one result! Only one result accepted!";
        }
        elseif(!isset($response[key($response)]["OperationResult"]) || !isset($response[key($response)]["ErrorCode"]))
        {
            // Set error data
            $result = array();
            $result["Code"] = "INVALID_RESPONSE";
            $result["Message"] = "Invalid response or no response received from server!";
            $result["Details"] = "Operation result or Error code not received from server!";
        }
        elseif(strtoupper($response[key($response)]["OperationResult"]) != "SUCCESS")
        {
            // Set error data
            $result = array();
            $result["Code"] = "";
            $result["Message"] = "";
            $result["Details"] = "";
            
            $result["Message"] = "Operation can not completed successfully!";

            if(isset($response[key($response)]["OperationMessage"]))
            { $result["Code"] = "API_" . $response[key($response)]["ErrorCode"]; }

            if(isset($response[key($response)]["OperationResult"]))
            { $result["Code"] .= "_" . $response[key($response)]["OperationResult"]; }

            if(isset($response[key($response)]["OperationMessage"]))
            { $result["Details"] = $response[key($response)]["OperationMessage"]; }
            
        }
        else
        {
            
        }

        return $result;
    }
    
    // Check if response contains error
    private function hasError($response)
    { return ($this->parseError($response) === false) ? false : true; }
    
    // Set error message
    private function setError($Code, $Message, $Details)
    {
        $result = array();
        $result["Code"] = $Code;
        $result["Message"] = $Message;
        $result["Details"] = $Details;
        return $result;
    }
    
    // Parse domain info
    private function parseDomainInfo($data)
    {
        $result = array();
        $result["ID"] = "";
        $result["Status"] = "";
        $result["DomainName"] = "";
        $result["AuthCode"] = "";
        $result["LockStatus"] = "";
        $result["PrivacyProtectionStatus"] = "";
        $result["IsChildNameServer"] = "";
        $result["Contacts"] = array();
        $result["Contacts"]["Billing"] = array();
        $result["Contacts"]["Technical"] = array();
        $result["Contacts"]["Administrative"] = array();
        $result["Contacts"]["Registrant"] = array();
        $result["Contacts"]["Billing"]["ID"] = "";
        $result["Contacts"]["Technical"]["ID"] = "";
        $result["Contacts"]["Administrative"]["ID"] = "";
        $result["Contacts"]["Registrant"]["ID"] = "";
        $result["Dates"] = array();
        $result["Dates"]["Start"] = "";
        $result["Dates"]["Expiration"] = "";
        $result["Dates"]["RemainingDays"] = "";
        $result["NameServers"] = array();
        $result["Additional"] = array();
        $result["ChildNameServers"] = array();
        
        foreach($data as $attrName => $attrValue)
        {
            switch($attrName)
            {
                case "Id":
                {
                    if(is_numeric($attrValue)) { $result["ID"] = $attrValue; }
                    break;
                }
                
                case "Status":
                { $result["Status"] = $attrValue; break; }
                    
                case "DomainName":
                { $result["DomainName"] = $attrValue; break; }
                
                case "AdministrativeContactId":
                { 
                    if(is_numeric($attrValue)) { $result["Contacts"]["Administrative"]["ID"] = $attrValue;  }
                    break; 
                }
                
                case "BillingContactId":
                { 
                    if(is_numeric($attrValue)) { $result["Contacts"]["Billing"]["ID"] = $attrValue;  }
                    break; 
                }
                
                case "TechnicalContactId":
                { 
                    if(is_numeric($attrValue)) { $result["Contacts"]["Technical"]["ID"] = $attrValue;  }
                    break; 
                }
                
                case "RegistrantContactId":
                { 
                    if(is_numeric($attrValue)) { $result["Contacts"]["Registrant"]["ID"] = $attrValue;  }
                    break; 
                }
                
                case "Auth":
                {
                    if(is_string($attrValue) && !is_null($attrValue))
                    { $result["AuthCode"] = $attrValue; }
                    break;
                }
                
                case "StartDate":
                { $result["Dates"]["Start"] = $attrValue; break; }
                
                case "ExpirationDate":
                { $result["Dates"]["Expiration"] = $attrValue; break; }
                
                case "LockStatus":
                { 
                    if(is_bool($attrValue))
                    { $result["LockStatus"] = var_export($attrValue, true); }
                    break; 
                }
                
                case "PrivacyProtectionStatus":
                { 
                    if(is_bool($attrValue))
                    { $result["PrivacyProtectionStatus"] = var_export($attrValue, true); }
                    break; 
                }
                
                case "IsChildNameServer":
                { 
                    if(is_bool($attrValue))
                    { $result["IsChildNameServer"] = var_export($attrValue, true); }
                    break; 
                }
                
                case "RemainingDay":
                { 
                    if(is_numeric($attrValue))
                    { $result["Dates"]["RemainingDays"] = $attrValue; }
                    break; 
                }
                
                case "NameServerList":
                { 
                    if(is_array($attrValue))
                    {
                        foreach($attrValue as $nameserverValue)
                        {
                            array_push($result["NameServers"], $nameserverValue);
                        }
                    }
                    break; 
                }
                
                case "AdditionalAttributes":
                { 
                    if(is_array($attrValue))
                    {

                        if(isset($attrValue["KeyValueOfstringstring"]))
                        {
                            foreach($attrValue["KeyValueOfstringstring"] as $attribute)
                            {
                                if(isset($attribute["Key"]) && isset($attribute["Value"]))
                                {
                                    $result["Additional"][$attribute["Key"]] = $attribute["Value"];
                                }
                            }
                        }
                    }
                    break; 
                }
                
                case "ChildNameServerInfo":
                {

                    if(is_array($attrValue))
                    {
					
						if(isset($attrValue["ChildNameServerInfo"]["IpAddress"]))
						{
							$attribute = $attrValue["ChildNameServerInfo"];
						
							$ns = "";
							$IpAddresses = array();

							// Name of NameServer
							if(!is_null($attribute["NameServer"]) && is_string($attribute["NameServer"]))
							{ $ns = $attribute["NameServer"]; }
							 
							// IP adresses of NameServer
							if(is_array($attribute["IpAddress"]) && isset($attribute["IpAddress"]["string"]))
							{
									
								if(is_array($attribute["IpAddress"]["string"]))
								{
									
									foreach($attribute["IpAddress"]["string"] as $ip)
									{
										if(isset($ip) && !is_null($ip) && is_string($ip))
										{
											array_push($IpAddresses, $ip);
										}
									}
								
								}
								elseif(is_string($attribute["IpAddress"]["string"]))
								{
									array_push($IpAddresses, $attribute["IpAddress"]["string"]);
								}
								
							}
							
							array_push($result["ChildNameServers"], 
								array(
									"NameServer" => $ns,
									"IPAddresses" => $IpAddresses
								)
							);
							

						}
						else
						{
							if(count($attrValue["ChildNameServerInfo"])>0)
							{
								foreach($attrValue["ChildNameServerInfo"] as $attribute)
								{
		
									if(isset($attribute["NameServer"]) && isset($attribute["IpAddress"]))
									{
										$ns = "";
										$IpAddresses = array();
		
										// Name of NameServer
										if(!is_null($attribute["NameServer"]) && is_string($attribute["NameServer"]))
										{ $ns = $attribute["NameServer"]; }
										 
										// IP adresses of NameServer
										if(is_array($attribute["IpAddress"]) && isset($attribute["IpAddress"]["string"]))
										{
												
											if(is_array($attribute["IpAddress"]["string"]))
											{
												
												foreach($attribute["IpAddress"]["string"] as $ip)
												{
													if(isset($ip) && !is_null($ip) && is_string($ip))
													{
														array_push($IpAddresses, $ip);
													}
												}
											
											}
											elseif(is_string($attribute["IpAddress"]["string"]))
											{
												array_push($IpAddresses, $attribute["IpAddress"]["string"]);
											}
											
										}
										
										array_push($result["ChildNameServers"], 
											array(
												"NameServer" => $ns,
												"IPAddresses" => $IpAddresses
											)
										);
										
										
										
									}
									
								}	
								
							}					
						}

                        
                    }
                    break;
                }
            }

        }

        return $result;
    }
    
    
    

    // Parse Contact info
    private function parseContactInfo($data)
    {
        $result = array();
        $result["ID"] = "";
        $result["Status"] = "";
        $result["Additional"] = array();
        $result["Address"] = array();
        $result["Address"]["Line1"] = "";
        $result["Address"]["Line2"] = "";
        $result["Address"]["Line3"] = "";
        $result["Address"]["State"] = "";
        $result["Address"]["City"] = "";
        $result["Address"]["Country"] = "";
        $result["Address"]["ZipCode"] = "";
        $result["Phone"] = array();
        $result["Phone"]["Phone"] = array();
        $result["Phone"]["Phone"]["Number"] = "";
        $result["Phone"]["Phone"]["CountryCode"] = "";
        $result["Phone"]["Fax"]["Number"] = "";
        $result["Phone"]["Fax"]["CountryCode"] = "";
        $result["AuthCode"] = "";
        $result["FirstName"] = "";
        $result["LastName"] = "";
        $result["Company"] = "";
        $result["EMail"] = "";
        $result["Type"] = "";
        
        foreach($data as $attrName => $attrValue)
        {
            switch($attrName)
            {
                case "Id":
                {
                    if(is_numeric($attrValue)) { $result["ID"] = $attrValue; }
                    break;
                }
                
                case "Status":
                { $result["Status"] = $attrValue; break; }

                case "AdditionalAttributes":
                { 
                    if(is_array($attrValue))
                    {

                        if(isset($attrValue["KeyValueOfstringstring"]))
                        {
                            foreach($attrValue["KeyValueOfstringstring"] as $attribute)
                            {
                                if(isset($attribute["Key"]) && isset($attribute["Value"]))
                                {
                                    $result["Additional"][$attribute["Key"]] = $attribute["Value"];
                                }
                            }
                        }
                    }
                    break; 
                }

                case "AddressLine1":
                { 
                    if(is_string($attrValue) && !is_null($attrValue)) { $result["Address"]["Line1"] = $attrValue;  }
                    break; 
                }
                
                case "AddressLine2":
                { 
                    if(is_string($attrValue) && !is_null($attrValue)) { $result["Address"]["Line2"] = $attrValue;  }
                    break; 
                }
                
                case "AddressLine3":
                { 
                    if(is_string($attrValue) && !is_null($attrValue)) { $result["Address"]["Line3"] = $attrValue;  }
                    break; 
                }
                
                case "Auth":
                {
                    if(is_string($attrValue) && !is_null($attrValue))
                    { $result["AuthCode"] = $attrValue; }
                    break;
                }
                
                case "City":
                { 
                    if(is_string($attrValue) && !is_null($attrValue)) { $result["Address"]["City"] = $attrValue;  }
                    break; 
                }
                
                case "Company":
                { 
                    if(is_string($attrValue) && !is_null($attrValue)) { $result["Company"] = $attrValue;  }
                    break; 
                }
                
                case "Country":
                { 
                    if(is_string($attrValue) && !is_null($attrValue)) { $result["Address"]["Country"] = $attrValue;  }
                    break; 
                }
                
                case "EMail":
                { 
                    if(is_string($attrValue) && !is_null($attrValue)) { $result["EMail"] = $attrValue;  }
                    break; 
                }
                
                case "Fax":
                { 
                    if(is_string($attrValue) && !is_null($attrValue)) { $result["Phone"]["Fax"]["Number"] = $attrValue;  }
                    break; 
                }
                
                case "FaxCountryCode":
                { 
                    if(is_string($attrValue) && !is_null($attrValue)) { $result["Phone"]["Fax"]["CountryCode"] = $attrValue;  }
                    break; 
                }
                
                case "Phone":
                { 
                    if(is_string($attrValue) && !is_null($attrValue)) { $result["Phone"]["Phone"]["Number"] = $attrValue;  }
                    break; 
                }
                
                case "PhoneCountryCode":
                { 
                    if(is_string($attrValue) && !is_null($attrValue)) { $result["Phone"]["Phone"]["CountryCode"] = $attrValue;  }
                    break; 
                }
                
                case "FirstName":
                { 
                    if(is_string($attrValue) && !is_null($attrValue)) { $result["FirstName"] = $attrValue;  }
                    break; 
                }
                
                case "LastName":
                { 
                    if(is_string($attrValue) && !is_null($attrValue)) { $result["LastName"] = $attrValue;  }
                    break; 
                }
                
                case "State":
                { 
                    if(is_string($attrValue) && !is_null($attrValue)) { $result["Address"]["State"] = $attrValue;  }
                    break; 
                }
                
                case "ZipCode":
                { 
                    if(is_string($attrValue) && !is_null($attrValue)) { $result["Address"]["ZipCode"] = $attrValue;  }
                    break; 
                }
                
                case "Type":
                { 
                    if(is_string($attrValue) && !is_null($attrValue)) { $result["Type"] = $attrValue;  }
                    break; 
                }
                
            }

        }

        return $result;
    }
    
    // API METHODs
    
    // Check domain is available?
    public function CheckAvailability($parameters)
    {
        $result = array();

        $TldSayisi=count($parameters["request"]["TldList"]);
        
        try
        {
            // SOAP method which is same as current function name called
            $response = $this->service->__soapCall(__FUNCTION__, array($parameters));

        
            // Serialize as array
            $response = $this->objectToArray($response);
            
            // Check is there any error?

                if(!$this->hasError($response))
                    { 

                        if($TldSayisi>1){
                                $data = $response[key($response)];
                                
                                if(isset($data["DomainAvailabilityInfoList"]["DomainAvailabilityInfo"])) {
                                    if(is_array($data["DomainAvailabilityInfoList"]["DomainAvailabilityInfo"])) {
                                        $result["data"] = array();
                                        
                                        foreach($data["DomainAvailabilityInfoList"]["DomainAvailabilityInfo"] as $name => $value) {
                                            array_push($result["data"], 
                                                array(  "DomainName" => $value["DomainName"],//Domain adı
                                                        "TLD" => $value["Tld"], // Uzantı
                                                        "Status" => $value["Status"], // Domain alınabilirlik durumu available notavailable
                										"IsFee" => $value["IsFee"], // Domain Premium mu True /False
                										"Currency" => $value["Currency"],// Doviz kuru
                										"Command" => $value["Command"], // Komut create,renew,transfer,restore fiyatlarının çekilmesi
                										"Period" => $value["Period"], // Domain Periyodu
                										"Price" => $value["Price"], // Domain Fiyatı
                										"ClassKey" => $value["ClassKey"], //Premium domainin açıklaması
                										"Reason" => $value["Reason"] //Domain ile ilgili özel durumlar rezerve dilmiş  veya alınamıyor gibi
                                                )
                                            );
                                        }

                                        $result["result"] = "OK";

                                    }
                                    else
                                    {
                                        $result["result"] = "ERROR";
                                        $result["error"] = $this->setError("INVALID_DOMAIN_LIST", "Invalid response received from server!", "Domain list is not valid!");;
                                    }
                                }
                                else
                                {
                                    $result["result"] = "ERROR";
                                    $result["error"] = $this->setError("INVALID_DOMAIN_LIST", "Invalid response received from server!", "Domain list is not valid!");;
                                } 
                            }else{

                               $data = $response[key($response)];
                                
                                if(isset($data["DomainAvailabilityInfoList"]["DomainAvailabilityInfo"])) {
                                    if(is_array($data["DomainAvailabilityInfoList"]["DomainAvailabilityInfo"])) {
                                        $result["data"] = array(); 

                                            array_push($result["data"], 
                                                array(  "DomainName" => $data["DomainAvailabilityInfoList"]["DomainAvailabilityInfo"]["DomainName"],//Domain adı
                                                        "TLD" => $data["DomainAvailabilityInfoList"]["DomainAvailabilityInfo"]["Tld"], // Uzantı
                                                        "Status" => $data["DomainAvailabilityInfoList"]["DomainAvailabilityInfo"]["Status"], // Domain alınabilirlik durumu available notavailable
                                                        "IsFee" => $data["DomainAvailabilityInfoList"]["DomainAvailabilityInfo"]["IsFee"], // Domain Premium mu True /False
                                                        "Currency" => $data["DomainAvailabilityInfoList"]["DomainAvailabilityInfo"]["Currency"],// Doviz kuru
                                                        "Command" => $data["DomainAvailabilityInfoList"]["DomainAvailabilityInfo"]["Command"], // Komut create,renew,transfer,restore fiyatlarının çekilmesi
                                                        "Period" => $data["DomainAvailabilityInfoList"]["DomainAvailabilityInfo"]["Period"], // Domain Periyodu
                                                        "Price" => $data["DomainAvailabilityInfoList"]["DomainAvailabilityInfo"]["Price"], // Domain Fiyatı
                                                        "ClassKey" => $data["DomainAvailabilityInfoList"]["DomainAvailabilityInfo"]["ClassKey"], //Premium domainin açıklaması
                                                        "Reason" => $data["DomainAvailabilityInfoList"]["DomainAvailabilityInfo"]["Reason"] //Domain ile ilgili özel durumlar rezerve dilmiş  veya alınamıyor gibi
                                                )
                                            );
                                        

                                        $result["result"] = "OK";

                                    }
                                    else
                                    {
                                        $result["result"] = "ERROR";
                                        $result["error"] = $this->setError("INVALID_DOMAIN_LIST", "Invalid response received from server!", "Domain list is not valid!");;
                                    }
                                }
                                else
                                {
                                    $result["result"] = "ERROR";
                                    $result["error"] = $this->setError("INVALID_DOMAIN_LIST", "Invalid response received from server!", "Domain list is not valid!");;
                                }
                            }
                    }


            else
            {
                // Hata mesajini dondur
                $result["result"] = "ERROR";
                $result["error"] = $this->parseError($response);
            }
            
        }
        catch(Exception $ex)
        {
            $result["result"] = "ERROR";
            $result["error"] = $this->parseError($this->objectToArray($ex));
        }
        
        return $result;
    }

    
    // Get domain list
    public function GetList($parameters)
    {
        $result = array();
		
        try
        {
            // SOAP method which is same as current function name called
            $response = $this->service->__soapCall(__FUNCTION__, array($parameters));

            // Serialize as array
            $response = $this->objectToArray($response);
            
            // Check is there any error?
            if(!$this->hasError($response))
            {
                $data = $response[key($response)];

                // If DomainInfo a valid array
                if(isset($data["TotalCount"]) && is_numeric($data["TotalCount"]))
                {
					$result["data"]["Domains"] = array();
					
					if(isset($data["DomainInfoList"]) && is_array($data["DomainInfoList"]))
					{
						if(isset($data["DomainInfoList"]["DomainInfo"]["Id"]))
						{
							array_push($result["data"]["Domains"], $data["DomainInfoList"]["DomainInfo"]);
						}
						else
						{
							// Parse multiple domain info
							foreach($data["DomainInfoList"]["DomainInfo"] as $domainInfo)
							{
								array_push($result["data"]["Domains"], $this->parseDomainInfo($domainInfo));
							}					
						}
					
					}
					

                    $result["result"] = "OK";
					
                }
                else
                {
                    // Set error
                    $result["result"] = "ERROR";
                    $result["error"] = $this->setError("INVALID_DOMAIN_LIST", "Invalid response received from server!", "Domain info is not a valid array or more than one domain info has returned!");;                        
                }


            }
            else
            {
                // Hata mesajini dondur
                $result["result"] = "ERROR";
                $result["error"] = $this->parseError($response);
            }
            
        }
        catch(Exception $ex)
        {
            $result["result"] = "ERROR";
            $result["error"] = $this->parseError($this->objectToArray($ex));
        }
        
        return $result;
    }
    


    
    // Get domain details
    public function GetDetails($parameters)
    {
        $result = array();
		
        try
        {
            // SOAP method which is same as current function name called
            $response = $this->service->__soapCall(__FUNCTION__, array($parameters));

            // Serialize as array
            $response = $this->objectToArray($response);
            
            // Check is there any error?
            if(!$this->hasError($response))
            {
                $data = $response[key($response)];


                // If DomainInfo a valid array
                if(isset($data["DomainInfo"]) && is_array($data["DomainInfo"]))
                {
                    // Parse domain info
                    $result["data"] = $this->parseDomainInfo($data["DomainInfo"]);
                    $result["result"] = "OK";
					
                }
                else
                {
                    // Set error
                    $result["result"] = "ERROR";
                    $result["error"] = $this->setError("INVALID_DOMAIN_LIST", "Invalid response received from server!", "Domain info is not a valid array or more than one domain info has returned!");;                        
                }


            }
            else
            {
                // Hata mesajini dondur
                $result["result"] = "ERROR";
                $result["error"] = $this->parseError($response);
            }
            
        }
        catch(Exception $ex)
        {
            $result["result"] = "ERROR";
            $result["error"] = $this->parseError($this->objectToArray($ex));
        }
        
        return $result;
    }
    
    
    
    
    
    // Modify name servers
    public function ModifyNameServer($parameters)
    {
        $result = array();
        
        try
        {
            // SOAP method which is same as current function name called
            $response = $this->service->__soapCall(__FUNCTION__, array($parameters));

            // Serialize as array
            $response = $this->objectToArray($response);
            
            // Check is there any error?
            if(!$this->hasError($response))
            {
                $result["data"] = array();
                $result["data"]["NameServers"] = array();
                $result["data"]["NameServers"] = $parameters["request"]["NameServerList"];
                $result["result"] = "OK";

            }
            else
            {
                // Hata mesajini dondur
                $result["result"] = "ERROR";
                $result["error"] = $this->parseError($response);
            }
            
        }
        catch(Exception $ex)
        {
            $result["result"] = "ERROR";
            $result["error"] = $this->parseError($this->objectToArray($ex));
        }
        
        return $result;
    }   
    
    





    // Enable Theft Protection Lock
    public function EnableTheftProtectionLock($parameters)
    {
        $result = array();
        
        try
        {
            // SOAP method which is same as current function name called
            $response = $this->service->__soapCall(__FUNCTION__, array($parameters));

            // Serialize as array
            $response = $this->objectToArray($response);
            
            // Check is there any error?
            if(!$this->hasError($response))
            {
                $result["data"] = array();
                $result["data"]["LockStatus"] = var_export(true, true);
                $result["result"] = "OK";

            }
            else
            {
                // Hata mesajini dondur
                $result["result"] = "ERROR";
                $result["error"] = $this->parseError($response);
            }
            
        }
        catch(Exception $ex)
        {
            $result["result"] = "ERROR";
            $result["error"] = $this->parseError($this->objectToArray($ex));
        }
        
        return $result;
    }   
    






    // Disable Theft Protection Lock
    public function DisableTheftProtectionLock($parameters)
    {
        $result = array();
        
        try
        {
            // SOAP method which is same as current function name called
            $response = $this->service->__soapCall(__FUNCTION__, array($parameters));

            // Serialize as array
            $response = $this->objectToArray($response);
            
            // Check is there any error?
            if(!$this->hasError($response))
            {
                $result["data"] = array();
                $result["data"]["LockStatus"] = var_export(false, true);
                $result["result"] = "OK";

            }
            else
            {
                // Hata mesajini dondur
                $result["result"] = "ERROR";
                $result["error"] = $this->parseError($response);
            }
            
        }
        catch(Exception $ex)
        {
            $result["result"] = "ERROR";
            $result["error"] = $this->parseError($this->objectToArray($ex));
        }
        
        return $result;
    }   
    
	



    // Modify privacy protection status
    public function ModifyPrivacyProtectionStatus($parameters)
    {
        $result = array();
        
        try
        {
            // SOAP method which is same as current function name called
            $response = $this->service->__soapCall(__FUNCTION__, array($parameters));

            // Serialize as array
            $response = $this->objectToArray($response);
            
            // Check is there any error?
            if(!$this->hasError($response))
            {
                $result["data"] = array();
                $result["data"]["PrivacyProtectionStatus"] = var_export($parameters["request"]["ProtectPrivacy"], true);
                $result["result"] = "OK";

            }
            else
            {
                // Hata mesajini dondur
                $result["result"] = "ERROR";
                $result["error"] = $this->parseError($response);
            }
            
        }
        catch(Exception $ex)
        {
            $result["result"] = "ERROR";
            $result["error"] = $this->parseError($this->objectToArray($ex));
        }
        
        return $result;
    }   
    



    // CHILD NAMESERVER MANAGEMENT
    
    // Add Child Nameserver
    public function AddChildNameServer($parameters)
    {
        $result = array();
        
        try
        {
            // SOAP method which is same as current function name called
            $response = $this->service->__soapCall(__FUNCTION__, array($parameters));

            // Serialize as array
            $response = $this->objectToArray($response);
            
            // Check is there any error?
            if(!$this->hasError($response))
            {
                $result["data"] = array();
                $result["data"]["NameServer"] = $parameters["request"]["ChildNameServer"];
                $result["data"]["IPAdresses"] = array();
                $result["data"]["IPAdresses"] = $parameters["request"]["IpAddressList"];
                $result["result"] = "OK";

            }
            else
            {
                // Hata mesajini dondur
                $result["result"] = "ERROR";
                $result["error"] = $this->parseError($response);
            }
            
        }
        catch(Exception $ex)
        {
            $result["result"] = "ERROR";
            $result["error"] = $this->parseError($this->objectToArray($ex));
        }
        
        return $result;
    }   
    
    

    // Delete Child Nameserver
    public function DeleteChildNameServer($parameters)
    {
        $result = array();
        
        try
        {
            // SOAP method which is same as current function name called
            $response = $this->service->__soapCall(__FUNCTION__, array($parameters));

            // Serialize as array
            $response = $this->objectToArray($response);
            
            // Check is there any error?
            if(!$this->hasError($response))
            {
                $result["data"] = array();
                $result["data"]["NameServer"] = $parameters["request"]["ChildNameServer"];
                $result["result"] = "OK";

            }
            else
            {
                // Hata mesajini dondur
                $result["result"] = "ERROR";
                $result["error"] = $this->parseError($response);
            }
            
        }
        catch(Exception $ex)
        {
            $result["result"] = "ERROR";
            $result["error"] = $this->parseError($this->objectToArray($ex));
        }
        
        return $result;
    }   



    // Modify Child Nameserver
    public function ModifyChildNameServer($parameters)
    {
        $result = array();

        try
        {
            // SOAP method which is same as current function name called
            $response = $this->service->__soapCall(__FUNCTION__, array($parameters));

            // Serialize as array
            $response = $this->objectToArray($response);
            
            // Check is there any error?
            if(!$this->hasError($response))
            {
                $result["data"] = array();
                $result["data"]["NameServer"] = $parameters["request"]["ChildNameServer"];
                $result["data"]["IPAdresses"] = array();
                $result["data"]["IPAdresses"] = $parameters["request"]["IpAddressList"];
                $result["result"] = "OK";

            }
            else
            {
                // Hata mesajini dondur
                $result["result"] = "ERROR";
                $result["error"] = $this->parseError($response);
            }
            
        }
        catch(Exception $ex)
        {
            $result["result"] = "ERROR";
            $result["error"] = $this->parseError($this->objectToArray($ex));
        }
        
        return $result;
    }   







    // CONTACT MANAGEMENT
    
    // Get Contact
    public function GetContacts($parameters)
    {
        $result = array();

        try
        {
            // SOAP method which is same as current function name called
            $response = $this->service->__soapCall(__FUNCTION__, array($parameters));

            // Serialize as array
            $response = $this->objectToArray($response);
            
            // Check is there any error?
            if(!$this->hasError($response))
            {
                $data = $response[key($response)];

                // If ContactInfo a valid array
                if(isset($data["AdministrativeContact"]) && is_array($data["AdministrativeContact"]) && isset($data["TechnicalContact"]) && is_array($data["TechnicalContact"]) && isset($data["RegistrantContact"]) && is_array($data["RegistrantContact"]) && isset($data["BillingContact"]) && is_array($data["BillingContact"]))
                {
                    // Parse domain info
                    $result["data"] = array();
                    $result["data"]["contacts"] = array();
                    $result["data"]["contacts"]["Administrative"] = $this->parseContactInfo($data["AdministrativeContact"]);
                    $result["data"]["contacts"]["Billing"] = $this->parseContactInfo($data["BillingContact"]);
                    $result["data"]["contacts"]["Registrant"] = $this->parseContactInfo($data["RegistrantContact"]);
                    $result["data"]["contacts"]["Technical"] = $this->parseContactInfo($data["TechnicalContact"]);
                    $result["result"] = "OK";

                }
                else
                {
                    // Set error
                    $result["result"] = "ERROR";
                    $result["error"] = $this->setError("INVALID_CONTACT_INTO", "Invalid response received from server!", "Contact info is not a valid array or more than one contact info has returned!");;                     
                }

            }
            else
            {
                // Hata mesajini dondur
                $result["result"] = "ERROR";
                $result["error"] = $this->parseError($response);
            }
            
        }
        catch(Exception $ex)
        {
            $result["result"] = "ERROR";
            $result["error"] = $this->parseError($this->objectToArray($ex));
        }
        
        return $result;
    }   




    // Save contact informations
    public function SaveContacts($parameters)
    {
        $result = array();

        try
        {
            // SOAP method which is same as current function name called
            $response = $this->service->__soapCall(__FUNCTION__, array($parameters));

            // Serialize as array
            $response = $this->objectToArray($response);
            
            // Check is there any error?
            if(!$this->hasError($response))
            {
                $data = $response[key($response)];

                // If ContactInfo a valid array
                if(1 == 1)
                {
                    $result["result"] = "OK";
                }
                else
                {
                    // Set error
                    $result["result"] = "ERROR";
                    $result["error"] = $this->setError("INVALID_DOMAIN_LIST", "Invalid response received from server!", "Domain info is not a valid array or more than one domain info has returned!");;                        
                }

            }
            else
            {
                // Hata mesajini dondur
                $result["result"] = "ERROR";
                $result["error"] = $this->parseError($response);
            }
            
        }
        catch(Exception $ex)
        {
            $result["result"] = "ERROR";
            $result["error"] = $this->parseError($this->objectToArray($ex));
        }
        
        return $result;
    }   



    // Start domain transfer
    public function Transfer($parameters)
    {
        $result = array();

        try
        {
            // SOAP method which is same as current function name called
            $response = $this->service->__soapCall(__FUNCTION__, array($parameters));

            // Serialize as array
            $response = $this->objectToArray($response);
            
            // Check is there any error?
            if(!$this->hasError($response))
            {
                $data = $response[key($response)];

                // If DomainInfo a valid array
                if(isset($data["DomainInfo"]) && is_array($data["DomainInfo"]))
                {
                    // Parse domain info
                    $result["data"] = $this->parseDomainInfo($data["DomainInfo"]);
                    $result["result"] = "OK";
                    
                }
                else
                {
                    // Set error
                    $result["result"] = "ERROR";
                    $result["error"] = $this->setError("INVALID_DOMAIN_LIST", "Invalid response received from server!", "Domain info is not a valid array or more than one domain info has returned!");;                        
                }

            }
            else
            {
                // Hata mesajini dondur
                $result["result"] = "ERROR";
                $result["error"] = $this->parseError($response);
            }
            
        }
        catch(Exception $ex)
        {
            $result["result"] = "ERROR";
            $result["error"] = $this->parseError($this->objectToArray($ex));
        }
        
        return $result;
    }   



    // Cancel domain transfer
    public function CancelTransfer($parameters)
    {
        $result = array();

        try
        {
            // SOAP method which is same as current function name called
            $response = $this->service->__soapCall(__FUNCTION__, array($parameters));

            // Serialize as array
            $response = $this->objectToArray($response);
            
            // Check is there any error?
            if(!$this->hasError($response))
            {
                // Parse domain info
                $result["data"] = array();
                $result["data"]["DomainName"] = $parameters["request"]["DomainName"];
                $result["result"] = "OK";
            

            }
            else
            {
                // Hata mesajini dondur
                $result["result"] = "ERROR";
                $result["error"] = $this->parseError($response);
            }
            
        }
        catch(Exception $ex)
        {
            $result["result"] = "ERROR";
            $result["error"] = $this->parseError($this->objectToArray($ex));
        }
        
        return $result;
    }   


	    // Cancel domain transfer
    public function CheckTransfer($parameters)
    {
        $result = array();

        try
        {
            // SOAP method which is same as current function name called
            $response = $this->service->__soapCall(__FUNCTION__, array($parameters));

            // Serialize as array
            $response = $this->objectToArray($response);
            
            // Check is there any error?
            if(!$this->hasError($response))
            {
                // Parse domain info
                $result["data"] = array();
                $result["data"]["DomainName"] = $parameters["request"]["DomainName"];
                $result["result"] = "OK";
            

            }
            else
            {
                // Hata mesajini dondur
                $result["result"] = "ERROR";
                $result["error"] = $this->parseError($response);
            }
            
        }
        catch(Exception $ex)
        {
            $result["result"] = "ERROR";
            $result["error"] = $this->parseError($this->objectToArray($ex));
        }
        
        return $result;
    } 
	
	


    // Register domain with contact informations
    public function RegisterWithContactInfo($parameters)
    {
        $result = array();

        try
        {
            // SOAP method which is same as current function name called
            $response = $this->service->__soapCall(__FUNCTION__, array($parameters));

            // Serialize as array
            $response = $this->objectToArray($response);
            
            // Check is there any error?
            if(!$this->hasError($response))
            {
                $data = $response[key($response)];

                // If DomainInfo a valid array
                if(isset($data["DomainInfo"]) && is_array($data["DomainInfo"]))
                {
                    // Parse domain info
                    $result["data"] = $this->parseDomainInfo($data["DomainInfo"]);
                    $result["result"] = "OK";
                    
                }
                else
                {
                    // Set error
                    $result["result"] = "ERROR";
                    $result["error"] = $this->setError("INVALID_DOMAIN_LIST", "Invalid response received from server!", "Domain info is not a valid array or more than one domain info has returned!");;                        
                }

            }
            else
            {
                // Hata mesajini dondur
                $result["result"] = "ERROR";
                $result["error"] = $this->parseError($response);
            }
            
        }
        catch(Exception $ex)
        {
            $result["result"] = "ERROR";
            $result["error"] = $this->parseError($this->objectToArray($ex));
        }
        
        return $result;
    }   




    // Renew domain
    public function Renew($parameters)
    {
        $result = array();

        try
        {
            // SOAP method which is same as current function name called
            $response = $this->service->__soapCall(__FUNCTION__, array($parameters));

            // Serialize as array
            $response = $this->objectToArray($response);
            
            // Check is there any error?
            if(!$this->hasError($response))
            {
                $data = $response[key($response)];

                $result["data"] = array();
                $result["data"]["ExpirationDate"] = "";

                if(isset($data["ExpirationDate"]))
                {
                    $result["data"]["ExpirationDate"] = $data["ExpirationDate"];
                }

                $result["result"] = "OK";
            

            }
            else
            {
                // Hata mesajini dondur
                $result["result"] = "ERROR";
                $result["error"] = $this->parseError($response);
            }
            
        }
        catch(Exception $ex)
        {
            $result["result"] = "ERROR";
            $result["error"] = $this->parseError($this->objectToArray($ex));
        }
        
        return $result;
    }   








    // Delete domain
    public function Delete($parameters)
    {
        $result = array();

        try
        {
            // SOAP method which is same as current function name called
            $response = $this->service->__soapCall(__FUNCTION__, array($parameters));

            // Serialize as array
            $response = $this->objectToArray($response);
            
            // Check is there any error?
            if(!$this->hasError($response))
            {
                $result["result"] = "OK";
            }
            else
            {
                // Hata mesajini dondur
                $result["result"] = "ERROR";
                $result["error"] = $this->parseError($response);
            }
            
        }
        catch(Exception $ex)
        {
            $result["result"] = "ERROR";
            $result["error"] = $this->parseError($this->objectToArray($ex));
        }
        
        return $result;
    }   









    
    // Sync domain details
    public function SyncFromRegistry($parameters)
    {
        $result = array();
		
        try
        {
            // SOAP method which is same as current function name called
            $response = $this->service->__soapCall(__FUNCTION__, array($parameters));

            // Serialize as array
            $response = $this->objectToArray($response);
            
            // Check is there any error?
            if(!$this->hasError($response))
            {
                $data = $response[key($response)];

                // If DomainInfo a valid array
                if(isset($data["DomainInfo"]) && is_array($data["DomainInfo"]))
                {
                    // Parse domain info
                    $result["data"] = $this->parseDomainInfo($data["DomainInfo"]);
                    $result["result"] = "OK";
					
                }
                else
                {
                    // Set error
                    $result["result"] = "ERROR";
                    $result["error"] = $this->setError("INVALID_DOMAIN_LIST", "Invalid response received from server!", "Domain info is not a valid array or more than one domain info has returned!");;                        
                }

            }
            else
            {
                // Hata mesajini dondur
                $result["result"] = "ERROR";
                $result["error"] = $this->parseError($response);
            }
            
        }
        catch(Exception $ex)
        {
            $result["result"] = "ERROR";
            $result["error"] = $this->parseError($this->objectToArray($ex));
        }
        
        return $result;
    }
    
    
    
}








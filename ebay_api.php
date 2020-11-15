<?php
    // Change this to Debug.
    error_reporting(0);
    
    class ebay_api {
        function __construct() {   
            // Import & Set IDs
            $this->ebay_app_id = "Your Ebay Application ID.";
            $this->ebay_dev_id = "Your Ebay development ID.";
            $this->ebay_client_secret = "Your Ebay Client Secret.";
        }
        
        // Misc IDs
        private $ebay_app_id;
        private $ebay_dev_id;
        private $ebay_client_secret;

        // Main Vars
        private $call_logs = [];
        private $write_call_logs = false;

        // true = Writes logs to file, false = doesn't write logs to file.
        function Log($write_file, $call, $status){
            // https://www.php.net/manual/en/timezones.php
            // date_default_timezone_set("Europe/London");
            $date = date("Y-m-d");
            $date_time = date("Y-m-d H:i:sA");

            $log_string = "[{$date_time}] Call: {$call} | Status: {$status}";
            array_push($this->call_logs, $log_string);

            if ($write_file){
                // Opens the file with the current date and writes to it.
                $directory = "{$date}-ebay.txt";
                $file = fopen($directory, "a+") or die("Unable to open file!");
                fwrite($file, $log_string . "\n");
                fclose($file);
            }   
        }
         
        function GetEbayTime(){
            // Returns Greenwich Mean Time 
            // YYYY - MM - DD / HH:MM:SS:MS
        
            $request_url = "https://open.api.ebay.com/shopping?callname=GeteBayTime"
            . "&responseencoding=XML"
            . "&appid={$this->ebay_app_id}"
            . "&siteid=0"
            . "&version=1157";

            $request = file_get_contents($request_url);
            $data = simplexml_load_string($request);

            if($data->Ack == "Success"){
                
                if($this->write_call_logs){
                    $this->Log(true, "GetEbayTime()", "Success.");
                }
                
                return $data->Timestamp;
            } else {

                if($this->write_call_logs){
                    $this->Log(true, "GetEbayTime()", "Error, failed to return ebay time.");
                }
 
                return "There was an issue with your request.";
            }
        }

        function GetSingleItem($item_id, $site_id, $html_description){
            if($html_description === true){
                // You can't grab both at the same time. So you have to ask for either or. Default is without the HTML Mark-up.
                $request_url = "https://open.api.ebay.com/shopping?" 
                . "callname=GetSingleItem"
                . "&responseencoding=XML"
                . "&appid={$this->ebay_app_id}"
                . "&siteid={$site_id}"
                . "&version=1157"
                . "&ItemID={$item_id}"
                . "&IncludeSelector=Details,Description,ItemSpecifics,Variations,Compatibility";
            } else {
                $request_url = "https://open.api.ebay.com/shopping?" 
                . "callname=GetSingleItem"
                . "&responseencoding=XML"
                . "&appid={$this->ebay_app_id}"
                . "&siteid={$site_id}"
                . "&version=1157"
                . "&ItemID={$item_id}"
                . "&IncludeSelector=Details,Description,TextDescription,ItemSpecifics,Variations,Compatibility";
            }

            $request = file_get_contents($request_url);
            $data = simplexml_load_string($request);
            
            if($data->Ack == "Success"){

                if($this->write_call_logs){
                    $this->Log(true, "GetSingleItem({$item_id}, {$site_id}, {$html_description});", "Success.");
                }

                $product_data['Ack'] = $data->Ack;
                $product_data['Timestamp'] = $data->Timestamp;
                $product_data['Build'] = $data->Build;
                $product_data['Version'] = $data->Version;
                $product_data['BestOfferStatus'] = $data->Item->BestOfferEnabled;
                $product_data['Description'] = $data->Item->Description;
                $product_data['ItemID'] = $data->Item->ItemID;
                $product_data['EndTime'] = $data->Item->EndTime;
                $product_data['StartTime'] = $data->Item->StartTime;
                $product_data['NaturalURL'] = $data->Item->ViewItemURLForNaturalSearch;
                $product_data['ListingType'] = $data->Item->ListingType;
                $product_data['Location'] = $data->Item->Location;
                $product_data['PaymentMethods'] = $data->Item->PaymentMethods;
                $product_data['ListingGalleryURL'] = $data->Item->GalleryURL;
                $product_data['ListingPictureURL'] = $data->Item->PictureURL;
                $product_data['PostalCode'] = $data->Item->PostalCode;
                $product_data['PrimaryCatergoryID'] = $data->Item->PrimaryCategoryID;
                $product_data['PrimaryCatergoryName'] = $data->Item->PrimaryCategoryName;
                $product_data['Quantity'] = $data->Item->Quantity;
                $product_data['SellerUserID'] = $data->Item->Seller->UserID;
                $product_data['RatingStar'] = $data->Item->Seller->FeedbackRatingStar;
                $product_data['FeedbackScore'] = $data->Item->Seller->FeedbackScore;
                $product_data['FeedbackPercent'] = $data->Item->Seller->PositiveFeedbackPercent;
                $product_data['Remaining'] = $data->Item->Quantity - $data->Item->QuantitySold;
                $product_data['BidCount'] = $data->Item->BidCount;

                // This is returned based on the Site-ID you give. So if the product is listed on the .co.uk domain and you give it the .com Site-Id (0), It'll return the converted price and the currency-id (USD).
                $product_data['ConvertedCurrentPrice'] = $data->Item->ConvertedCurrentPrice;
                $product_data['ConvertedCurrencyID'] =  $data->Item->ConvertedCurrentPrice['currencyID'];
                
                // This will always be the original price on the domain the product was listed. Regardless of Site-ID provided to the API Call.
                $product_data['CurrentPrice'] = $data->Item->CurrentPrice;
                $product_data['CurrentPriceCurrencyID'] = $data->Item->CurrentPrice['currencyID'];

                $product_data['ListingStatus'] = $data->Item->ListingStatus;
                $product_data['QuantitySold'] = $data->Item->QuantitySold;
                $product_data['ShipToLocations'] = $data->Item->ShipToLocation;

                // Returns the original domain the product was listed on.
                $product_data['Site'] = $data->Item->Site;
                
                $product_data['TimeLeft'] = $data->Item->TimeLeft;
                $product_data['Title'] = $data->Item->Title;
                $product_data['ProductBrand'] = $data->Item->ItemSpecifics->NameValueList[4]->Value;
                $product_data['Views'] = $data->Item->HitCount;
                $product_data['PrimaryCategoryIDPath'] = $data->Item->PrimaryCategoryIDPath;
                $product_data['StoreURL'] = $data->Item->Storefront->StoreURL;
                $product_data['StoreName'] = $data->Item->Storefront->StoreName;
                $product_data['Country'] = $data->Item->Country;
                $product_data['ReturnWithin'] = $data->Item->ReturnPolicy->ReturnsWithin;
                $product_data['ReturnsAccepted'] = $data->Item->ReturnPolicy->ReturnsAccepted;
                $product_data['ShippingCostPaidBy'] = $data->Item->ReturnPolicy->ShippingCostPaidBy;
                $product_data['AutoPay'] = $data->Item->AutoPay;
                $product_data['IntegratedMerchantCreditCardEnabled'] = $data->Item->IntegratedMerchantCreditCardEnabled;
                $product_data['HandlingTime'] = $data->Item->HandlingTime;
                $product_data['ConditionID'] = $data->Item->ConditionID;
                $product_data['ProductCondition'] = $data->Item->ConditionDisplayName;
                $product_data['QuantityAvailableHint'] = $data->Item->QuantityAvailableHint;
                $product_data['QuantityThreshold'] = $data->Item->QuantityThreshold;
                $product_data['GlobalShipping'] = $data->Item->GlobalShipping;
                $product_data['QuantitySoldByPickupInStore'] = $data->Item->QuantitySoldByPickupInStore;
                $product_data['SKU'] = $data->Item->SKU;
                $product_data['NewBestOffer'] = $data->Item->NewBestOffer;
                // $product_data[''] = $data->Item->;

                return $product_data;
            } else {

                if($this->write_call_logs){
                    $this->Log(true, "GetSingleItem({$item_id}, {$site_id});", "Error: The request has failed.");
                }

                return "There was an issue with your request.";
            }
        }
        
        // These are utility functions built to help with general use of the API.    
        function site2domain($site){
            switch($site){
                case "Australia":
                    return "https://www.ebay.com.au/";

                case "Austria":
                    return "https://www.ebay.at/";

                case "Belgium_Dutch":
                    return "https://www.benl.ebay.be/";

                case "Belgium_French":
                    return "https://www.befr.ebay.be/";

                case "Canada":
                    return "https://www.ebay.ca/";

                case "CanadaFrench":
                    return "https://www.cafr.ebay.ca/";

                case "France":
                    return "https://www.ebay.fr/";
                
                case "Germany":
                    return "https://www.ebay.de/";

                case "HongKong":
                    return "https://www.ebay.com.hk/";
                
                case "India":
                    return "https://www.ebay.in/";

                case "Ireland":
                    return "https://www.ebay.ie/";

                case "Italy":
                    return "https://www.ebay.it/";
                    
                case "Malaysia":
                    return "https://www.ebay.com.my/";

                case "Netherlands":
                    return "https://www.ebay.nl/";
                    
                case "Philippines":
                    return "https://www.ebay.ph/";

                case "Poland":
                    return "https://www.ebay.pl/";

                case "Singapore":
                    return "https://www.ebay.sg/";

                case "Spain":
                    return "https://www.ebay.es/";

                case "Switzerland":
                    return "https://www.ebay.ch/";

                case "UK":
                    return "https://www.ebay.co.uk/";

                case "US":
                    return "https://www.ebay.com/";   
                      
            default:
                return "https://www.ebay.com/";
            }
        }
        
        function site2global($site){
            // Site -> Global-ID
            // Not all these are supported and ebay doesn't tell you what is. Need to manually check every single one to see if it works and if it doesn't remove it from the list.
            // Currently Not Supported (Russia)
            switch($site){
                case "Australia":
                    return "EBAY-AU";
                
                case "Austria":
                    return  "EBAY-AT";
            
                case "Belgium_Dutch":
                    return "EBAY-NLBE";

                case "Belgium_French":
                    return "EBAY-FRBE";

                case "Canada":
                    return "EBAY-ENCA";

                case "CanadaFrench":
                    return "EBAY-FRCA";
            
                case "France":
                    return "EBAY-FR";

                case "Germany":
                    return "EBAY-DE";

                case "HongKong":
                    return "EBAY-HK";

                case "India": 
                    return "EBAY-IN";
                
                case "Ireland":
                    return "EBAY-IE";

                case "Italy":
                    return "EBAY-IT";

                case "Malaysia":
                    return "EBAY-MY";

                case "Netherlands":
                    return "EBAY-NL";
                
                 case "Philippines":
                    return "EBAY-PH";

                case "Poland":
                    return "EBAY-PL";

                case "Spain":
                    return "EBAY-ES";
                                                       
                case "Singapore":
                    return "EBAY-SG";

                case "Switzerland":
                    return "EBAY-CH";

                case "UK":
                    return "EBAY-GB";

                case "US":
                    return "EBAY-US";
                
                case "Motors":
                    return "EBAY-MOTOR";
                    
                default:
                    return "EBAY-US";
            }
        }

        function curr2sym($curr) {
            // Converts currency abbreviations to symbols. 
            // Only made this because I like having the symbol over chars.
            // USD = $, EUR = €, GBP = £, etc
            // This supports every domain on https://developer.ebay.com/DevZone/merchandising/docs/Concepts/SiteIDToGlobalID.html
        
            switch($curr){
                case "USD":
                    return "$";
                    
                case "GBP":
                    return "£";
                    
                case "EUR":
                    return "€";
                    
                case "CAD":
                    // Canadian Money Symbols: $, C$, CAD, Can$
                    return "C$";

                case "AUD":
                    // Australian Money Symbols: $, A$, AUD
                    return "A$";

                case "CHF": 
                    // Switzerland Money Symbols: CHf, Fr., SFr.
                    return "Fr.";

                case "HKD":
                    // Hong Kong Money Symbols: $, HK$
                    return "HK$";
                    
                case "MYR":
                    return "RM";

                case "PHP":
                    // hehe php (Phillipines)
                    return "₱";

                case "PLN":
                    return "zł";
                    
                case "SGD":
                    // Singapore Money Symbols: $, S$
                    return "S$";
                    
                default:
                    // If this fails somehow it'll just return the three chars instead of nothing.
                    return $curr;
            }
            
        }

        function GrabItemID($ebay_url){
            // Check if its an ebay domain.
            if(strpos($ebay_url, 'ebay.')){

                // The reason I decided to use Regex for this as it was the only consistent thing across all ebay domains.
                // Also the reason its checking for 2 matches is because Regex returns a "Full Match" and "Group 1" 
                // Both the exact same thing in this case.
                
                $regex = '/(\d{12})/m';
                preg_match_all($regex, $ebay_url, $matches, PREG_SET_ORDER, 0);
                if(count($matches[0]) == 2){
                    
                    // Logging Options
                    $this->Log(true, "GrabItemID(\"{$ebay_url}\");", "Success");
                    
                    // Proceed  
                    return $matches[0][0];

                } else if (count($matches[0]) > 2){

                    $this->Log(true, "GrabItemID(\"{$ebay_url}\");", "Error: More than 1 Match in Regex.");
                    // Fails if more than 1 ID is detected in the URL
                    return false;

                } else {

                    $this->Log(true, "GrabItemID(\"{$ebay_url}\");", "Error: No match, bad Link.");
                    // Fails if 0 matches.
                    return false;
                
                }          
            }
            
            $this->Log(true, "GrabItemID(\"{$ebay_url}\");", "Error: Definitely not an Ebay link.");
            // Fails if link doesn't have "ebay." in the string
            return false;  
        }
    }
?>
<?php  
    // 2 API CALL EXPLANATION
    // The reason Two Requests must be made to the API is because Ebay has horrible endpoint management and half
    // the data you need isn't available with APIs they say it is. Aka WatchCount is meant to be on the findItems call but it doesn't return.
    // So we gotta do a shitty hacky work around to grab the watchers. (You can only get watchers from the finding api...)

    // Grab ID > findSingleItem API Call > Grab "Site" tag from XML > Convert it to a Global ID > Make a Call to findItemsAdvanced > Grab WatchCount
    // Return the Data to user. (Finding API and Shopping API are both different end-points and have their usage calculated seperately. So its basically only 1 Call on Each.
    // Finding API = 5,000 Calls a Day
    // Shopping API = 5,000 Calls a Day
    
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
        private $seller_data;
        private $product_data;

        private $call_logs = [];
        private $write_call_logs;

        function SetData($data_set, $data_name, $data){

            // Data Sets (0 = Product Information, 1 = Seller Information)
            if($data_set === 0){
                
                if($data == NULL || $data == ""){

                    $this->product_data[$data_name] = "No Value Provided.";
                    return;

                } else {

                    $this->product_data[$data_name] = $data;
                    return;
                    
                }
                
                
            } else if ($data_set === 1) {

                if($data == NULL || $data == ""){

                    $this->seller_data[$data_name] = "No Value Provided";
                    return;
                    
                } else {

                    $this->seller_data[$data_name] = $data;
                    return;

                }
                
                
            } else {

                return "[Error] No map specified.";
            
            }
        }

        function GrabData($data_set, $data_name){

            // Data Sets (0 = Listing Information, 1 = Seller Information, 2 = Search Results)
            if($data_set === 0){
                
                if($this->product_data[$data_name] !== null){

                    if($data_name == "listing_remaining"){

                        return $this->product_data[$data_name];
                    }
                    
                    return $this->product_data[$data_name][0];
                }

                return "[Error] No Data found.";
                
            } else if ($data_set === 1) {

                if($this->seller_data[$data_name] !== null){

                    if($data_name == "top_rated_seller" || $data_name == "seller_link" || $data_name == "seller_other_listings"){

                        return $this->seller_data[$data_name];

                    } else {

                        return $this->seller_data[$data_name][0];

                    }

                }

                return "[Error] No Data found.";

            } else {

                return "[Error] No map specified.";
            
            }
        }
    
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

        /*
        !! stoned coding rant below !!
        
        This is by far the bane of my existence, I have not found 1 bug free way of getting an Item ID from a URL.
        I have tried making 1 request to the URL and grabbing the item-id from the Source-HTML but this wasn't consistent across all their sites.
        Even the ones using the same designs have slight differences. (The information just wasn't there, its removed fully, so I can't just handle the exceptions)
        So I settled on using Regex to just check the link they provide for a string of integers between 11 - 13. If it exists it uses that as the item-id.
        An obvious fault with this is if a persons listing name has a string of 11 - 13 numbers in a row it'll return false.

        Side Note: I haven't found an ItemID in 2+ years of dealing with ebay that is longer or shorter than 12 numbers. But I added the extra number on each side
        of the range (11-13) as padding. On the good news I have scanned really hard and found 0 listings with a string of numbers in their title that matches our regex.
        So thats neat. It also checks to make sure the string also contains "ebay.". And if it doesn't it just returns false.
        */
        
        function grab_item_id($ebay_url){
            // Check if its an ebay domain.
            if(strpos($ebay_url, 'ebay.')){

                // The reason I decided to use Regex for this as it was the only consistent thing across all ebay domains.
                $regex = '/\d{11,13}/m';
                preg_match_all($regex, $ebay_url, $matches, PREG_SET_ORDER, 0);
                
                if(count($matches[0]) == 1){
                    // Save Item ID
                    $this->SetData(0, "listing_id", $matches[0]);
                    
                    // Proceed
                    return true;
                } 

                // Fails if more than 1 ID is detected in the URL
                return false;
            }
            
            // Fails if link doesn't have "ebay." in the string
            return false;  
        }
    
        function watcher_lookup($site){
            // Grab WatchCount with Converted Global ID
            $request_url = "https://svcs.ebay.com/services/search/FindingService/v1?OPERATION-NAME=findItemsAdvanced"
                . "&SERVICE-VERSION=1.13.0"
                . "&SECURITY-APPNAME={$this->ebay_app_id}"
                . "&GLOBAL-ID={$this->site2global($site)}"
                . "&keywords={$this->GrabData(0, "listing_id")}"    
                . "&paginationInput.entriesPerPage=3"
                . "&descriptionSearch=true"; 

                $request = file_get_contents($request_url);
                $data = simplexml_load_string($request);
                
                if($data[0]->Ack !== "Failure"){
                    // This is REALLY fucking weird. But I'ma document it anyway.
                    // $data[0]->searchResult[0]->item->listingInfo->watchCount[0] and $data[0]->searchResult[0]->item->listingInfo->watchCount return the same value but the one without [0] also returns null if empty.
                    // The one without [0] just returns an empty XML Object.
                    
                    if($data[0]->searchResult[0]->item->listingInfo->watchCount[0] !== NULL){
                        
                        $this->SetData(0, "listing_watchers", $data[0]->searchResult[0]->item->listingInfo->watchCount);
                        return;

                    } else {

                        $this->SetData(0, "listing_watchers", "0");
                        return;
                        
                    }
            
                } else {

                    $this->SetData(0, "listing_watchers", "Error Occured.");
                    return;
                    
                }         
        }

        function product_lookup($site_id){
            $request_url = "https://open.api.ebay.com/shopping?" 
            . "callname=GetSingleItem"
            . "&responseencoding=XML"
            . "&appid={$this->ebay_app_id}"
            . "&siteid={$site_id}"
            . "&version=1157"
            . "&ItemID={$this->GrabData(0, "listing_id")}"
            . "&IncludeSelector=Details,Sold,Description";
            
            $request = file_get_contents($request_url);
            $data = simplexml_load_string($request);
            
            if($data->{'Ack'} !== "Failure"){
                // Profile Data
                $this->SetData(1, "seller_name", $data->Item->Seller->UserID);
                $this->SetData(1, "seller_link", $this->site2domain($data->Item->Site) . "usr/" . $data->Item->Seller->UserID);
                $this->SetData(1, "seller_total_feedback", $data->Item->Seller->FeedbackScore);
                $this->SetData(1, "seller_feedback_percent", $data->Item->Seller->PositiveFeedbackPercent);
                $this->SetData(1, "seller_other_listings", $this->site2domain($data->Item->Site) . "sch/" . $data->Item->Seller->UserID . "/m.html?_nkw=&_armrs=1&_ipg=&_from=");
                $this->SetData(1, "seller_site", $data->Item->Site);
                $this->SetData(1, "seller_country", $data->Item->Country);

                if($data->StoreFront !== NULL){
                    $this->SetData(1, "seller_store_url", $data->StoreFront->StoreURL);
                    $this->SetData(1, "seller_store_name", $data->StoreFront->StoreName);
                }

                if($data->Item->Seller->TopRatedSeller !== NULL){
                    
                    if($data->Item->Seller->TopRatedSeller == "true"){

                        $this->SetData(1, "top_rated_seller", "Yes.");

                    } else {
                        
                        $this->SetData(1, "top_rated_seller", "No.");
                        
                    }
                }

                // Listing Data
                $this->SetData(0, "listing_title", $data->Item->Title);
                $this->SetData(0, "listing_created", $data->Item->StartTime);
                $this->SetData(0, "listing_ending", $data->Item->EndTime);
                $this->SetData(0, "listing_views", $data->Item->HitCount);
                //$this->SetData(0, "listing_watchers", "Not available with this API Call.");
                $this->SetData(0, "listing_quantity", $data->Item->Quantity);
                $this->SetData(0, "listing_sold", $data->Item->QuantitySold);
                $remaining = $data->Item->Quantity - $data->Item->QuantitySold;
                $this->SetData(0, "listing_remaining",  (string)$remaining);
                $this->SetData(0, "listing_link", $data->Item->ViewItemURLForNaturalSearch);
                $this->SetData(0, "listing_image_link", $data->Item->PictureURL);
                $this->SetData(0, "listing_price", $data->Item->CurrentPrice);
                $this->SetData(0, "listing_currency", $data->Item->CurrentPrice['currencyID']);
                $this->SetData(0, "listing_converted_price", $data->Item->ConvertedCurrentPrice);
                $this->SetData(0, "listing_converted_currency", $data->Item->ConvertedCurrentPrice['currencyID']);
                $this->SetData(0, "listing_desc", $data->Item->Description);
                $this->SetData(0, "listing_id", $data->Item->ItemID);
                
                return;        
            }

            return false;
        }
        
        // Returns Greenwich Mean Time 
        // YYYY - MM - DD / HH:MM:SS:MS
        function GetEbayTime(){
            $request_url = "https://open.api.ebay.com/shopping?callname=GeteBayTime"
            . "&responseencoding=XML"
            . "&appid={$this->ebay_app_id}"
            . "&siteid=0"
            . "&version=1157";

            $request = file_get_contents($request_url);
            $data = simplexml_load_string($request);
            $error = error_get_last();
            
            // If the request is GOOD, PHP will return a Notice about trying to reach
            // an unassigned array offset ($error['message']); 
            // Might want to use [ error_reporting(0); ] in production.
            if(!$error['message'] && $data->Ack != "Failure"){
                return $data->Timestamp;
            } else {
                return "There was an issue with your request.";
                error_clear_last();
            }
        }
        
        /*
        These are utility functions built to help with 
        general use of the API.
        */
        
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

        // Converts currency abbreviations to symbols. 
        // Only made this because I like having the symbol over chars.
        // USD = $, EUR = €, GBP = £, etc
        // This supports every domain on https://developer.ebay.com/DevZone/merchandising/docs/Concepts/SiteIDToGlobalID.html
        function curr2sym($curr) {
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
    }
?>
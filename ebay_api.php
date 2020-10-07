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
            $this->ebay_app_id = "YOUR APP ID";
            $this->ebay_dev_id = "YOUR DEV ID";
            $this->ebay_client_secret = "YOUR CLIENT SECRET";
        }
        
        // Misc IDs
        public $ebay_app_id;
        public $ebay_dev_id;
        public $ebay_client_secret;

        // Main Vars
        public $seller_data;
        public $product_data;
        

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

            // Data Sets (0 = Product Information, 1 = Seller Information)
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
    
        function grab_item_id($ebay_url){
            // Check if its an ebay domain.
            if(strpos($ebay_url, 'ebay.')){

                // The reason I decided to use Regex for this as it was the only consistent thing across all ebay domains.
                // I also wanted to try not have to use requests to the URL provided.
                // This Regex will check for 11-13 numbers in a row and if so, it will grab that match and save it. 
                // This isn't very fool proof as if your ebay product link has a 11-13 digit number it will return 2 matches and fail the lookup.
                
                $regex = '/\d{11,13}/m';
                preg_match_all($regex, $ebay_url, $matches, PREG_SET_ORDER, 0);
                
                if(count($matches[0]) == 1){
                    // Save Item ID
                    $this->SetData(0, "listing_id", $matches[0]);
                    
                    // Proceed
                    return true;
                } 

                // Fails if more than 1 ID is detected in the URL, Return Image Guide
                return false;
            }
            
            // Fails if link doesn't have "ebay." in the string, , Return Image Guide
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

                case "Ireland":
                    return "EBAY-IE";

                case "Italy":
                    return "EBAY-IT";

                case "Netherlands":
                    return "EBAY-NL";

                case "Philippines":
                    return "EBAY-PH";

                case "Poland":
                    return "EBAY-PL";

                case "Spain":
                    return "EBAY-ES";

                case "Switzerland":
                    return "EBAY-CH";

                case "UK":
                    return "EBAY-GB";

                case "US":
                    return "EBAY-US";
                
                default:
                    return "EBAY-US";
            }
        }
    }
?>
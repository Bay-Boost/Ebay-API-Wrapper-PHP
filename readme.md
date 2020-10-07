# Basic Ebay API Wrapper (Listing Info)
This is what I am currently using in my project https://bay-boost.com/. And as Ebay's documentation is absolutely dreadful I thought I would release this to help anyone else out.

## Included Information [Function Call Required]
Function Used: product_lookup()
- Listing Title
- Listing Created/Ending
- Listing Views
- Listing Quantity
- Listing Sold
- Listing Remaining
- Listing URL
- Listing Image
- Listing Price
- Listing Currency
- Listing Converted Price (This will show you the converted price if you are searching for a product on a different ebay site. Etc Listing on ebay.co.uk but you're looking it up from ebay.com.)
- Listing Converted Currency
- Listing Description
- Listing ID
- Seller Name
- Seller Link
- Seller Total Feedback
- Seller Feedback Percentage
- Seller Site (Original Domain)
- Seller Country

Function Used: watcher_lookup()
- Listing Watchers

## How to use?
For the data sets I have it split up between Listing Information and Seller Information. We identify these with 0 and 1. 

0 = Listing Information

1 = Seller Information

```php
// Don't forget to add your development information on line 18, 19 and 20 in the ebay_api.php file.
require 'ebay_api.php';
$ebay_api = new ebay_api();

// grab_item_id will try and grab the listings ID, if it fails it's a bad link.
// grab_item_id must be called before anything else will work as everything else required a valid item ID to work.
if($ebay_api->grab_item_id($ebay_link)){

    // Please provide a valid Site ID. (https://developer.ebay.com/DevZone/XML/Docs/Reference/ebay/types/SiteCodeType.html)
    // Most Popular: AU: 15, US: 0, UK: 3, CAD: 2 
    $ebay_api->product_lookup($site_id); 

    // To use the information you have just acquired you call it like this.
    $ebay_api->GrabData(0, "listing_title");

    // Listing Views
    $ebay_api->GrabData(0, "listing_views");

    // Top Rated Seller (Returns "Yes." or "No.")
    $ebay_api->GrabData(1, "top_rated_seller");

    // If you wish to get the Watchers of a listing you will need to use the secondary function watcher_lookup()
    // The reason behind why we have to do this is at the top of the api file.
    // Grab Watchers Information (You MUST Call product_lookup() first as it will provide you with the information to successfully get the Watchers) 
    $ebay_api->watcher_lookup($ebay_api->GrabData(1, "seller_site"));

    // Watchers
    $ebay_api->GrabData(0, "listing_watchers");

    // If you want to know how to call something specifically, procceed to line 181 of the ebay_api.php file.
}
```

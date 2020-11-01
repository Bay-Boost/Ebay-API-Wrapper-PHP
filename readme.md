<p align="center">
  <img src="https://i.ibb.co/C75cJX1/test.png" /><br>
    This is a wrapper for the Ebay API. I plan on finishing this in my spare time over a few months.<br>
    *I am not a great programmer so please forgive any issues or coding faux pas you find.*
</p>
<hr>
<br>

|   Endpoint   |       Call       |       Status      | Docs |
|:------------:|:----------------:|:-----------------:|:-----:|
| Shopping API |   FindProducts   |   <img src="https://lingtalfi.com/services/pngtext?color=cc0000&size=11&text=Not%20Supported">   |       |
| Shopping API |  GetCategoryInfo |   <img src="https://lingtalfi.com/services/pngtext?color=cc0000&size=11&text=Not%20Supported">   |       |
| Shopping API |    GeteBayTime   |   <img src="https://lingtalfi.com/services/pngtext?color=08FF00&size=11&text=Supported">         |   ebay_api->GetEbayTime()   |
| Shopping API |   GetItemStatus  |   <img src="https://lingtalfi.com/services/pngtext?color=cc0000&size=11&text=Not%20Supported">   |       |
| Shopping API | GetMultipleItems |   <img src="https://lingtalfi.com/services/pngtext?color=cc0000&size=11&text=Not%20Supported">   |       |
| Shopping API | GetShippingCosts |   <img src="https://lingtalfi.com/services/pngtext?color=cc0000&size=11&text=Not%20Supported">   |       |
| Shopping API |   GetSingleItem  |   <img src="https://lingtalfi.com/services/pngtext?color=FFC300&size=11&text=Supported">         |   ebay_api->product_lookup($<a href="https://developer.ebay.com/DevZone/XML/Docs/Reference/ebay/types/SiteCodeType.html">site_id</a>)    |
| Shopping API |  GetUserProfile  |   <img src="https://lingtalfi.com/services/pngtext?color=cc0000&size=11&text=Not%20Supported">   |       |

*There are more Endpoints/Calls that I will update soon, they aren't listed because they aren't completed.*<br>
*I am also currently working on Full Documentation to make this more easy to use.*

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
    // Grab Watchers Information (You MUST Call product_lookup() first as it will provide you with the information to successfully get the Watchers) 
    $ebay_api->watcher_lookup($ebay_api->GrabData(1, "seller_site"));

    // Watchers
    $ebay_api->GrabData(0, "listing_watchers");

    // If you want to know how to call something specifically, procceed to line 181 of the ebay_api.php file.
}
```

## Function Information
```
Function: grab_item_id($ebay_link);
Requires: Ebay URL.
Data Returned:-
- $ebay_api->GrabData(0, "listing_id");

Function: product_lookup($site_id);
Requires: grab_item_id($ebay_link) needs to be called before this and must return true. 
Data Returned:-
- $ebay_api->GrabData(0, "listing_title");
- $ebay_api->GrabData(0, "listing_created");
- $ebay_api->GrabData(0, "listing_ending");
- $ebay_api->GrabData(0, "listing_views");
- $ebay_api->GrabData(0, "listing_quantity");
- $ebay_api->GrabData(0, "listing_sold");
- $ebay_api->GrabData(0, "listing_remaining");
- $ebay_api->GrabData(0, "listing_link");
- $ebay_api->GrabData(0, "listing_image_link");
- $ebay_api->GrabData(0, "listing_price");
- $ebay_api->GrabData(0, "listing_currency");
- $ebay_api->GrabData(0, "listing_converted_price");
- $ebay_api->GrabData(0, "listing_converted_currency");
- $ebay_api->GrabData(0, "listing_desc");
- $ebay_api->GrabData(0, "listing_id");

Function: watcher_lookup($site);
Requires: product_lookup($site_id) needs to be called beforehand.
Data Returned:-
- $ebay_api->GrabData(0, "listing_watchers");

Function: GetEbayTime();
Requires: Nothing.
Data Returned:-
- Full GMT Time (YYYY - MM - DD / HH:MM:SS:MS)

```

## Credits
www.ebay.com - Thank you. <br>
www.watchcount.com - Jay Specifically, thank you for helping me with some things that aren't documented very well.

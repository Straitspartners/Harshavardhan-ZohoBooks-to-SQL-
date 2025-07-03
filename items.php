<?php
// Zoho Books API URL for items (adjust URL as necessary)
$url = 'https://www.zohoapis.in/books/v3/items?organization_id=60008266217&cf_created_date=2025-01-17';  // Adjust this URL as per your actual Zoho API endpoint

// Your OAuth token and refresh token
$access_token = '1000.2ffd0a14d1c6999b515c95f7cf2e8374.85f2b973b1bc53a1782364ee65309868';
$refresh_token = '1000.60e90e1b4772d0c026cb615437c35541.4292106f120fbdea4dd8b528bc995d81';

// MySQL connection setup
$servername = "190.92.174.90";
$username = "abptone_trading_zoho_user"; // Change this to your MySQL username
$password = "Strait@9760!"; // Change this to your MySQL password
$dbname = "abptone_trading_zoho";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to refresh OAuth token
function refreshAccessToken($refresh_token) {
    $url = 'https://accounts.zoho.in/oauth/v2/token';
    $data = [
        'refresh_token' => $refresh_token,
        'client_id' => '1000.E0UC7XOAL21FNJIM7GIJA53JNIAJ3Q', // Your client ID
        'client_secret' => 'fa29b16691413821946cbe4e885373acdbbab1b908', // Your client secret
        'grant_type' => 'refresh_token'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

    $response = curl_exec($ch);
    curl_close($ch);

    if ($response === false) {
        die('Error refreshing access token.');
    }

    $response_data = json_decode($response, true);
    if (isset($response_data['access_token'])) {
        $_SESSION['access_token'] = $response_data['access_token'];
        $_SESSION['last_token_refresh'] = time();  // Update refresh timestamp
        return $response_data['access_token'];
    }

    die('Error: Could not refresh the access token.');
}

// Refresh the access token if expired
if (isTokenExpired($access_token)) {
    $access_token = refreshAccessToken($refresh_token);
}

// Set up pagination for API calls
$page = 1;
$per_page = 200;
$all_items = [];

// Loop through all pages until no more items are returned
do {
    // cURL setup to fetch data from Zoho Books API
    $ch = curl_init();
    $page_url = $url . "&page=$page&per_page=$per_page"; // Add pagination parameters
    curl_setopt($ch, CURLOPT_URL, $page_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Zoho-oauthtoken ' . $access_token
    ]);
    curl_setopt($ch, CURLOPT_CAINFO, 'C:\php-8.4.2\extras\ssl\cacert.pem');
    $response = curl_exec($ch);

    // Check for cURL errors
    if (curl_errno($ch)) {
        echo 'cURL error: ' . curl_error($ch);
        exit;
    }

    curl_close($ch);

    // Decode JSON response
    $data = json_decode($response, true);

    if (isset($data['items'])) {
        // Append items from this page to the all_items array
        $all_items = array_merge($all_items, $data['items']);
       // echo $all_items;
        $page++; // Move to the next page
    } else {
        break; // No items found, exit loop
    }
} while (count($data['items']) > 0); // Keep fetching until no items are returned

// Now you have all items in $all_items
foreach ($all_items as $items1) {
    // Extract fields from Zoho Books API response
    $item_id = isset($items1['item_id']) ? $items1['item_id'] : null;
    $urlll = 'https://www.zohoapis.in/books/v3/items/'.$item_id.'?organization_id=60008266217';
             //echo $urlll; 
//////////////////////////////////////////////////////////////////////////////////////////
    
              //echo $access_token;
           
              $chh = curl_init();
              // Add pagination parameters
                 curl_setopt($chh, CURLOPT_URL, $urlll);
                 curl_setopt($chh, CURLOPT_RETURNTRANSFER, true);
                 curl_setopt($chh, CURLOPT_HTTPHEADER, [
                     'Authorization: Zoho-oauthtoken ' . $access_token
                 ]);
                
                 curl_setopt($chh, CURLOPT_CAINFO, 'C:\php-8.4.2\extras\ssl\cacert.pem');
                 $responses = curl_exec($chh);
                 //echo $responses;
                 curl_close($chh);
                 $dataa = json_decode($responses, true);
                 if (isset($dataa['item']))
                 {
                  $item=$dataa['item'];
                 $item_id= isset($item['item_id']) ? $item['item_id'] : null;
                $item_desc = isset($item['description']) ? $item['description'] : null;
                $item_name = isset($item['name']) ? $item['name'] : null;
                $purchase_price = isset($item['purchase_rate']) ? $item['purchase_rate'] : 0.00;
                $selling_price = isset($item['sales_rate']) ? $item['sales_rate'] : 0.00;
                $wh_name = isset($item['warehouse_name']) ? $item['warehouse_name'] : null;
                $part_type = isset($item['part_type']) ? $item['part_type'] : null;
                $category_name = isset($item['category_name']) ? $item['category_name'] : null;
                $item_brand = isset($item['brand']) ? $item['brand'] : null;  // Assuming 'brand' field exists
                $stock_in_hand = isset($item['stock_on_hand']) ? $item['stock_on_hand'] : 0;  // Assuming 'stock_in_hand' exists
                $created_at = isset($item['created_time']) ? convertZohoDatetimeToMysql($item['created_time']) : null;  // Assuming 'created_time' field exists
                $updated_at = isset($item['updated_time']) ? convertZohoDatetimeToMysql($item['updated_time']) : null;  // Assuming 'updated_time' field exists
  /////////////////////////////////////////////////////
  if (isset($item['custom_fields']) && is_array($item['custom_fields']) && count($item['custom_fields']) > 0) {
    // Loop through each warehouse item
    foreach ($item['custom_fields'] as $item_customfields) {
        $api_name = isset($item_customfields['api_name']) ? $item_customfields['api_name'] : null;
        //echo $api_name;
if($api_name == "cf_variable_purchase_price")
{
    $cf_variable_purchase_price = isset($item_customfields['value']) ? $item_customfields['value'] : null;
    //echo $cf_variable_purchase_price;
}
if($api_name == "cf_part_type")
{
    $cf_part_type = isset($item_customfields['value']) ? $item_customfields['value'] : null;
    //echo $cf_part_type;
}
  
    }
}
  ////////////////////////////////////////////////////////
    
    if (isset($item['warehouses']) && is_array($item['warehouses']) && count($item['warehouses']) > 0) {
        // Loop through each warehouse item
        foreach ($item['warehouses'] as $item_warehouse) {
            $is_primary = isset($item_warehouse['is_primary']) ? $item_warehouse['is_primary'] : null; 
            //echo $is_primary;
            $warehouse_id = isset($item_warehouse['warehouse_id']) ? $item_warehouse['warehouse_id'] : null;
            $wh_name = isset($item_warehouse['warehouse_name']) ? $item_warehouse['warehouse_name'] : null;
           // echo "Warehouse ID: $warehouse_id, Warehouse Name: $warehouse_name\n";
        }
    } else {
       // echo "No warehouses data available for this item.\n";
    }

                 }
    // Check if the item already exists in your database
    $stmt = $conn->prepare("SELECT * FROM trading_items WHERE item_name = ?");
    $stmt->bind_param("s", $item_name);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        // Item doesn't exist, insert a new record
        $stmt = $conn->prepare("INSERT INTO trading_items (item_id, item_name, purchase_desc, purchase_price, selling_price, wh_name, part_type,category_name, item_brand, stock_on_hand, created_at, updated_at , factory_special_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssssssss",$item_id, $item_name, $item_desc, $purchase_price, $selling_price, $wh_name, $cf_part_type, $category_name, $item_brand, $stock_in_hand, $created_at, $updated_at,$cf_variable_purchase_price);
        $stmt->execute();
    }

        
        // Close statement
        $stmt->close();
    }
    
    // Close the database connection
    


// Function to convert Zoho datetime to MySQL datetime
function convertZohoDatetimeToMysql($zoho_datetime) {
    // Strip off the timezone part
    $datetime = preg_replace('/[+\-]\d{4}$/', '', $zoho_datetime);
    return $datetime; // Will return in 'YYYY-MM-DD HH:MM:SS' format
}



///////////////////////////////////////---------------------update-----/////////////////////////////////////////////////////////////////////////////


$update_url='https://www.zohoapis.in/books/v3/items?organization_id=60008266217&cf_modified_date=2025-01-21';
$pageup = 1;
 $per_pageup = 200;
 $up_items = [];
do {
    // cURL setup to fetch data from Zoho Books API
    $updatech = curl_init();
    $uppage_url = $update_url . "&page=$pageup&per_page=$per_pageup"; // Add pagination parameters
    curl_setopt($updatech, CURLOPT_URL, $uppage_url);
    curl_setopt($updatech, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($updatech, CURLOPT_HTTPHEADER, [
        'Authorization: Zoho-oauthtoken ' . $access_token
    ]);
   // echo $ch;
    curl_setopt($updatech, CURLOPT_CAINFO, 'C:\php-8.4.2\extras\ssl\cacert.pem');
    $upresponse = curl_exec($updatech);

    //echo $response;
    // Check for cURL errors
    if(curl_errno($updatech)) {
        echo 'cURL error: ' . curl_error($updatech);
        exit;
    }

    curl_close($updatech);

    // Decode JSON response
    $updata = json_decode($upresponse, true);

    if (isset($updata['items'])) {
        // Append invoices from this page to the all_invoices array
        $up_items = array_merge($up_items, $updata['items']);
        $pageup++; // Move to the next page
       //echo $all_invoices;
    } else {
        break; // No invoices found, exit loop
    }
} while (count($updata['items']) > 0); // Keep fetching until no invoices are returned
if (!empty($up_items))
 {
    foreach ($up_items as $itemup) 
    {
                           
        $upitem_id = $itemup['item_id']; 
        //echo $upitem_id;
        $urll = 'https://www.zohoapis.in/books/v3/items/'.$upitem_id.'?organization_id=60008266217';
        $ch = curl_init();
              // Add pagination parameters
                 curl_setopt($ch, CURLOPT_URL, $urll);
                 curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                 curl_setopt($ch, CURLOPT_HTTPHEADER, [
                     'Authorization: Zoho-oauthtoken ' . $access_token
                 ]);
                
                 curl_setopt($ch, CURLOPT_CAINFO, 'C:\php-8.4.2\extras\ssl\cacert.pem');
                 $responses = curl_exec($ch);
                 //echo $responses;
                 curl_close($ch);
                 $data = json_decode($responses, true);
                 if (isset($data['item']))
                 {
                  $item=$data['item'];
                  $item_id= isset($item['item_id']) ? $item['item_id'] : null;
                 $item_desc = isset($item['description']) ? $item['description'] : null;
                 $item_name = isset($item['name']) ? $item['name'] : null;
                 //echo $item_name.'/n';
                 $purchase_price = isset($item['purchase_rate']) ? $item['purchase_rate'] : 0.00;
                 $selling_price = isset($item['sales_rate']) ? $item['sales_rate'] : 0.00;
                 //$wh_name = isset($item['warehouse_name']) ? $item['warehouse_name'] : null;
                //  $part_type = isset($item['part_type']) ? $item['part_type'] : null;
                //  echo $part_type.'/n';
                 $category_name = isset($item['category_name']) ? $item['category_name'] : null;
                 $item_brand = isset($item['brand']) ? $item['brand'] : null;  // Assuming 'brand' field exists
                 $stock_in_hand = isset($item['stock_on_hand']) ? $item['stock_on_hand'] : 0;  // Assuming 'stock_in_hand' exists
                 $created_at = isset($item['created_time']) ? convertZohoDatetimeToMysql($item['created_time']) : null;  // Assuming 'created_time' field exists
                 //echo $created_at.'/n';
                 $updated_at = isset($item['updated_time']) ? convertZohoDatetimeToMysql($item['updated_time']) : null;  // Assuming 'updated_time' field exists
                 echo $updated_at.'/n';
                 /////////////////////////////////////////////////////
   if (isset($item['custom_fields']) && is_array($item['custom_fields']) && count($item['custom_fields']) > 0) {
     // Loop through each warehouse item
     foreach ($item['custom_fields'] as $item_customfields) {
         $api_name = isset($item_customfields['api_name']) ? $item_customfields['api_name'] : null;
         //echo $api_name;
 if($api_name == "cf_variable_purchase_price")
 {
     $cf_variable_purchase_price = isset($item_customfields['value']) ? $item_customfields['value'] : null;
     //echo $cf_variable_purchase_price;
 }
 if($api_name == "cf_part_type")
 {
     $cf_part_type = isset($item_customfields['value']) ? $item_customfields['value'] : null;
     //echo $cf_part_type;
 }
   
     }
 }
   ////////////////////////////////////////////////////////
    
   if (isset($item['warehouses']) && is_array($item['warehouses']) && count($item['warehouses']) > 0) {
    // Loop through each warehouse item
    foreach ($item['warehouses'] as $item_warehouse) {
        $is_primary = isset($item_warehouse['is_primary']) ? $item_warehouse['is_primary'] : null; 
        //echo $is_primary;
        $warehouse_id = isset($item_warehouse['warehouse_id']) ? $item_warehouse['warehouse_id'] : null;
        $wh_name = isset($item_warehouse['warehouse_name']) ? $item_warehouse['warehouse_name'] : null;
       // echo "Warehouse ID: $warehouse_id, Warehouse Name: $warehouse_name\n";
    }
} else {
    echo "No warehouses data available for this item.\n";
}
    }
}
 }

    $result = $conn->query("SELECT * FROM trading_items  WHERE item_id  = '$item_id'");
                                        if ($result->num_rows > 0)
                                               {
                                                
                                                             $stmt = $conn->prepare("UPDATE trading_items  SET  item_name = ?, purchase_desc = ?, purchase_price = ?, selling_price = ?, wh_name = ?, part_type = ?, category_name = ?, item_brand = ?, stock_on_hand = ?, created_at = ?, updated_at = ?, factory_special_price = ? WHERE item_id = ? ");
                                                             $stmt->bind_param("sssssssssssss", $item_name, $item_desc, $purchase_price, $selling_price, $wh_name, $cf_part_type, $category_name, $item_brand, $stock_in_hand, $created_at, $updated_at, $cf_variable_purchase_price,$item_id);
                                          
                                              if ($stmt->execute()) {
                                                    echo "item_name {$item_name} updated successfully!<br>";
                                                } else {
                                                    echo "Error updating invoice {$item_name}: " . $stmt->error . "<br>";
                                                 }



}   
  

///////////////////////////////////DELETE Function///////////////////////////////////////////////
$delete='https://www.zohoapis.in/books/v3/cm_deleted_item?organization_id=60008266217&cf_date=2025-01-21';
$pageupp = 1;
 $per_pageupp = 200;
 $del_items = [];
do {
    // cURL setup to fetch data from Zoho Books API
    $deltech = curl_init();
    $uppage_url = $delete . "&page=$pageupp&per_page=$per_pageupp"; // Add pagination parameters
    curl_setopt($deltech, CURLOPT_URL, $uppage_url);
    curl_setopt($deltech, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($deltech, CURLOPT_HTTPHEADER, [
        'Authorization: Zoho-oauthtoken ' . $access_token
    ]);
   // echo $ch;
    curl_setopt($deltech, CURLOPT_CAINFO, 'C:\php-8.4.2\extras\ssl\cacert.pem');
    $delresponse = curl_exec($deltech);

    //echo $response;
    // Check for cURL errors
    if(curl_errno($deltech)) {
        echo 'cURL error: ' . curl_error($deltech);
        exit;
    }

    curl_close($deltech);

    // Decode JSON response
    $deldata = json_decode($delresponse, true);

    if (isset($deldata['module_records'])) {
        // Append invoices from this page to the all_invoices array
        $del_items = array_merge($del_items, $deldata['module_records']);
        $pageupp++; // Move to the next page
       //echo $all_invoices;
    } else {
        break; // No invoices found, exit loop
    }
} while (count($deldata['module_records']) > 0); // Keep fetching until no invoices are returned
if (!empty($del_items))
 {
    foreach ($del_items as $itemdel) 
    {
                           
        $upitem_id = $itemdel['cf_item_id']; 
       // echo $upitem_id;
    }
}


    if ($upitem_id != null) {
        // Delete invoice if not in the API data
        $conn->query("DELETE FROM trading_items WHERE item_id = '$upitem_id'");
        echo "$upitem_id  ID deleted from database.<br>";
    }








/////////////////////////////////////////////////////////////////////////
$conn->close();
                                                
function isTokenExpired($token) {
    // Check if the token has been stored in the session
    session_start();
    
    if (!isset($_SESSION['last_token_refresh'])) {
        // If no token refresh timestamp exists, assume the token is expired
        return true;
    }

    $last_refresh_timestamp = $_SESSION['last_token_refresh'];
    $current_timestamp = time();

    // Access token typically expires in 1 hour, check if it has expired
    $token_expiry_time = 3600; // 1 hour in seconds

    if (($current_timestamp - $last_refresh_timestamp) > $token_expiry_time) {
        // Token has expired
        return true;
    }

    // Token is still valid
    return false;
}

 ?>
<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Zoho Books API URL for customers (adjust URL as necessary)
$url = 'https://www.zohoapis.in/books/v3/contacts?organization_id=60008266217&cf_created_date=2025-07-02&contact_type=customer';  // Adjust this URL for your actual Zoho API endpoint

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
$all_customers = [];

// Loop through all pages until no more customers are returned
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

    if (isset($data['contacts'])) {
        // Append customers from this page to the all_customers array
        $all_customers = array_merge($all_customers, $data['contacts']);
        $page++; // Move to the next page
    } else {
        break; // No customers found, exit loop
    }
} while (count($data['contacts']) > 0); // Keep fetching until no items are returned

// Now you have all customers in $all_customers
foreach ($all_customers as $customer) {
    // Extract the contact_id from the response
    $contact_id = isset($customer['contact_id']) ? $customer['contact_id'] : null;
    
    if ($contact_id) {
        // Fetch detailed data using the contact_id
        $contact_details_url = "https://www.zohoapis.in/books/v3/contacts/$contact_id?organization_id=60008266217"; // Your organization ID
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $contact_details_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Zoho-oauthtoken ' . $access_token
        ]);
        curl_setopt($ch, CURLOPT_CAINFO, 'C:\php-8.4.2\extras\ssl\cacert.pem');
        $response = curl_exec($ch);
        //print_r($response); // Print the raw API response
        // Check for cURL errors
        if (curl_errno($ch)) {
            echo 'cURL error: ' . curl_error($ch);
            exit;
        }

        curl_close($ch);

        // Decode the response to get the contact details
        $contact_data = json_decode($response, true);
        //print_r($contact_data); // Print the decoded JSON response
        
        if (isset($contact_data['contact'])) {
            $contact = $contact_data['contact'];
            // Extract the details for this specific customer using the contact_id
            $cust_name = isset($contact['contact_name']) ? $contact['contact_name'] : null;
            $gst_no = isset($contact['gst_no']) ? $contact['gst_no'] : null;
            $price_list = isset($contact['pricebook_name']) ? $contact['pricebook_name'] : null;
            $cust_warehouse = isset($contact['cf_warehouse']) ? $contact['cf_warehouse'] : null;
            $cust_market = isset($contact['cf_market']) ? $contact['cf_market'] : null;
            $cf_custo_code = isset($contact['cf_cust_code']) ? $contact['cf_cust_code'] : null;
            //print_r($cf_custo_code);
            
            // Billing details
            $billing_address = isset($contact['billing_address']['address']) ? $contact['billing_address']['address'] : null;
            $billing_attention = isset($contact['billing_address']['attention']) ? $contact['billing_address']['attention'] : null;
            $billing_city = isset($contact['billing_address']['city']) ? $contact['billing_address']['city'] : null;
            $billing_code = isset($contact['billing_address']['zip']) ? $contact['billing_address']['zip'] : null;
            $billing_country = isset($contact['billing_address']['country']) ? $contact['billing_address']['country'] : null;
            $billing_phone = isset($contact['billing_address']['phone']) ? $contact['billing_address']['phone'] : null;
            $billing_state = isset($contact['billing_address']['state']) ? $contact['billing_address']['state'] : null;
            $billing_street = isset($contact['billing_address']['street2']) ? $contact['billing_address']['street2'] : null;

            // Shipping details
            $shipping_address = isset($contact['shipping_address']['address']) ? $contact['shipping_address']['address'] : null;
            $shipping_attention = isset($contact['shipping_address']['attention']) ? $contact['shipping_address']['attention'] : null;
            $shipping_street = isset($contact['shipping_address']['street2']) ? $contact['shipping_address']['street2'] : null;
            $shipping_city = isset($contact['shipping_address']['city']) ? $contact['shipping_address']['city'] : null;
            $shipping_state = isset($contact['shipping_address']['state']) ? $contact['shipping_address']['state'] : null;
            $shipping_country = isset($contact['shipping_address']['country']) ? $contact['shipping_address']['country'] : null;
            $shipping_code = isset($contact['shipping_address']['zip']) ? $contact['shipping_address']['zip'] : null;
            $shipping_phone = isset($contact['shipping_address']['phone']) ? $contact['shipping_address']['phone'] : null;

            // Other details
            $created_at = isset($contact['created_time']) ? convertZohoDatetimeToMysql($contact['created_time']) : null;
            $updated_at = isset($contact['last_modified_time']) ? convertZohoDatetimeToMysql($contact['last_modified_time']) : null;
            $currency_code = isset($contact['currency_code']) ? $contact['currency_code'] : null;
            $payment_terms = isset($contact['payment_terms']) ? $contact['payment_terms'] : null;
            $place_of_supply = isset($contact['place_of_contact']) ? $contact['place_of_contact'] : null;
            $status = isset($contact['status']) ? $contact['status'] : null;

            //extra fields as per live

            $display_name = isset($contact['contact_name']) ? $contact['contact_name'] : null;
            $company_name = isset($contact['company_name']) ? $contact['company_name'] : null;
            $first_name = isset($contact['first_name']) ? $contact['first_name'] : null;
            $last_name = isset($contact['last_name']) ? $contact['last_name'] : null;
            $email_id = isset($contact['email']) ? $contact['email'] : null;
            $phone = isset($contact['phone']) ? $contact['phone'] : null;
            $mobile = isset($contact['mobile']) ? $contact['mobile'] : null;
            $contact_id = $contact['contact_id']; // Make sure this is explicitly set


            // Now insert or update into the database
            // Check if the customer already exists in your database (using cust_code)
            $stmt = $conn->prepare("SELECT * FROM trading_customers WHERE customer_id = ?");
            $stmt->bind_param("s", $contact['contact_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                echo "Inserting new customer: $cust_name\n";
                // Insert the customer into the database
                $stmt = $conn->prepare("INSERT INTO trading_customers 
    (customer_id, contact_name, cust_code, gst_no, price_list, cust_wh, cust_market, 
    billing_address, billing_attention, billing_city, billing_pincode, billing_country, 
    billing_phone, billing_state, billing_street, shipping_address, shipping_attention, 
    shipping_street, shipping_city, shipping_state, shipping_country, shipping_pincode, 
    shipping_phone, created_at, updated_at, currency_code, payment_terms, place_of_supply, status,
    display_name, company_name, first_name, last_name, email_id, phn_no, mobile_no) 
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");


                $stmt->bind_param("ssssssssssssssssssssssssssssssssssss",
    $contact_id, $cust_name, $cf_custo_code, $gst_no, $price_list, $cust_warehouse, $cust_market, 
    $billing_address, $billing_attention, $billing_city, $billing_code, $billing_country, 
    $billing_phone, $billing_state, $billing_street, $shipping_address, $shipping_attention, 
    $shipping_street, $shipping_city, $shipping_state, $shipping_country, $shipping_code, 
    $shipping_phone, $created_at, $updated_at, $currency_code, $payment_terms, $place_of_supply, $status,
    $display_name, $company_name, $first_name, $last_name, $email_id, $phone, $mobile
);

                $stmt->execute();
            } else {
                echo "Customer already exists: $cust_name\n";
            }

           $stmt->close();
        }
    }
}

///////////////////////////////////////update customer /////////////////////////////////////////////////////////////

$update_url='https://www.zohoapis.in/books/v3/customers?organization_id=60008266217&cf_modified_date=2025-07-02';
$pageupp = 1;
$per_pageupp = 200;
 $up_con = [];
do {
    // cURL setup to fetch data from Zoho Books API
    $updatech = curl_init();
    $uppage_url = $update_url . "&page=$pageupp&per_page=$per_pageupp"; // Add pagination parameters
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
   // print_r($updata);
    if (isset($updata['contacts'])) {
        // Append invoices from this page to the all_invoices array
        $up_con = array_merge($up_con, $updata['contacts']);
        $pageupp++; // Move to the next page
       //echo $all_invoices;
    } else {
        break; // No invoices found, exit loop
    }
} while (count($updata['contacts']) > 0); // Keep fetching until no invoices are returned
if (!empty($up_con))
 {
    foreach ($up_con as $up_contacts) 
    {
        $contact_idd = $up_contacts['contact_id'];
        //print_r($contact_idd);

        if ($contact_id) {
            // Fetch detailed data using the contact_id
            $contact_details_url = "https://www.zohoapis.in/books/v3/contacts/$contact_idd?organization_id=60008266217"; // Your organization ID
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $contact_details_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Zoho-oauthtoken ' . $access_token
            ]);
            curl_setopt($ch, CURLOPT_CAINFO, 'C:\php-8.4.2\extras\ssl\cacert.pem');
            $response = curl_exec($ch);
            //print_r($response); // Print the raw API response
            // Check for cURL errors
            if (curl_errno($ch)) {
                echo 'cURL error: ' . curl_error($ch);
                exit;
            }
    
            curl_close($ch);
    
            // Decode the response to get the contact details
            $contact_data = json_decode($response, true);
           //print_r($contact_data); // Print the decoded JSON response
            
            if (isset($contact_data['contact'])) {
                $contact = $contact_data['contact'];
                
                    $cust_name = isset($contact['contact_name']) ? $contact['contact_name'] : null;
                // Extract the details for this specific customer using the contact_id
                $contact_id = isset($contact['contact_id']) ? $contact['contact_id'] : null;
                //print_r($contact_id);
                $gst_no = isset($contact['gst_no']) ? $contact['gst_no'] : null;
                $price_list = isset($contact['pricebook_name']) ? $contact['pricebook_name'] : null;
                $cust_warehouse = isset($contact['cf_warehouse']) ? $contact['cf_warehouse'] : null;
                $cust_market = isset($contact['cf_market']) ? $contact['cf_market'] : null;
                $cf_custo_code = isset($contact['cf_cust_code']) ? $contact['cf_cust_code'] : null;
               // print_r($cf_custo_code);
    
                // Billing details
                $billing_address = isset($contact['billing_address']['address']) ? $contact['billing_address']['address'] : null;
                $billing_attention = isset($contact['billing_address']['attention']) ? $contact['billing_address']['attention'] : null;
                $billing_city = isset($contact['billing_address']['city']) ? $contact['billing_address']['city'] : null;
                $billing_code = isset($contact['billing_address']['zip']) ? $contact['billing_address']['zip'] : null;
                $billing_country = isset($contact['billing_address']['country']) ? $contact['billing_address']['country'] : null;
                $billing_phone = isset($contact['billing_address']['phone']) ? $contact['billing_address']['phone'] : null;
                $billing_state = isset($contact['billing_address']['state']) ? $contact['billing_address']['state'] : null;
                $billing_street = isset($contact['billing_address']['street2']) ? $contact['billing_address']['street2'] : null;
    
                // Shipping details
                $shipping_address = isset($contact['shipping_address']['address']) ? $contact['shipping_address']['address'] : null;
                $shipping_attention = isset($contact['shipping_address']['attention']) ? $contact['shipping_address']['attention'] : null;
                $shipping_street = isset($contact['shipping_address']['street2']) ? $contact['shipping_address']['street2'] : null;
                $shipping_city = isset($contact['shipping_address']['city']) ? $contact['shipping_address']['city'] : null;
                $shipping_state = isset($contact['shipping_address']['state']) ? $contact['shipping_address']['state'] : null;
                $shipping_country = isset($contact['shipping_address']['country']) ? $contact['shipping_address']['country'] : null;
                $shipping_code = isset($contact['shipping_address']['zip']) ? $contact['shipping_address']['zip'] : null;
                $shipping_phone = isset($contact['shipping_address']['phone']) ? $contact['shipping_address']['phone'] : null;
    
                // Other details
                $created_at = isset($contact['created_time']) ? convertZohoDatetimeToMysql($contact['created_time']) : null;
                $updated_at = isset($contact['last_modified_time']) ? convertZohoDatetimeToMysql($contact['last_modified_time']) : null;
                $currency_code = isset($contact['currency_code']) ? $contact['currency_code'] : null;
                $payment_terms = isset($contact['payment_terms']) ? $contact['payment_terms'] : null;
                $place_of_supply = isset($contact['place_of_contact']) ? $contact['place_of_contact'] : null;
                $status = isset($contact['status']) ? $contact['status'] : null;

                //extra fields as per live

            $display_name = isset($contact['contact_name']) ? $contact['contact_name'] : null;
            $company_name = isset($contact['company_name']) ? $contact['company_name'] : null;
            $first_name = isset($contact['first_name']) ? $contact['first_name'] : null;
            $last_name = isset($contact['last_name']) ? $contact['last_name'] : null;
            $email_id = isset($contact['email']) ? $contact['email'] : null;
            $phone = isset($contact['phone']) ? $contact['phone'] : null;
            $mobile = isset($contact['mobile']) ? $contact['mobile'] : null;
            $contact_id = $contact['contact_id']; // Make sure this is explicitly set
  
                
        
               
 $result = $conn->query("SELECT * FROM trading_customers WHERE customer_id = '$contact_id'");
            if ($result->num_rows > 0) {
                
                // Prepare the statement if the record exists
                $stmt = $conn->prepare("UPDATE trading_customers SET 
    contact_name = ?, 
    cust_code = ?, 
    gst_no = ?, 
    price_list = ?, 
    cust_wh = ?, 
    cust_market = ?, 
    billing_address = ?, 
    billing_attention = ?, 
    billing_city = ?, 
    billing_pincode = ?, 
    billing_country = ?, 
    billing_phone = ?, 
    billing_state = ?, 
    billing_street = ?, 
    shipping_address = ?, 
    shipping_attention = ?, 
    shipping_street = ?, 
    shipping_city = ?, 
    shipping_state = ?, 
    shipping_country = ?, 
    shipping_pincode = ?, 
    shipping_phone = ?, 
    created_at = ?, 
    updated_at = ?, 
    currency_code = ?, 
    payment_terms = ?, 
    place_of_supply = ?, 
    status = ?, 
    display_name = ?, 
    company_name = ?, 
    first_name = ?, 
    last_name = ?, 
    email_id = ?, 
    phn_no = ?, 
    mobile_no = ? 
    WHERE customer_id = ?");

                // Check if preparation was successful
                if ($stmt) {
                    $stmt->bind_param("ssssssssssssssssssssssssssssssssssss",
    $contact_id, $cust_name, $cf_custo_code, $gst_no, $price_list, $cust_warehouse, $cust_market, 
    $billing_address, $billing_attention, $billing_city, $billing_code, $billing_country, 
    $billing_phone, $billing_state, $billing_street, $shipping_address, $shipping_attention, 
    $shipping_street, $shipping_city, $shipping_state, $shipping_country, $shipping_code, 
    $shipping_phone, $created_at, $updated_at, $currency_code, $payment_terms, $place_of_supply, $status,
    $display_name, $company_name, $first_name, $last_name, $email_id, $phone, $mobile
);
                    
                    if ($stmt->execute()) {
                        echo "Invoice {$contact_id} updated successfully!<br>";
                    } else {
                        echo "Error updating invoice {$contact_id}: " . $stmt->error . "<br>";
                    }
                    $stmt->close();
                } else {
                    echo "Error preparing statement: " . $conn->error . "<br>";
                }
           }



    }          

    }
}
}

///////////////////////////delete customers////////////////////////////////////////////////

$delete='https://www.zohoapis.in/books/v3/cm_deleted_customer?organization_id=60008266217&cf_date=2025-07-02';
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
$upitem_id = null; // Initialize before use
if (!empty($del_items))
 {
    foreach ($del_items as $itemdel) 
    {
                           
        $upitem_id = $itemdel['cf_contact_id']; 
    }}

    if ($upitem_id != null) {
        // Delete invoice if not in the API data
        $conn->query("DELETE FROM trading_customers WHERE customer_id = '$upitem_id'");
        echo "$upitem_id  ID deleted from database.<br>";
    }













/////////////////////////////////////////////////////////////////
// Close the database connection
$conn->close();

// Function to convert Zoho datetime to MySQL datetime
function convertZohoDatetimeToMysql($zoho_datetime) {
    // Strip off the timezone part
    $datetime = preg_replace('/[+\-]\d{4}$/', '', $zoho_datetime);
    return $datetime; // Will return in 'YYYY-MM-DD HH:MM:SS' format
}

function isTokenExpired($token) {
    session_start();
    
    if (!isset($_SESSION['last_token_refresh'])) {
        return true;
    }

    $last_refresh_timestamp = $_SESSION['last_token_refresh'];
    $current_timestamp = time();

    $token_expiry_time = 3600; // 1 hour in seconds

    if (($current_timestamp - $last_refresh_timestamp) > $token_expiry_time) {
        return true;
    }

    return false;
}
?>

<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Zoho Books API URL for vendors (adjust URL as necessary)
$url = 'https://www.zohoapis.in/books/v3/contacts?organization_id=60008266217&cf_created_date=2025-01-23&contact_type=vendor';  // Adjust this URL for your actual Zoho API endpoint

// Your OAuth token and refresh token
$access_token = '1000.e7b452e37417ddef932249e97f6c6437.0d8704c36a45e18c7f0c8dd385102c96';
$refresh_token = '1000.ed6fec2963d2b763e454264e59085d29.9fc6b5936996f531d4136f5ada280698'; // Update with your actual refresh token

// MySQL connection setup
$servername = "190.92.174.90";
$username = "tanishkaenter_user"; // MySQL username
$password = "Tanishka_User123!"; // MySQL password
$dbname = "tanishkaenter_test";

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
        'client_id' => '1000.GO9NDA38HWZ4N5ZHLOXYM6XH1F4WVA', // Your client ID
        'client_secret' => 'ed089fb96e6269ee0c08b6f7f1bae44272ea1b1f9f', // Your client secret
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
$all_vendors = [];
$invoice_details = [];

// Loop through all pages until no more vendors are returned
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
        // Append vendors from this page to the all_vendors array
        $all_vendors = array_merge($all_vendors, $data['contacts']);
        $page++; // Move to the next page
    } else {
        break; // No vendors found, exit loop
    }
} while (count($data['contacts']) > 0); // Keep fetching until no items are returned

// Now you have all vendors in $all_vendors
foreach ($all_vendors as $vendor) {
    // Extract the vendor_id from the response
    $vendor_id = isset($vendor['contact_id']) ? $vendor['contact_id'] : null;
   //echo $vendor_id;
    if ($vendor_id) {
        // Fetch detailed data using the vendor_id
        $vendor_details_url = "https://www.zohoapis.in/books/v3/contacts/$vendor_id?organization_id=60008266217&contact_type=vendor"; // Your organization ID
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $vendor_details_url);
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

        // Decode the response to get the vendor details
        $vendor_data = json_decode($response, true);
        //print_r($vendor_data);
       
        if (isset($vendor_data['contact'])) {
            $vendor = $vendor_data['contact'];
            //$vendor_id= isset($vendor['contact_id']) ? $vendor['contact_id'] : null;
            // Extract the details for this specific vendor using the vendor_id
            $beneficiary_name = isset($vendor['beneficiary_name']) ? $vendor['beneficiary_name'] : null;
            $gst_no = isset($vendor['gst_no']) ? $vendor['gst_no'] : null;
            $price_list = isset($vendor['pricebook_name']) ? $vendor['pricebook_name'] : null;
            $source_of_supply = isset($vendor['place_of_contact']) ? $vendor['place_of_contact'] : null;
            

            $contact_persons = is_array($vendor['contact_persons']) ? json_encode($vendor['contact_persons']) : $vendor['contact_persons'];
            //print_r($contact_persons);
           if (is_array($vendor['contact_persons'])) 
                 {
            $contact_fname = isset($vendor['first_name']) ? $vendor['first_name'] : null; 
            $contact_lname = isset($vendor['last_name']) ? $vendor['last_name'] : null; 
            $contact_mobile_no = isset($vendor['mobile']) ? $vendor['mobile'] : null;
            $contact_phn_no = isset($vendor['phone']) ? $vendor['phone'] : null;       
            $contact_email = isset($vendor['email']) ? $vendor['email'] : null;
                 }
                 //$invoice_details= is_array($vendor['bank_accounts']) ? json_encode($vendor['bank_accounts']) : $vendor['contact_persons'];
                 //print_r($bank_accounts);
                 
                 if (isset($vendor['bank_accounts']) && !empty($vendor['bank_accounts'])) {
                    foreach ($vendor['bank_accounts'] as $vendors) {
                       // print_r($vendors);
                         $invoice_details[] = [
                             'beneficiary_name' => $vendors['beneficiary_name'] ?? 'N/A', // Replace with a default if missing
                             'account_number' => $vendors['account_number'] ?? 'N/A', // Replace with a default if missing
                             'routing_number' => $vendors['routing_number'] ?? 'N/A', // Replace with a default if missing
                             'bank_name' => $vendors['bank_name'] ?? 'N/A', // Replace with a default if missing
                            
                         ];                    
                 } 
                
                }
                else {
                    // When there are no invoices
                    $invoice_details[] = [
                        'beneficiary_name' => 'N/A',
                        'account_number' => 'N/A',
                        'routing_number' => 'N/A',
                        'bank_name' => 'N/A'
                    ];
                }
                $last_value = end($invoice_details); 
                //print_r($last_value);


            $created_at = isset($vendor['created_time']) ? convertZohoDatetimeToMysql($vendor['created_time']) : null;
            $updated_at = isset($vendor['last_modified_time']) ? convertZohoDatetimeToMysql($vendor['last_modified_time']) : null;
            $currency_code = isset($vendor['currency_code']) ? $vendor['currency_code'] : null;

            $billing_address = isset($vendor['billing_address']['address']) ? $vendor['billing_address']['address'] : null;
            $billing_attention = isset($vendor['billing_address']['attention']) ? $vendor['billing_address']['attention'] : null;
            $billing_city = isset($vendor['billing_address']['city']) ? $vendor['billing_address']['city'] : null;
            $billing_code = isset($vendor['billing_address']['zip']) ? $vendor['billing_address']['zip'] : null;
            $billing_country = isset($vendor['billing_address']['country']) ? $vendor['billing_address']['country'] : null;
            $billing_phone = isset($vendor['billing_address']['phone']) ? $vendor['billing_address']['phone'] : null;
            $billing_state = isset($vendor['billing_address']['state']) ? $vendor['billing_address']['state'] : null;
            $billing_street = isset($vendor['billing_address']['street2']) ? $vendor['billing_address']['street2'] : null;
           


            $vendor_code = isset($vendor['cf_cust_code']) ? $vendor['cf_cust_code'] : null;
            $vendor_name = isset($vendor['contact_name']) ? $vendor['contact_name'] : null;
            $vendor_id = isset($vendor['contact_id']) ? $vendor['contact_id'] : null;
            $vendor_warehouse = isset($vendor['cf_warehouse']) ? $vendor['cf_warehouse'] : null;
            $vendor_status = isset($vendor['status']) ? $vendor['status'] : null;
            $udyam_no = isset($vendor['udyam_reg_no']) ? $vendor['udyam_reg_no'] : null;
            $udyam_type = isset($vendor['msme_type']) ? $vendor['msme_type'] : null;

            // Now insert or update into the database
            // Check if the vendor already exists in your database (using vendor_code)
            $stmt = $conn->prepare("SELECT * FROM fitok_all_vendors WHERE vendor_code = ?");
            $stmt->bind_param("s", $vendor_code);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                echo "Inserting new vendor: $vendor_name\n";
                // Insert the vendor into the database
                $stmt = $conn->prepare("INSERT INTO fitok_all_vendors 
                (vendor_id,beneficiary_name, vendor_code, gst_no, price_list, source_of_supply, vendor_warehouse, vendor_status, vendor_name,
                contact_fname, contact_lname, contact_mobile_no, contact_phn_no, contatct_email_id,  
                billing_address, billing_attention, billing_city, billing_code, billing_country, 
                billing_phone, billing_state, billing_street, created_at, updated_at, currency_code,
                vendor_bank_acct_no, vendor_bank_code, vendor_bank_name, udyam_no, udyam_type)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            
             

            $stmt->bind_param("ssssssssssssssssssssssssssssss", 
                $vendor_id, $last_value['beneficiary_name'], $vendor_code, $gst_no, $price_list, $source_of_supply, $vendor_warehouse, $vendor_status,  $vendor_name,
                $contact_fname, $contact_lname, $contact_mobile_no, $contact_phn_no, $contact_email,  
                $billing_address, $billing_attention, $billing_city, $billing_code, $billing_country, 
                $billing_phone, $billing_state, $billing_street, $created_at, $updated_at, $currency_code,
                $last_value['account_number'], $last_value['routing_number'], $last_value['bank_name'], $udyam_no, $udyam_type
            );
            
                $stmt->execute();
            } else {
                echo "Vendor already exists: $vendor_name\n";
            }

            $stmt->close();
        }
    }
}
////////////////////////////////////////////////////update vendors////////////////////////////////////////////
$update_url='https://www.zohoapis.in/books/v3/contacts?organization_id=60008266217&cf_modified_date=2025-01-28&contact_type=vendor';
$pageupp = 1;
$per_pageupp = 200;
 $up_cp = [];
 $up_invoices = [];
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
        $up_cp = array_merge($up_cp, $updata['contacts']);
        $pageupp++; // Move to the next page
       //echo $all_invoices;
    } else {
        break; // No invoices found, exit loop
    }
} while (count($updata['contacts']) > 0); // Keep fetching until no invoices are returned
if (!empty($up_cp))
 {
    foreach ($up_cp as $up_payment) 
    {
        $up_payment_id = $up_payment['contact_id'];


        if ($up_payment_id) {
            // Fetch detailed data using the vendor_id
            $vendor_details_url = "https://www.zohoapis.in/books/v3/contacts/$up_payment_id?organization_id=60008266217&contact_type=vendor"; // Your organization ID
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $vendor_details_url);
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
    
            // Decode the response to get the vendor details
            $vendor_data = json_decode($response, true);
           
            if (isset($vendor_data['contact'])) {
                $vendor = $vendor_data['contact'];
                //$vendor_id= isset($vendor['contact_id']) ? $vendor['contact_id'] : null;
                // Extract the details for this specific vendor using the vendor_id
                $beneficiary_name = isset($vendor['beneficiary_name']) ? $vendor['beneficiary_name'] : null;
                $gst_no = isset($vendor['gst_no']) ? $vendor['gst_no'] : null;
                $price_list = isset($vendor['pricebook_name']) ? $vendor['pricebook_name'] : null;
                $source_of_supply = isset($vendor['place_of_contact']) ? $vendor['place_of_contact'] : null;
                
    
                $contact_persons = is_array($vendor['contact_persons']) ? json_encode($vendor['contact_persons']) : $vendor['contact_persons'];
                //print_r($contact_persons);
               if (is_array($vendor['contact_persons'])) 
                     {
                $contact_fname = isset($vendor['first_name']) ? $vendor['first_name'] : null; 
                $contact_lname = isset($vendor['last_name']) ? $vendor['last_name'] : null; 
                $contact_mobile_no = isset($vendor['mobile']) ? $vendor['mobile'] : null;
                $contact_phn_no = isset($vendor['phone']) ? $vendor['phone'] : null;       
                $contact_email = isset($vendor['email']) ? $vendor['email'] : null;
                     }
                     $bank_accounts = is_array($vendor['bank_accounts']) ? json_encode($vendor['bank_accounts']) : $vendor['contact_persons'];
                     //print_r($bank_accounts);
                     
                     if (isset($vendor['bank_accounts']) && !empty($vendor['bank_accounts'])) {
                        foreach ($vendor['bank_accounts'] as $vendors) {
                           // print_r($vendors);
                             $up_invoices[] = [
                                 'beneficiary_name' => $vendors['beneficiary_name'] ?? 'N/A', // Replace with a default if missing
                                 'account_number' => $vendors['account_number'] ?? 'N/A', // Replace with a default if missing
                                 'routing_number' => $vendors['routing_number'] ?? 'N/A', // Replace with a default if missing
                                 'bank_name' => $vendors['bank_name'] ?? 'N/A', // Replace with a default if missing
                                
                             ];                    
                     } 
                     //print_r($invoice_details);
                    }
                    else {
                        // When there are no invoices
                        $up_invoices[] = [
                            'beneficiary_name' => 'N/A',
                            'account_number' => 'N/A',
                            'routing_number' => 'N/A',
                            'bank_name' => 'N/A'
                        ];
                    }
                    $last_valuee = end($up_invoices); 
                    //print_r($up_invoices);
    
    
                $created_at = isset($vendor['created_time']) ? convertZohoDatetimeToMysql($vendor['created_time']) : null;
                $updated_at = isset($vendor['last_modified_time']) ? convertZohoDatetimeToMysql($vendor['last_modified_time']) : null;
                $currency_code = isset($vendor['currency_code']) ? $vendor['currency_code'] : null;
    
                $billing_address = isset($vendor['billing_address']['address']) ? $vendor['billing_address']['address'] : null;
                $billing_attention = isset($vendor['billing_address']['attention']) ? $vendor['billing_address']['attention'] : null;
                $billing_city = isset($vendor['billing_address']['city']) ? $vendor['billing_address']['city'] : null;
                $billing_code = isset($vendor['billing_address']['zip']) ? $vendor['billing_address']['zip'] : null;
                $billing_country = isset($vendor['billing_address']['country']) ? $vendor['billing_address']['country'] : null;
                $billing_phone = isset($vendor['billing_address']['phone']) ? $vendor['billing_address']['phone'] : null;
                $billing_state = isset($vendor['billing_address']['state']) ? $vendor['billing_address']['state'] : null;
                $billing_street = isset($vendor['billing_address']['street2']) ? $vendor['billing_address']['street2'] : null;
               
    
    
                $vendor_code = isset($vendor['cf_cust_code']) ? $vendor['cf_cust_code'] : null;
                $vendor_name = isset($vendor['contact_name']) ? $vendor['contact_name'] : null;
                $vendor_id = isset($vendor['contact_id']) ? $vendor['contact_id'] : null;
                $vendor_warehouse = isset($vendor['cf_warehouse']) ? $vendor['cf_warehouse'] : null;
                $vendor_status = isset($vendor['status']) ? $vendor['status'] : null;
                $udyam_no = isset($vendor['udyam_reg_no']) ? $vendor['udyam_reg_no'] : null;
                $udyam_type = isset($vendor['msme_type']) ? $vendor['msme_type'] : null;




            $result = $conn->query("SELECT * FROM fitok_all_vendors WHERE vendor_id = '$vendor_id'");
            if ($result->num_rows > 0) {
                // Prepare the statement if the record exists
                $stmt = $conn->prepare("UPDATE fitok_all_vendors SET 
                beneficiary_name= ?, vendor_code= ?, gst_no= ?, price_list= ?, source_of_supply= ?, vendor_warehouse= ?, vendor_status= ?, vendor_name= ?,
                 contact_fname= ?, contact_lname= ?, contact_mobile_no= ?, contact_phn_no= ?, contatct_email_id= ?,  
                 billing_address= ?, billing_attention= ?, billing_city= ?, billing_code= ?, billing_country= ?, 
                 billing_phone= ?, billing_state= ?, billing_street= ?, created_at= ?, updated_at= ?, currency_code= ?,
                 vendor_bank_acct_no= ?, vendor_bank_code= ?, vendor_bank_name= ?, udyam_no=?, udyam_type= ? WHERE vendor_id = ?");
                  //$invoices = $up_invoices[1];
                  //print_r($invoices);          
                
                // Check if preparation was successful
                if ($stmt) {
                    $stmt->bind_param("ssssssssssssssssssssssssssssss",
                    $last_valuee['beneficiary_name'], $vendor_code, $gst_no, $price_list, $source_of_supply, $vendor_warehouse, $vendor_status,  $vendor_name,
                    $contact_fname, $contact_lname, $contact_mobile_no, $contact_phn_no, $contact_email,  
                    $billing_address, $billing_attention, $billing_city, $billing_code, $billing_country, 
                    $billing_phone, $billing_state, $billing_street, $created_at, $updated_at, $currency_code,
                    $last_valuee['account_number'], $last_valuee['routing_number'], $last_valuee['bank_name'], $udyam_no, $udyam_type,$vendor_id);
             
                    
                    
                    if ($stmt->execute()) {
                        echo "vendor {$vendor_id} updated successfully!<br>";
                    } else {
                        echo "Error updating invoice {$vendor_id}: " . $stmt->error . "<br>";
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
/////////////////////////////////////////deleted vendors/////////////////////////////////////////////////////////////////        
$delete='https://www.zohoapis.in/books/v3/cm_deleted_vendor?organization_id=60008266217&cf_date=2025-01-27';
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
                           
        $upitem_id = $itemdel['cf_contact_id']; 
        //print_r($upitem_id);
    }
}


    if ($upitem_id != null) {
        // Delete invoice if not in the API data

        $conn->query("DELETE FROM fitok_all_vendors WHERE vendor_id = '$upitem_id'");
        echo "$upitem_id  ID deleted from database.<br>";
    }



////////////////////////////////////////////////////////////////////////////////////
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


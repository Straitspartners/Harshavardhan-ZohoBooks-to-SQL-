<?php
// Zoho Books API URL for vendor payments
$url = 'https://www.zohoapis.in/books/v3/vendorpayments?organization_id=60008266217&date=2025-01-15';

// Your OAuth token and refresh token
$access_token = '1000.e7b452e37417ddef932249e97f6c6437.0d8704c36a45e18c7f0c8dd385102c96';
$refresh_token = '1000.ed6fec2963d2b763e454264e59085d29.9fc6b5936996f531d4136f5ada280698';

// MySQL connection setup
$servername = "190.92.174.90";
$username = "tanishkaenter_user"; 
$password = "Tanishka_User123!"; 
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
        'client_id' => '1000.GO9NDA38HWZ4N5ZHLOXYM6XH1F4WVA', 
        'client_secret' => 'ed089fb96e6269ee0c08b6f7f1bae44272ea1b1f9f', 
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
        $_SESSION['last_token_refresh'] = time(); 
        return $response_data['access_token'];
    }
    
    die('Error: Could not refresh the access token.');
}

// Refresh the access token if expired
if (isTokenExpired($access_token)) {
    $access_token = refreshAccessToken($refresh_token);
}

// Set up initial page and per_page (pagination)
$page = 1;
$per_page = 200;
$all_payments = [];

// Loop through all pages until no more payments are returned
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
    if(curl_errno($ch)) {
        echo 'cURL error: ' . curl_error($ch);
        exit;
    }

    curl_close($ch);

    // Decode JSON response
    $data = json_decode($response, true);

    if (isset($data['vendorpayments'])) {
        // Append payments from this page to the all_payments array
        $all_payments = array_merge($all_payments, $data['vendorpayments']);
        $page++; 
    } else {
        break; 
    }
} while (count($data['vendorpayments']) > 0);

function convertZohoDatetimeToMysql($zoho_datetime) {
    // Strip off the timezone part
    $datetime = preg_replace('/[+\-]\d{4}$/', '', $zoho_datetime);
    return $datetime;
}

// Now you have all payments in $all_payments
if (!empty($all_payments)) {
    foreach ($all_payments as $payment) {
        $payment_id = $payment['payment_id']; // Get payment_id
        // Fetch additional payment details by using the payment_id
        $payment_details_url = "https://www.zohoapis.in/books/v3/vendorpayments/{$payment_id}?organization_id=60008266217"; // URL to get details of a single payment
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $payment_details_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Zoho-oauthtoken ' . $access_token
        ]);
        curl_setopt($ch, CURLOPT_CAINFO, 'C:\php-8.4.2\extras\ssl\cacert.pem');
        $response_details = curl_exec($ch);
        curl_close($ch);

        // Decode JSON response to get bills details
        $payment_details = json_decode($response_details, true);

        // Check if 'bills' array is available in the response
        $bills = isset($payment_details['vendorpayment']['bills']) ? $payment_details['vendorpayment']['bills'] : [];

        // Extract bill details
        if (!empty($bills)) {
            foreach ($bills as $bill) {
                $bill_no = $bill['bill_number'];
                $bill_date = $bill['date'];
                $bill_amt = $bill['total'];

                // Now you can insert these bill details along with payment information into the database
                $vendor_name = $payment['vendor_name'];
                $vendor_payment_date = $payment['date'];
                $vendor_payment_amt = isset($payment['amount']) ? $payment['amount'] : 0.00;
                $currency_code = isset($payment['currency_code']) ? $payment['currency_code'] : '';
                $vendor_payment_mode = $payment['payment_mode'];
                $vendor_payment_desc = isset($payment['description']) ? $payment['description'] : 'No description available';
                $exchange_rate = isset($payment['exchange_rate']) ? $payment['exchange_rate'] : 1.00; // Default if missing
                $bank_charges = isset($payment['cf_bank_charges_unformatted']) ? $payment['cf_bank_charges_unformatted'] : 0.00; 
                $foreign_bank_charges = isset($payment['cf_foreign_bank_charges_unformatted']) ? $payment['cf_foreign_bank_charges_unformatted'] : null;
                //print_r($foreign_bank_charges);
                $reference_no = isset($payment['reference_number']) ? $payment['reference_number'] : null;
                $payment_type = $payment['payment_mode'];
                
                 $result = $conn->query("SELECT * FROM fitok_vendor_payment WHERE vendor_payment_no = '{$payment['payment_number']}' AND bill_no = '{$bill_no}'");
                if ($result->num_rows == 0) {
                    // Insert new payment into the database
                    $stmt = $conn->prepare("INSERT INTO fitok_vendor_payment (bank_charges, bill_amt, bill_date, bill_no, currency_code, exchange_rate, foreign_bank_charges, payment_type, reference_no, vendor_name, vendor_payment_amt, vendor_payment_date, vendor_payment_desc, vendor_payment_mode, vendor_payment_no) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

                    $stmt->bind_param("sssssdsssssssss", $bank_charges, $bill_amt, $bill_date, $bill_no, $currency_code, $exchange_rate, $foreign_bank_charges, $payment_type, $reference_no, $vendor_name, $vendor_payment_amt, $vendor_payment_date, $vendor_payment_desc, $vendor_payment_mode, $payment['payment_number']);

                    if ($stmt->execute()) {
                        echo "Payment {$payment['payment_number']} with Bill No: {$bill_no} inserted successfully!<br>";
                    } else {
                        echo "Error inserting payment {$payment['payment_number']} with Bill No: {$bill_no}: " . $stmt->error . "<br>";
                    }
                    $stmt->close();
                } else {
                    echo "Payment {$payment['payment_number']} with Bill No: {$bill_no} already exists in the database.<br>";
                }
                
            }
        }
    }
 } 
else {
    echo "No payments found or error fetching data.";
}

// Close the database connection
// 

////////////////////////////////////////////////////////update code//////////////////////////////////////////////

$update_url='https://www.zohoapis.in/books/v3/vendorpayments?organization_id=60008266217&cf_modified_date=2025-01-24';
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
    if (isset($updata['vendorpayments'])) {
        // Append invoices from this page to the all_invoices array
        $up_cp = array_merge($up_cp, $updata['vendorpayments']);
        $pageupp++; // Move to the next page
       //echo $all_invoices;
    } else {
        break; // No invoices found, exit loop
    }
} while (count($updata['vendorpayments']) > 0); // Keep fetching until no invoices are returned
if (!empty($up_cp))
 {
    foreach ($up_cp as $up_payment) 
    {
        $up_payment_id = $up_payment['payment_id'];
        $payment_details_urll = "https://www.zohoapis.in/books/v3/vendorpayments/{$up_payment_id}?organization_id=60008266217"; // URL to get details of a single payment
        
        $chs = curl_init();
        curl_setopt($chs, CURLOPT_URL, $payment_details_urll);
        curl_setopt($chs, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($chs, CURLOPT_HTTPHEADER, [
            'Authorization: Zoho-oauthtoken ' . $access_token
        ]);
        curl_setopt($chs, CURLOPT_CAINFO, 'C:\php-8.4.2\extras\ssl\cacert.pem');
        $response_detailss = curl_exec($chs);
        curl_close($chs);

        // Decode JSON response to get bills details
        $payment_detailss = json_decode($response_detailss, true);

        // Check if 'bills' array is available in the response
        $billss = isset($payment_detailss['vendorpayment']['bills']) ? $payment_detailss['vendorpayment']['bills'] : [];

        // Extract bill details
        if (!empty($billss)) {
            foreach ($billss as $bill) {
                $bill_no = $bill['bill_number'];
                $bill_date = $bill['date'];
                $bill_amt = $bill['total'];
                //  print_r($bill_amt);
                // Now you can insert these bill details along with payment information into the database
                $vendor_name = $payment['vendor_name'];
                $payment_number = $payment['payment_number'];
                $vendor_payment_date = $payment['date'];
                $vendor_payment_amt = isset($payment['amount']) ? $payment['amount'] : 0.00;
                $currency_code = isset($payment['currency_code']) ? $payment['currency_code'] : '';
                $vendor_payment_mode = $payment['payment_mode'];
                $vendor_payment_desc = isset($payment['description']) ? $payment['description'] : 'No description available';
                $exchange_rate = isset($payment['exchange_rate']) ? $payment['exchange_rate'] : 1.00; // Default if missing
                $bank_charges = isset($payment['cf_bank_charges_unformatted']) ? $payment['cf_bank_charges_unformatted'] : 0.00; 
                $foreign_bank_charges = isset($payment['cf_foreign_bank_charges_unformatted']) ? $payment['cf_foreign_bank_charges_unformatted'] : null;
                $reference_no = isset($payment['reference_number']) ? $payment['reference_number'] : null;
                $payment_type = $payment['payment_mode'];



                 $result = $conn->query("SELECT * FROM fitok_vendor_payment WHERE vendor_payment_no = '$payment_number' AND  bill_no='$bill_no' ");
            if ($result->num_rows > 0) {
                // Prepare the statement if the record exists
                $stmt = $conn->prepare("UPDATE fitok_vendor_payment SET bank_charges = ?, bill_amt = ?, bill_date = ?, currency_code = ?, exchange_rate = ?, foreign_bank_charges = ?, payment_type = ?, reference_no = ?, vendor_name = ?, vendor_payment_amt = ?, vendor_payment_date = ?, vendor_payment_desc = ?, vendor_payment_mode = ?   WHERE vendor_payment_no = ? AND bill_no = ?");
               
                // Check if preparation was successful
                if ($stmt) {
                    $stmt->bind_param("ssssdssssssssss",$bank_charges, $bill_amt, $bill_date, $currency_code, $exchange_rate, $foreign_bank_charges, $payment_type, $reference_no, $vendor_name, $vendor_payment_amt, $vendor_payment_date, $vendor_payment_desc, $vendor_payment_mode, $payment_number, $bill_no);
                    
                    if ($stmt->execute()) {
                        echo "Invoice {$payment_number} updated successfully!<br>";
                    } else {
                        echo "Error updating invoice {$payment_number}: " . $stmt->error . "<br>";
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
///////////////////////////////////////////deletion code////////////////////////////////////////////

$delete='https://www.zohoapis.in/books/v3/cm_deleted_vendorpayment?organization_id=60008266217&cf_date=2025-01-25';
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
                           
        $upitem_id = $itemdel['cf_payment_id']; 
    }
}


    if ($upitem_id != null) {
        // Delete invoice if not in the API data

        $conn->query("DELETE FROM fitok_vendor_payment WHERE vendor_payment_no = '$upitem_id'");
        echo "$upitem_id  ID deleted from database.<br>";
    }





//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function isTokenExpired($token) {
    session_start();
    
    if (!isset($_SESSION['last_token_refresh'])) {
        return true;
    }

    $last_refresh_timestamp = $_SESSION['last_token_refresh'];
    $current_timestamp = time();
    $token_expiry_time = 3600; 

    return (($current_timestamp - $last_refresh_timestamp) > $token_expiry_time);
}
?>

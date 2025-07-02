<?php
// Zoho Books API URL
$url = 'https://www.zohoapis.in/books/v3/customerpayments?organization_id=60008266217&date=2025-01-21';

// Your OAuth token and refresh token
// $access_token = '1000.e7b452e37417ddef932249e97f6c6437.0d8704c36a45e18c7f0c8dd385102c96';
// $refresh_token = '1000.ed6fec2963d2b763e454264e59085d29.9fc6b5936996f531d4136f5ada280698'; // Update with your actual refresh token

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
    die('cURL Error: ' . curl_error($ch));
}
    if ($response === false) {
        die('Error refreshing access token.');
    }

    $response_data = json_decode($response, true);
    if (isset($response_data['access_token'])) {
        $_SESSION['access_token'] = $response_data['access_token'];
        $_SESSION['last_token_refresh'] = time();  // Update refresh timestamp
        return $response_data['access_token'];
    }
    print_r($response_data);
    die('Error: Could not refresh the access token.');
}

// Function to check if the token has expired
function isTokenExpired() {
    session_start();
    
    if (!isset($_SESSION['last_token_refresh'])) {
        return true;
    }

    $last_refresh_timestamp = $_SESSION['last_token_refresh'];
    $current_timestamp = time();

    $token_expiry_time = 3600; // 1 hour in seconds

    return ($current_timestamp - $last_refresh_timestamp) > $token_expiry_time;
}

// Refresh the access token if expired
if (isTokenExpired()) {
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

    if (isset($data['customerpayments'])) {
        // Append payments from this page to the all_payments array
        $all_payments = array_merge($all_payments, $data['customerpayments']);
        $page++; // Move to the next page
    } else {
        break; // No payments found, exit loop
    }
} while (count($data['customerpayments']) > 0); // Keep fetching until no payments are returned

// Function to convert Zoho datetime to MySQL format
function convertZohoDatetimeToMysql($zoho_datetime) {
    // Strip off the timezone part
    $datetime = preg_replace('/[+\-]\d{4}$/', '', $zoho_datetime);
    return $datetime; // Will return in 'YYYY-MM-DD HH:MM:SS' format
}

// Now you have all payments in $all_payments
if (!empty($all_payments)) {
    foreach ($all_payments as $payment) {
        // Extract relevant fields
        $payment_id = $payment['payment_id'];
       
        $urlll = 'https://www.zohoapis.in/books/v3/customerpayments/'.$payment_id.'?organization_id=60008266217';

        // Fetch payment details by ID
        $chh = curl_init();
        curl_setopt($chh, CURLOPT_URL, $urlll);
        curl_setopt($chh, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($chh, CURLOPT_HTTPHEADER, [
            'Authorization: Zoho-oauthtoken ' . $access_token
        ]);
        curl_setopt($chh, CURLOPT_CAINFO, 'C:\php-8.4.2\extras\ssl\cacert.pem');
        $responses = curl_exec($chh);
        curl_close($chh);
        $dataa = json_decode($responses, true);
        
        if (isset($dataa['payment'])) {
            $paymentss = $dataa['payment'];
            $payment_number = $paymentss['payment_number'];
           // print_r($payment_number)."/n";
            $customer_name = $payment['customer_name'];
            $payment_date = $payment['date'];
            $payment_amt = isset($payment['amount']) && is_numeric($payment['amount']) ? $payment['amount'] : 0.00;
            $currency_code = isset($payment['currency_code']) ? $payment['currency_code'] : 'INR'; // Default currency
     
     
           
            $exchange_rate = isset($payment['exchange_rate']) ? $payment['exchange_rate'] : 1.00; // Default if missing
     
            // $write_off = isset($payment['cf_write_off']) ? $payment['cf_write_off'] : 0.00; // Default if missing
        
            $case_no=$payment['cf_case_no'];
             //print_r($write_off);
            // print_r($case_no);


/////////////////////////////////////////////////////

            // Check and process invoice details
            $invoice_details = [];
            if (isset($paymentss['invoices']) && !empty($paymentss['invoices'])) {
                foreach ($paymentss['invoices'] as $invoice) {
                    $invoice_details[] = [
                        'invoice_number' => $invoice['invoice_number'] ?? 'N/A', // Replace with a default if missing
                        'invoice_date' => $invoice['date'] ?? 'N/A', // Default date
                        'invoice_amount' => $invoice['total'] ?? 0.00 // Default amount
                    ];
                }
            } else {
                // When there are no invoices
                $invoice_details[] = [
                    'invoice_number' => 'N/A',
                    'invoice_date' => 'N/A',
                    'invoice_amount' => 0.00
                ];
            }

            
        //    Insert payment details and invoice into the database
        //     Insert only if it doesn't already exist in the database
            $stmt = $conn->prepare("SELECT * FROM trading_cust_payment WHERE payment_no = ?");
            $stmt->bind_param("s", $payment_number);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows == 0) {
                // Insert new payment into the database
                $stmt = $conn->prepare("INSERT INTO trading_cust_payment (payment_no, customer_name, payment_date, payment_amt, currency_code, invoice_no, invoice_date, invoice_total, exchange_rate, caseno) VALUES (?,?,?,?,?,?,?,?,?,?)");

                // Use the first invoice details as an example (you may need to loop through if there are multiple invoices)
                $invoice = $invoice_details[0]; // First invoice details

                $stmt->bind_param("ssssssssss", $payment_number, $customer_name, $payment_date, $payment_amt, $currency_code, $invoice['invoice_number'], $invoice['invoice_date'], $invoice['invoice_amount'], $exchange_rate, $case_no);

                if ($stmt->execute()) {
                    echo "Payment {$payment_number} inserted successfully!<br>";
                } else {
                    echo "Error inserting payment {$payment_number}: " . $stmt->error . "<br>";
                }
                $stmt->close();
            } else {
                echo "Payment {$payment_number} already exists in the database.<br>";
            }
        }
    }
 } 
// else {
//     echo "No payments found or error fetching data.";
// }
//////////////////update-------------------------------------------
$update_url='https://www.zohoapis.in/books/v3/customerpayments?organization_id=60008266217&cf_modified_date=2025-01-23';
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
    if (isset($updata['customerpayments'])) {
        // Append invoices from this page to the all_invoices array
        $up_cp = array_merge($up_cp, $updata['customerpayments']);
        $pageupp++; // Move to the next page
       //echo $all_invoices;
    } else {
        break; // No invoices found, exit loop
    }
} while (count($updata['customerpayments']) > 0); // Keep fetching until no invoices are returned
if (!empty($up_cp))
 {
    foreach ($up_cp as $up_payment) 
    {
        $up_payment_id = $up_payment['payment_id'];
        $write_off = isset($up_payment['cf_write_off']) ? $up_payment['cf_write_off'] : 0.00; 
        $url = 'https://www.zohoapis.in/books/v3/customerpayments/'.$up_payment_id.'?organization_id=60008266217';

        // Fetch payment details by ID
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Zoho-oauthtoken ' . $access_token
        ]);
        curl_setopt($ch, CURLOPT_CAINFO, 'C:\php-8.4.2\extras\ssl\cacert.pem');
        $responsess = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($responsess, true);
        
        if (isset($data['payment'])) {
            $payments = $data['payment'];
            $payment_no = $payments['payment_number'];
            $customer_name = $payment['customer_name'];
            $payment_date = $payment['date'];
            $payment_amt = isset($payment['amount']) && is_numeric($payment['amount']) ? $payment['amount'] : 0.00;
            $currency_code = isset($payment['currency_code']) ? $payment['currency_code'] : 'INR'; // Default currency
 
     
            $created_at = isset($payment['created_time']) ? convertZohoDatetimeToMysql($payment['created_time']) : null;
            $exchange_rate = isset($payment['exchange_rate']) ? $payment['exchange_rate'] : 1.00; // Default if missing
   
            // $write_off = isset($payment['cf_write_off']) ? $payment['cf_write_off'] : 0.00; // Default if missing
        
            $case_no=$payment['cf_case_no'];
           
            


/////////////////////////////////////////////////////

            // Check and process invoice details
            $invoice_details = [];
            if (isset($paymentss['invoices']) && !empty($paymentss['invoices'])) {
                foreach ($paymentss['invoices'] as $invoice) {
                    $invoice_details[] = [
                        'invoice_number' => $invoice['invoice_number'] ?? 'N/A', // Replace with a default if missing
                        'invoice_date' => $invoice['date'] ?? 'N/A', // Default date
                        'invoice_amount' => $invoice['total'] ?? 0.00 // Default amount
                    ];
                }
            } 
            else {
                // When there are no invoices
                $invoice_details[] = [
                    'invoice_number' => 'N/A',
                    'invoice_date' => 'N/A',
                    'invoice_amount' => 0.00
                ];
            }
            //////////
            $result = $conn->query("SELECT * FROM trading_cust_payment WHERE payment_no = '$payment_no'");
            if ($result->num_rows > 0) {
                // Prepare the statement if the record exists
                $stmt = $conn->prepare("UPDATE trading_cust_payment SET customer_name = ?, payment_date = ?, payment_amt = ?, currency_code = ? = ?, invoice_no = ?, invoice_date = ?, invoice_total = ? = ?, exchange_rate = ? = ? = ?, caseno = ? = ? WHERE payment_no = ?");
                
                // Check if preparation was successful
                if ($stmt) {
                    $stmt->bind_param("ssssssssss", $customer_name, $payment_date, $payment_amt, $currency_code, $invoice_details[0]['invoice_number'], $invoice_details[0]['invoice_date'], $invoice_details[0]['invoice_amount'], $exchange_rate, $case_no, $payment_no);
                    
                    if ($stmt->execute()) {
                        echo "Invoice {$payment_no} updated successfully!<br>";
                    } else {
                        echo "Error updating invoice {$payment_no}: " . $stmt->error . "<br>";
                    }
                    $stmt->close();
                } else {
                    echo "Error preparing statement: " . $conn->error . "<br>";
                }
            }
            
        }
    }
}
//print_r($invoice_details);
///////////////////////////////////////////delete customer payments///////////////
$delete='https://www.zohoapis.in/books/v3/cm_deleted_customer_payment?organization_id=60008266217&cf_date=2025-01-23';
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
                           
        $upitem_id = $itemdel['cf_customer_paymen_id']; 
    }
}


    if ($upitem_id != null) {
        // Delete invoice if not in the API data
        $conn->query("DELETE FROM trading_cust_payment WHERE payment_no = '$upitem_id'");
        echo "$upitem_id  ID deleted from database.<br>";
    }










///////////////////////////////////////////////////////////////////////////////////////
// Close the database connection
$conn->close();
?>

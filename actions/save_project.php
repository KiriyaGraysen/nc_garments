<?php
require_once('../config/database.php');

// Use the actual logged-in admin, fallback to 1 for safety during testing
$created_by_admin = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : 1; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Gather Basic Project Details
    $project_name = $_POST['project_name'];
    $quantity = (int)$_POST['quantity'];
    // Handle empty due dates safely
    $due_date = empty($_POST['due_date']) ? null : $_POST['due_date'];
    $workflow_type = $_POST['workflow_type'];
    
    // Default to 0.00 for Make-to-Order until they do Costing
    $agreed_price = 0.00; 

    // Variables for our Foreign Keys
    $final_customer_id = null;
    $final_product_id = null;

    // Start a secure database transaction
    $conn->begin_transaction();

    try {
        // 2. Handle the Workflow Logic & Snapshotting
        if ($workflow_type === 'customer') {
            // --- MAKE-TO-ORDER LOGIC ---
            $is_new_customer = filter_var($_POST['is_new_customer'], FILTER_VALIDATE_BOOLEAN);
            
            if ($is_new_customer) {
                // Insert brand new customer
                $c_name = $_POST['new_customer_name'];
                $c_contact = !empty($_POST['new_customer_contact']) ? $_POST['new_customer_contact'] : null;
                $c_address = !empty($_POST['new_customer_address']) ? $_POST['new_customer_address'] : null;

                $stmt_cust = $conn->prepare("INSERT INTO customer (full_name, contact_number, address) VALUES (?, ?, ?)");
                $stmt_cust->bind_param("sss", $c_name, $c_contact, $c_address);
                $stmt_cust->execute();
                
                $final_customer_id = $conn->insert_id; 
            } else {
                $final_customer_id = (int)$_POST['existing_customer_id'];
            }
            
        } else if ($workflow_type === 'internal') {
            // --- MAKE-TO-STOCK LOGIC ---
            $final_product_id = (int)$_POST['target_product_id'];
            
            // 🛡️ DATA SNAPSHOTTING: Fetch the current retail price
            $price_stmt = $conn->prepare("SELECT selling_price FROM premade_product WHERE product_id = ?");
            $price_stmt->bind_param("i", $final_product_id);
            $price_stmt->execute();
            $price_result = $price_stmt->get_result()->fetch_assoc();
            
            if ($price_result) {
                $retail_price = (float)$price_result['selling_price'];
                // Lock in the Expected Revenue!
                $agreed_price = $retail_price * $quantity;
            }
        }

        // 3. Insert the Final Project
        $stmt_proj = $conn->prepare("
            INSERT INTO project (customer_id, produced_product_id, created_by_admin, project_name, quantity, due_date, agreed_price) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt_proj->bind_param("iiisssd", 
            $final_customer_id, 
            $final_product_id, 
            $created_by_admin, 
            $project_name, 
            $quantity, 
            $due_date, 
            $agreed_price
        );
        $stmt_proj->execute();
        
        // Grab the newly created Project ID
        $new_project_id = $conn->insert_id;

        // 4. Save Sizing & Measurements (If provided!)
        if (isset($_POST['sizing_type']) && isset($_POST['sizing_data']) && $_POST['sizing_type'] !== 'none') {
            $sizingType = $_POST['sizing_type'];
            $sizingData = json_decode($_POST['sizing_data'], true);
            
            if ($sizingType === 'standard' && !empty($sizingData)) {
                $insert_size = $conn->prepare("INSERT INTO project_sizing (project_id, size_label, quantity) VALUES (?, ?, ?)");
                foreach ($sizingData as $size) {
                    $qty = (int)$size['qty'];
                    $label = trim($size['label']);
                    if(empty($label)) continue;
                    
                    $insert_size->bind_param("isi", $new_project_id, $label, $qty);
                    $insert_size->execute();
                }
            } elseif ($sizingType === 'custom' && !empty($sizingData)) {
                $insert_measure = $conn->prepare("INSERT INTO project_measurement (project_id, body_part, measurement_value, unit) VALUES (?, ?, ?, ?)");
                foreach ($sizingData as $measure) {
                    $val = (float)$measure['val'];
                    $part = trim($measure['part']);
                    $unit = $measure['unit'];
                    if(empty($part)) continue;
                    
                    $insert_measure->bind_param("isds", $new_project_id, $part, $val, $unit);
                    $insert_measure->execute();
                }
            }
        }

        // 5. Commit the transaction
        $conn->commit();

        // Send a success response back to our JavaScript
        echo json_encode([
            "status" => "success", 
            "message" => "Project created successfully!",
            "project_id" => $new_project_id,
            "project_name" => $project_name,
            "agreed_price" => $agreed_price // <-- Added this line!
        ]);

    } catch (Exception $e) {
        // If anything fails (like a database constraint error), rollback EVERYTHING!
        $conn->rollback();
        echo json_encode(["status" => "error", "message" => "Failed to create project: " . $e->getMessage()]);
    }
}
<?php
require_once('../config/database.php');

// Use the actual logged-in admin
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized access."]);
    exit();
}
$created_by_admin = (int)$_SESSION['admin_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Gather Basic Project Details
    $project_name = trim($_POST['project_name']);
    $quantity = (int)$_POST['quantity'];
    $due_date = empty($_POST['due_date']) ? null : $_POST['due_date'];
    $workflow_type = $_POST['workflow_type'];
    
    // Default to 0.00 for Make-to-Order until they do Costing
    $agreed_price = 0.00; 
    $final_customer_id = null;
    $final_product_id = null;
    $workflow_details = ""; // For the audit log description

    // Start a secure database transaction
    $conn->begin_transaction();

    try {
        // 2. Handle the Workflow Logic
        if ($workflow_type === 'customer') {
            $is_new_customer = filter_var($_POST['is_new_customer'], FILTER_VALIDATE_BOOLEAN);
            
            if ($is_new_customer) {
                $c_name = trim($_POST['new_customer_name']);
                $c_contact = !empty($_POST['new_customer_contact']) ? trim($_POST['new_customer_contact']) : null;
                $c_address = !empty($_POST['new_customer_address']) ? trim($_POST['new_customer_address']) : null;

                $stmt_cust = $conn->prepare("INSERT INTO customer (full_name, contact_number, address) VALUES (?, ?, ?)");
                $stmt_cust->bind_param("sss", $c_name, $c_contact, $c_address);
                $stmt_cust->execute();
                
                $final_customer_id = $conn->insert_id; 
                $workflow_details = "for new customer: $c_name";
            } else {
                $final_customer_id = (int)$_POST['existing_customer_id'];
                
                // Fetch customer name for the log
                $c_fetch = $conn->prepare("SELECT full_name FROM customer WHERE customer_id = ?");
                $c_fetch->bind_param("i", $final_customer_id);
                $c_fetch->execute();
                $c_res = $c_fetch->get_result()->fetch_assoc();
                $c_name = $c_res ? $c_res['full_name'] : "Existing Customer";
                $workflow_details = "for customer: $c_name";
            }
            
        } else if ($workflow_type === 'internal') {
            $final_product_id = (int)$_POST['target_product_id'];
            
            // Fetch product name and price
            $price_stmt = $conn->prepare("SELECT product_name, size, selling_price FROM premade_product WHERE product_id = ?");
            $price_stmt->bind_param("i", $final_product_id);
            $price_stmt->execute();
            $p_res = $price_stmt->get_result()->fetch_assoc();
            
            if ($p_res) {
                $agreed_price = (float)$p_res['selling_price'] * $quantity;
                $workflow_details = "Internal Stock: " . $p_res['product_name'] . " (" . $p_res['size'] . ")";
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
        $new_project_id = $conn->insert_id;

        // 4. Save Sizing & Measurements
        if (isset($_POST['sizing_type']) && isset($_POST['sizing_data']) && $_POST['sizing_type'] !== 'none') {
            $sizingType = $_POST['sizing_type'];
            $sizingData = json_decode($_POST['sizing_data'], true);
            
            if ($sizingType === 'standard' && !empty($sizingData)) {
                $insert_size = $conn->prepare("INSERT INTO project_sizing (project_id, size_label, quantity) VALUES (?, ?, ?)");
                foreach ($sizingData as $size) {
                    $l = trim($size['label']);
                    $q = (int)$size['qty'];
                    if(empty($l)) continue;
                    $insert_size->bind_param("isi", $new_project_id, $l, $q);
                    $insert_size->execute();
                }
            } elseif ($sizingType === 'custom' && !empty($sizingData)) {
                $insert_measure = $conn->prepare("INSERT INTO project_measurement (project_id, body_part, measurement_value, unit) VALUES (?, ?, ?, ?)");
                foreach ($sizingData as $measure) {
                    $p = trim($measure['part']);
                    $v = (float)$measure['val'];
                    $u = $measure['unit'];
                    if(empty($p)) continue;
                    $insert_measure->bind_param("isds", $new_project_id, $p, $v, $u);
                    $insert_measure->execute();
                }
            }
        }

        // 5. 🚨 LOG THE ACTIVITY
        $action = 'CREATE';
        $target_table = 'project';
        $formatted_id = "PRJ-" . str_pad($new_project_id, 4, '0', STR_PAD_LEFT);
        
        $description = "Created project $formatted_id ($project_name) with quantity of $quantity $workflow_details.";

        $log_stmt = $conn->prepare("INSERT INTO activity_log (admin_id, action, target_table, target_id, description) VALUES (?, ?, ?, ?, ?)");
        $log_stmt->bind_param("issis", $created_by_admin, $action, $target_table, $new_project_id, $description);
        $log_stmt->execute();

        // 6. Commit the transaction
        $conn->commit();

        echo json_encode([
            "status" => "success", 
            "message" => "Project created successfully!",
            "project_id" => $new_project_id,
            "project_name" => $project_name,
            "agreed_price" => $agreed_price
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["status" => "error", "message" => "Failed to create project: " . $e->getMessage()]);
    }
}
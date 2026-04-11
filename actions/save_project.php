<?php
// backend/save_project.php
require_once('../config/database.php');

// Simulate logged-in user (Replace with $_SESSION['admin_id'] later!)
$created_by_admin = 1; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Gather Basic Project Details
    $project_name = $_POST['project_name'];
    $quantity = (int)$_POST['quantity'];
    $due_date = $_POST['due_date'];
    $agreed_price = (float)$_POST['agreed_price'];
    $workflow_type = $_POST['workflow_type'];

    // Variables for our Foreign Keys (Default to null)
    $final_customer_id = null;
    $final_product_id = null;

    // Start a secure database transaction
    $conn->begin_transaction();

    try {
        // 2. Handle the Workflow Logic
        if ($workflow_type === 'customer') {
            // MAKE-TO-ORDER LOGIC
            $is_new_customer = filter_var($_POST['is_new_customer'], FILTER_VALIDATE_BOOLEAN);
            
            if ($is_new_customer) {
                // Insert brand new customer into the database
                $c_name = $_POST['new_customer_name'];
                $c_contact = !empty($_POST['new_customer_contact']) ? $_POST['new_customer_contact'] : null;
                $c_address = !empty($_POST['new_customer_address']) ? $_POST['new_customer_address'] : null;

                $stmt_cust = $conn->prepare("INSERT INTO customer (full_name, contact_number, address) VALUES (?, ?, ?)");
                $stmt_cust->bind_param("sss", $c_name, $c_contact, $c_address);
                $stmt_cust->execute();
                
                // Grab the newly created ID!
                $final_customer_id = $conn->insert_id; 
            } else {
                // Use the existing customer selected from the dropdown
                $final_customer_id = (int)$_POST['existing_customer_id'];
            }
        } 
        else if ($workflow_type === 'internal') {
            // MAKE-TO-STOCK LOGIC
            $final_product_id = (int)$_POST['target_product_id'];
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
        
        // Grab the ID of the project we just made so we can pass it to the Costing modal if needed
        $new_project_id = $conn->insert_id;

        // Commit the transaction
        $conn->commit();

        // Send a success response back to our JavaScript
        echo json_encode([
            "status" => "success", 
            "message" => "Project created successfully!",
            "project_id" => $new_project_id,
            "project_name" => $project_name
        ]);

    } catch (Exception $e) {
        // If anything fails, rollback the database
        $conn->rollback();
        echo json_encode(["status" => "error", "message" => "Failed to create project: " . $e->getMessage()]);
    }
}
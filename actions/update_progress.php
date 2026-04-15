<?php
require_once('../config/database.php');
$data = json_decode(file_get_contents('php://input'), true);

if ($data && isset($data['project_id'])) {
    $id = (int)$data['project_id'];
    
    // Check if we are updating progress OR updating notes
    if (isset($data['progress'])) {
        $progress = $data['progress'];
        
        // 1. Fetch current data
        $stmt = $conn->prepare("SELECT start_date FROM project WHERE project_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $curr = $stmt->get_result()->fetch_assoc();
        
        $start_date = $curr['start_date'];
        $finish_date = null;
        $status = 'active';

        // 2. The Auto-Date Logic (UPDATED: Resets date on 'not started')
        if ($progress === 'not started') {
            $start_date = null; // Wipe the start date!
        } elseif (empty($start_date)) {
            $start_date = date('Y-m-d'); // Stamp the start date today!
        }

        if ($progress === 'done' || $progress === 'released') {
            $finish_date = date('Y-m-d'); 
            $status = 'completed';
        } elseif ($progress === 'cancelled') {
            $status = 'cancelled';
        }

        // 3. Update the database
        $update = $conn->prepare("UPDATE project SET progress = ?, status = ?, start_date = ?, finish_date = ? WHERE project_id = ?");
        $update->bind_param("ssssi", $progress, $status, $start_date, $finish_date, $id);
        
        if ($update->execute()) echo json_encode(["status" => "success"]);
        else echo json_encode(["status" => "error", "message" => $conn->error]);
        
    } elseif (isset($data['overdue_notes'])) {
        // Updating notes
        $notes = $data['overdue_notes'];
        $update = $conn->prepare("UPDATE project SET overdue_notes = ? WHERE project_id = ?");
        $update->bind_param("si", $notes, $id);
        if ($update->execute()) echo json_encode(["status" => "success"]);
        else echo json_encode(["status" => "error"]);
    }
}
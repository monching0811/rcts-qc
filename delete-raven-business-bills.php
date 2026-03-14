<?php
require_once 'api/config/supabase.php';

echo "Deleting all business tax bills for Raven...\n";

$result = db_delete('rcts_assessment_billing_hub', [
    'qcitizen_id' => 'eq.92be37af-7c34-4c9b-80cb-47cde7c3a9fd',
    'bill_type' => 'eq.BusinessTax'
]);

if ($result['success']) {
    echo "✅ Deleted " . $result['count'] . " business tax bills for Raven\n";
} else {
    echo "❌ Failed to delete bills\n";
}
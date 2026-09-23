<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Medical Portal
    |--------------------------------------------------------------------------
    */

    'title' => 'Medical Services Portal',
    'description' => 'Manage clinics, medical staff, patients, visits, prescriptions, medicines, receipts, issues, and expiry dates.',
    'dashboard' => 'Medical Services Dashboard',
    'overview' => 'Overview',
    'medical_portal' => 'Medical Portal',

    /*
    |--------------------------------------------------------------------------
    | Navigation
    |--------------------------------------------------------------------------
    */

    'clinics' => 'Clinics / Medical Points',
    'staff' => 'Medical Staff',
    'patients' => 'Patients',
    'visits' => 'Medical Visits',
    'medicines' => 'Medicines',
    'batches' => 'Medicine Batches',
    'receipts' => 'Medicine Receipts',
    'issues' => 'Medicine Issues',
    'movements' => 'Medicine Movements',
    'prescriptions' => 'Prescriptions',
    'reports' => 'Reports',

    'medical_point' => 'Medical Point',
    'medical_points' => 'Medical Points',
    'clinic' => 'Clinic',

    /*
    |--------------------------------------------------------------------------
    | Page Descriptions
    |--------------------------------------------------------------------------
    */

    'clinics_description' => 'Use existing medical points within institutions without creating duplicate institution records.',
    'staff_description' => 'Link doctors and nurses to existing staff records in the system.',
    'patients_description' => 'Link patients to existing student and person records in the system.',
    'visits_description' => 'Record medical visits, vital signs, diagnosis, and treatment plans.',
    'medicines_description' => 'Medicine directory with reorder levels and minimum and maximum stock limits.',
    'batches_description' => 'Track medicine batches, expiry dates, and remaining stock.',
    'receipt_description' => 'Add new stock with a batch number and expiry date.',
    'issue_description' => 'Record medicine issues to patients and automatically update batch balances.',
    'movements_description' => 'Non-deletable log of receipts, issues, disposals, and adjustments.',
    'prescriptions_description' => 'Create and track prescriptions linked to patients and doctors.',
    'reports_description' => 'Indicators for medicine consumption, visit activity, and expired stock.',

    /*
    |--------------------------------------------------------------------------
    | Dashboard / Statistics
    |--------------------------------------------------------------------------
    */

    'total' => 'Total',
    'total_clinics' => 'Total Medical Points',
    'active_clinics' => 'Active Medical Points',
    'inactive_clinics' => 'Inactive Medical Points',

    'total_staff' => 'Total Medical Staff',
    'active_staff' => 'Active Medical Staff',

    'total_patients' => 'Total Patients',
    'active_patients' => 'Active Patients',
    'inactive_patients' => 'Inactive Patients',

    'total_medicines' => 'Total Medicines',
    'active_medicines' => 'Active Medicines',

    'total_batches' => 'Total Batches',
    'active_batches' => 'Active Batches',
    'total_quantity' => 'Total Quantity',

    'today' => 'Today',
    'today_visits' => "Today's Visits",
    'completed_visits' => 'Completed Visits',

    'today_movements' => "Today's Movements",
    'today_receipts' => "Today's Receipts",
    'today_issues' => "Today's Issues",

    'receipts_count' => 'Receipt Count',
    'issues_count' => 'Issue Count',
    'adjustments_count' => 'Adjustment Count',

    'expiring_soon' => 'Expiring Soon',
    'expired' => 'Expired',
    'expired_with_stock' => 'Expired with Stock',
    'low_stock' => 'Low Stock',
    'depleted' => 'Depleted',

    'stock_summary' => 'Stock Summary',
    'stock_alerts' => 'Stock Alerts',
    'quick_actions' => 'Quick Actions',

    'recent_movements' => 'Recent Medicine Movements',
    'recent_visits' => 'Recent Visits',
    'top_consumption' => 'Top Medicine Consumption',

    /*
    |--------------------------------------------------------------------------
    | Common Actions
    |--------------------------------------------------------------------------
    */

    'new_visit' => 'New Medical Visit',
    'new_medical_visit' => 'New Medical Visit',

    'new_receipt' => 'Add Medicine Receipt',
    'new_issue' => 'Record Medicine Issue',

    'add_staff' => 'Add Medical Staff Member',
    'add_patient' => 'Add Patient',
    'add_medicine' => 'Add Medicine',
    'new_prescription' => 'New Prescription',

    'add' => 'Add',
    'create' => 'Create',
    'edit' => 'Edit',
    'update' => 'Update',
    'delete' => 'Delete',
    'view' => 'View',
    'details' => 'Details',

    'save' => 'Save',
    'cancel' => 'Cancel',
    'close' => 'Close',
    'back' => 'Back',
    'return' => 'Back',

    'clear' => 'Clear',
    'reset' => 'Reset',
    'filter' => 'Filter',
    'apply_filter' => 'Apply Filter',

    'search' => 'Search',
    'actions' => 'Actions',
    'submit' => 'Submit',
    'confirm' => 'Confirm',

    'no_data' => 'No data available',
    'no_results' => 'No results found',
    'no_records_found' => 'No records found.',
    'showing_results' => 'Showing results',

    /*
    |--------------------------------------------------------------------------
    | Search
    |--------------------------------------------------------------------------
    */

    'search_patients' => 'Search patients',
    'search_medicines' => 'Search medicines',
    'search_movements' => 'Search by movement number or medicine',
    'search_visits' => 'Search by visit number or patient',
    'search_clinics' => 'Search clinics or medical points',
    'search_staff' => 'Search medical staff',
    'search_prescriptions' => 'Search prescriptions',

    'filter_by_status' => 'Filter by status',
    'filter_by_type' => 'Filter by type',
    'filter_by_clinic' => 'Filter by medical point',

    'all' => 'All',
    'all_statuses' => 'All statuses',
    'all_types' => 'All types',
    'all_clinics' => 'All medical points',
    'all_patients' => 'All patients',

    /*
    |--------------------------------------------------------------------------
    | Common Fields
    |--------------------------------------------------------------------------
    */

    'id' => 'ID',
    'code' => 'Code',
    'name' => 'Name',
    'name_ar' => 'Arabic Name',
    'name_en' => 'English Name',

    'status' => 'Status',
    'type' => 'Type',

    'active' => 'Active',
    'inactive' => 'Inactive',

    'date' => 'Date',
    'datetime' => 'Date & Time',
    'number' => 'Number',

    'institution' => 'Institution',
    'student' => 'Student',
    'patient' => 'Patient',
    'person' => 'Person',

    'doctor' => 'Doctor',
    'nurse' => 'Nurse',
    'pharmacist' => 'Pharmacist',
    'other' => 'Other',

    'profession' => 'Profession',
    'license_number' => 'License Number',
    'specialization' => 'Specialization',
    'started_on' => 'Start Date',
    'notes' => 'Notes',
    'reason' => 'Reason',
    'reference_number' => 'Reference Number',

    'created_at' => 'Created At',
    'updated_at' => 'Last Updated',

    /*
    |--------------------------------------------------------------------------
    | Clinics
    |--------------------------------------------------------------------------
    */

    'clinic_information' => 'Medical Point Information',
    'clinic_code' => 'Medical Point Code',
    'clinic_name' => 'Medical Point Name',
    'staff_count' => 'Staff Count',
    'active_staff_count' => 'Active Staff Count',

    /*
    |--------------------------------------------------------------------------
    | Medical Staff
    |--------------------------------------------------------------------------
    */

    'staff_information' => 'Medical Staff Information',
    'staff_profile' => 'Staff Profile',
    'staff_member' => 'Staff Member',
    'staff_status' => 'Staff Status',

    'select_staff_member' => 'Select Staff Member',
    'select_profession' => 'Select Profession',

    'profession_doctor' => 'Doctor',
    'profession_nurse' => 'Nurse',
    'profession_pharmacist' => 'Pharmacist',
    'profession_other' => 'Other',

    /*
    |--------------------------------------------------------------------------
    | Patients
    |--------------------------------------------------------------------------
    */

    'patient_profile' => 'Patient Profile',
    'patient_information' => 'Patient Information',
    'patient_code' => 'Patient Code',
    'student_code' => 'Student Code',
    'patient_status' => 'Patient Status',

    'gender' => 'Gender',
    'birth_date' => 'Date of Birth',

    'allergies' => 'Allergies',
    'chronic_conditions' => 'Chronic Conditions',
    'medical_history' => 'Medical History',

    'linked_student' => 'Linked Student',
    'select_student' => 'Select Student',
    'select_patient' => 'Select Patient',

    'visit_history' => 'Visit History',
    'prescription_history' => 'Prescription History',

    /*
    |--------------------------------------------------------------------------
    | Visits
    |--------------------------------------------------------------------------
    */

    'visit' => 'Visit',
    'visit_number' => 'Visit Number',

    'visit_information' => 'Visit Information',
    'clinical_assessment' => 'Clinical Assessment',
    'vital_signs' => 'Vital Signs',
    'treatment_information' => 'Treatment Information',

    'visited_at' => 'Visit Date & Time',

    'chief_complaint' => 'Chief Complaint',
    'diagnosis' => 'Diagnosis',
    'treatment_plan' => 'Treatment Plan',

    'temperature' => 'Temperature',
    'blood_pressure' => 'Blood Pressure',
    'pulse' => 'Pulse',
    'weight' => 'Weight',

    'open' => 'Open',
    'open_visit' => 'Open Visit',
    'in_progress' => 'In Progress',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled',

    'select_doctor' => 'Select Doctor',
    'select_nurse' => 'Select Nurse',
    'select_clinic' => 'Select Medical Point',
    'select_visit' => 'Select Visit',

    /*
    |--------------------------------------------------------------------------
    | Medicines
    |--------------------------------------------------------------------------
    */

    'medicine' => 'Medicine',
    'medicine_information' => 'Medicine Information',
    'medicine_code' => 'Medicine Code',
    'medicine_status' => 'Medicine Status',

    'generic_name' => 'Generic Name',
    'form' => 'Dosage Form',
    'strength' => 'Strength',
    'unit' => 'Unit',
    'manufacturer' => 'Manufacturer',

    'stock' => 'Stock',
    'current_stock' => 'Current Stock',
    'available_stock' => 'Available Stock',

    'reorder_level' => 'Reorder Level',
    'minimum_stock' => 'Minimum Stock',
    'maximum_stock' => 'Maximum Stock',

    'active_ingredient' => 'Active Ingredient',

    'select_medicine' => 'Select Medicine',

    /*
    |--------------------------------------------------------------------------
    | Medicine Batches
    |--------------------------------------------------------------------------
    */

    'batch' => 'Batch Number',
    'batch_number' => 'Batch Number',
    'batch_information' => 'Batch Information',

    'expiry_date' => 'Expiry Date',
    'expiry' => 'Expiry',

    'quantity' => 'Quantity',
    'current_quantity' => 'Current Quantity',
    'remaining_quantity' => 'Remaining Quantity',

    'days_remaining' => 'Days Remaining',

    'active_batch' => 'Active Batch',
    'expired_batch' => 'Expired Batch',
    'expiring_batch' => 'Expiring Batch',

    /*
    |--------------------------------------------------------------------------
    | Medicine Movements
    |--------------------------------------------------------------------------
    */

    'movement' => 'Movement',
    'movement_number' => 'Movement Number',
    'transaction_number' => 'Transaction Number',

    'movement_information' => 'Movement Information',
    'movement_type' => 'Movement Type',

    'receipt' => 'Receipt',
    'issue' => 'Issue',
    'disposal' => 'Disposal',
    'adjustment' => 'Adjustment',

    'received_quantity' => 'Received Quantity',
    'issued_quantity' => 'Issued Quantity',

    'received_at' => 'Received At',
    'occurred_at' => 'Movement Date',

    'unit_cost' => 'Unit Cost',
    'supplier' => 'Supplier',

    'save_receipt' => 'Save Receipt',
    'save_issue' => 'Save Issue',

    'fefo_note' => 'Issues are automatically processed using the First-Expiry-First-Out (FEFO) principle.',

    /*
    |--------------------------------------------------------------------------
    | Medicine Receipt
    |--------------------------------------------------------------------------
    */

    'receipt_information' => 'Receipt Information',
    'receipt_date' => 'Receipt Date',
    'batch_expiry' => 'Batch Expiry Date',

    /*
    |--------------------------------------------------------------------------
    | Medicine Issue
    |--------------------------------------------------------------------------
    */

    'issue_information' => 'Issue Information',
    'issue_date' => 'Issue Date',
    'issue_quantity' => 'Issue Quantity',

    'issue_patient' => 'Patient',
    'issue_visit' => 'Related Visit',

    /*
    |--------------------------------------------------------------------------
    | Prescriptions
    |--------------------------------------------------------------------------
    */

    'prescription' => 'Prescription',
    'prescription_number' => 'Prescription Number',

    'prescription_information' => 'Prescription Information',
    'prescription_lines' => 'Prescribed Medicines',

    'dose' => 'Dose',
    'frequency' => 'Frequency',
    'duration' => 'Duration',
    'instructions' => 'Instructions',

    'prescribed_by' => 'Prescribed By',

    'dispensed' => 'Dispensed',
    'not_dispensed' => 'Not Dispensed',
    'partially_dispensed' => 'Partially Dispensed',

    'dispense' => 'Dispense',
    'dispense_prescription' => 'Dispense Prescription',

    /*
    |--------------------------------------------------------------------------
    | Reports
    |--------------------------------------------------------------------------
    */

    'report' => 'Report',
    'report_period' => 'Report Period',

    'from' => 'From',
    'to' => 'To',

    'generate_report' => 'Generate Report',
    'export' => 'Export',
    'export_excel' => 'Export to Excel',

    'visits_report' => 'Visits Report',
    'medicines_report' => 'Medicines Report',
    'stock_report' => 'Stock Report',
    'expiry_report' => 'Expiry Report',
    'consumption_report' => 'Consumption Report',
    'receipts_report' => 'Receipts Report',
    'issues_report' => 'Issues Report',

    'total_visits' => 'Total Visits',
    'total_receipts' => 'Total Receipts',
    'total_issues' => 'Total Issues',
    'total_expired' => 'Total Expired Batches',

    'consumption' => 'Consumption',

    /*
    |--------------------------------------------------------------------------
    | Messages
    |--------------------------------------------------------------------------
    */

    'saved' => 'Saved successfully.',
    'updated' => 'Updated successfully.',
    'deleted' => 'Deleted successfully.',
    'operation_completed' => 'Operation completed successfully.',

    'receipt_saved' => 'Receipt recorded and batch balance updated.',
    'issue_saved' => 'Issue recorded and stock balances updated.',

    'select_required' => 'Please select a value.',
    'required_field' => 'This field is required.',

    /*
    |--------------------------------------------------------------------------
    | Validation / Errors
    |--------------------------------------------------------------------------
    */

    'errors' => [

        'insufficient_stock' =>
            'Available stock is insufficient for this issue.',

        'invalid_quantity' =>
            'The entered quantity is invalid.',

        'invalid_date' =>
            'The entered date is invalid.',

        'save_failed' =>
            'Unable to save the data. Please try again.',

        'load_failed' =>
            'Unable to load the data.',

        'not_found' =>
            'The requested record was not found.',

    ],
    // Medicine Movements Page

'medicine_movements' => 'Medicine Movements',
'medicine_movements_description' => 'Track all medicine movements including receipts, issues, adjustments, and disposals.',
'movement_list' => 'Medicine Movement List',
'movement_list_description' => 'Central log of all medical stock movements with search and filtering options.',
'all_movements' => 'All Movements',

'today_movements' => "Today's Movements",
'today_movements_description' => 'Total medicine movements recorded today.',

'receipts' => 'Medicine Receipts',
'receipts_description' => 'Medicine receiving and stock addition transactions.',

'issues' => 'Medicine Issues',
'issues_description' => 'Medicine transactions issued from stock.',

'adjustments' => 'Adjustments',
'adjustments_description' => 'Medicine stock balance adjustment transactions.',

'disposals' => 'Disposals',
'disposals_description' => 'Medicine disposal transactions that remove stock from inventory.',

'no_movements' => 'No Movements',
'no_movements_description' => 'No medicine movements match the current search or filter criteria.',
'new_receipt_description' => 'Register a new medicine receipt and add it to the medical point stock.',

'receipt_information_description' => 'Select the medicine, medical point, and basic receipt information.',

'select_medical_point' => 'Select Medical Point',

'batch_information_description' => 'Enter the batch number, expiry date, and quantity for the received medicine.',

'receipt_source' => 'Receipt Source',

'receipt_source_description' => 'Enter the supplier, reference number, receipt date, and reason for adding the stock.',
'medicine_issue' => 'Medicine Issue',

'medicine_issue_description' => 'Register medicine issues from the medical point stock and track movement details.',

'movement_history' => 'Medicine Movement History',

'movement_information_description' => 'Select the medicine, medical point, movement type, quantity, and related information.',

'patient_visit_information' => 'Patient & Visit Information',

'patient_visit_information_description' => 'Link the medicine issue to the patient and medical visit when applicable.',

'no_patient' => 'No Patient',

'no_visit' => 'No Visit',

'reason_and_notes' => 'Reason & Notes',

'reason_and_notes_description' => 'Enter the reason for the medicine movement and any additional notes.',

'stock_movement_warning_title' => 'Stock Warning',

'stock_movement_warning' => 'Make sure the requested quantity is available in the medical point stock before recording the movement.',

'save_movement' => 'Save Movement',
'total_clinics_description' => 'Total number of medical points registered in the system.',

'active_clinics_description' => 'Number of active medical points currently available.',

'inactive_clinics_description' => 'Number of medical points that are currently inactive.',

'active_medical_staff' => 'Active Medical Staff',

'active_medical_staff_description' => 'Total number of active medical staff members registered at the medical points.',

'results' => 'Results',

'medical_points_list' => 'Medical Points List',

'medical_points_list_description' => 'List of medical points belonging to the institutions, including their status and medical staff counts.',

'medical_staff' => 'Medical Staff',

'staff' => 'Medical Staff',

'active' => 'Active',

'inactive' => 'Inactive',

'members' => 'Members',
'inactive_staff' => 'Inactive Medical Staff',

'doctors' => 'Doctors',

'staff_list' => 'Medical Staff List',

'staff_list_description' => 'List of doctors, nurses, and medical staff members assigned to medical points.',

'records' => 'Records',

'no_staff_found' => 'No medical staff members were found.',
'staff_form_description' => 'Add and assign a medical staff member to a medical point and define their professional information.',
'new_patient' => 'Add Patient',

'active_patients_description' => 'Number of active patients registered in the system.',

'inactive_patients_description' => 'Number of inactive patients registered in the system.',

'patient_records' => 'Patient Records',

'patient_list' => 'Patient List',

'patient_list_description' => 'List of patients linked to student and person records in the system.',

'no_patients' => 'No Patient Records',

'no_patients_description' => 'No patient records match the current search or filter criteria.',
'back_to_list' => 'Back to Patient List',

'patient_registration' => 'Patient Registration',

'patient_registration_description' => 'Link a student to a medical record and define the medical point and basic health information.',

'medical_history_description' => 'Enter important health information associated with the patient.',

'allergies' => 'Allergies',

'chronic_conditions' => 'Chronic Conditions',

'save_patient' => 'Save Patient',
'registered_visits' => 'Registered Visits',
'displayed_visits' => 'Displayed Visits',
'current_page_results' => 'Current Page Results',
'visits_today' => 'Visits Today',
'completed_visits_description' => 'Number of medical visits that have been completed.',
'search_and_filter' => 'Search & Filter',
'visits_filter_description' => 'Search medical visits and filter the results by status.',
'visit_list' => 'Medical Visit List',
'visit_list_description' => 'Medical visit records with patient, status, diagnosis, and treatment plan details.',
'no_visits_description' => 'No medical visits match the current search or filter criteria.',
'new_visit_description' => 'Register a new medical visit and document the patient information, clinical assessment, vital signs, and treatment plan.',
'visit_information_description' => 'Select the patient, medical point, doctor, nurse, visit date, and visit status.',
'clinical_assessment_description' => 'Record the patient’s chief complaint, diagnosis, and clinical assessment.',
'vital_signs_description' => 'Record the patient’s basic vital signs during the medical visit.',
'treatment_information_description' => 'Enter the treatment plan and notes related to the medical visit.',
'save_visit' => 'Save Visit',
'new_medicine' => 'Add Medicine',
'active_medicines_description' => 'Number of active medicines registered and available for use.',
'low_stock_description' => 'Number of medicines whose stock has reached or fallen below the reorder level.',
'out_of_stock' => 'Out of Stock Medicines',
'out_of_stock_description' => 'Number of medicines with no current stock available.',
'inactive_medicines' => 'Inactive Medicines',
'inactive_medicines_description' => 'Number of medicines that are currently inactive or unavailable for use.',
'medicine_records' => 'Medicine Records',
'medicine_list' => 'Medicine List',
'medicine_list_description' => 'Medicine directory with stock information, reorder levels, and minimum and maximum stock limits.',
'no_medicines' => 'No Medicines',
'no_medicines_description' => 'No medicines match the current search or filter criteria.',
'medicine_information_description' => 'Enter the basic medicine information, including code, unit, names, dosage form, strength, and manufacturer.',
'unit_unit' => 'Unit',
'unit_tablet' => 'Tablet',
'unit_capsule' => 'Capsule',
'unit_bottle' => 'Bottle',
'unit_box' => 'Box',
'unit_ampoule' => 'Ampoule',
'unit_tube' => 'Tube',
'stock_settings' => 'Stock Settings',
'stock_settings_description' => 'Define the reorder level, minimum stock, and maximum stock levels for the medicine.',
'reorder_level_help' => 'When stock reaches or falls below this level, the medicine should be reordered.',
'maximum_stock_help' => 'The target maximum quantity to keep in stock for this medicine.',
'medicine_notes_description' => 'Add any additional notes or information related to the medicine.',
'save_medicine' => 'Save Medicine',
'medicine_batches' => 'Medicine Batches',
'medicine_batches_description' => 'Track medicine batches, batch numbers, expiry dates, and available quantities.',
'total_batches_description' => 'Total number of medicine batches registered in the system.',
'active_batches_description' => 'Number of active medicine batches currently available.',
'within_90_days' => 'Expiring Soon',
'expired_batches' => 'Expired Batches',
'batch_filter' => 'Batch Filter',
'batch_filter_description' => 'Filter medicine batches by status and quantity availability.',
'available_quantity' => 'Available Quantity',
'available_quantity_description' => 'Total quantity currently available across medicine batches.',
'batch_list' => 'Medicine Batch List',
'batch_list_description' => 'List of medicine batches with batch numbers, expiry dates, and available quantities.',
'no_batches' => 'No Batches',
'no_batches_description' => 'No medicine batches match the current filter criteria.',
'prescription_list' => 'Prescription List',
'prescription_list_description' => 'List of prescriptions linked to patients, doctors, and medical visits.',
'no_prescriptions' => 'No Prescriptions',
'no_prescriptions_description' => 'No prescriptions match the current search or filter criteria.',
'back_to_prescriptions' => 'Back to Prescription List',

'prescription_information_description' => 'Select the patient, doctor, and basic prescription information.',

'prescribed_medicine' => 'Prescribed Medicine',

'prescribed_medicine_description' => 'Select the medicine and define the dosage, frequency, duration, quantity, and usage instructions.',

'prescription_notes_description' => 'Add any additional notes or instructions related to the prescription.',

'save_prescription' => 'Save Prescription',
'back_to_dashboard' => 'Back to Medical Services Dashboard',

'report_period_description' => 'Select the date range for which you want to generate medical services indicators and reports.',

'from_date' => 'From Date',
'to_date' => 'To Date',

'report_visits_description' => 'Total medical visits recorded during the selected period.',
'report_receipts_description' => 'Total quantities of medicines received and added to stock during the selected period.',
'report_issues_description' => 'Total quantities of medicines issued and removed from stock during the selected period.',
'report_expired_description' => 'Number of expired medicine batches during the selected period.',

'top_medicines' => 'Top Medicines',
'top_medicines_description' => 'Medicines with the highest issued and consumed quantities during the selected period.',

'no_report_data' => 'No Report Data',
'no_report_data_description' => 'No medical data was recorded during the selected period to display in the report.',

'report_summary' => 'Report Summary',
'report_summary_description' => 'Summary of key medical services indicators and medicine movements during the selected period.',

'total_medicine_movement' => 'Total Medicine Movement',

];


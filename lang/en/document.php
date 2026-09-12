<?php

return [
    'title'         => 'Employee Documents',
    'singular'      => 'Document',
    'add_new'       => 'Upload New Document',
    'edit'          => 'Edit Document Details',
    'trash'         => 'Documents Trash',
    'back'          => 'Back to Employee Profile',
    'download'      => 'Download Document',
    'view_file'     => 'Preview File',
    'verified'      => 'Verified & Approved',
    'unverified'    => 'Under Review',

    'fields' => [
        'employee'      => 'Employee',
        'document_type' => 'Document Type',
        'title'         => 'Document Title',
        'file'          => 'Uploaded File',
        'issue_date'    => 'Issue Date',
        'expiry_date'   => 'Expiry Date',
        'is_verified'   => 'Verification Status',
        'verified'      => 'Verified & Approved',
        'unverified'    => 'Under Review',
        'verified_by'   => 'Verified By',
        'notes'         => 'Notes',
        'status'        => 'Validity Status',
        'valid'         => 'Valid',
        'expiring_soon' => 'Expiring Soon (Within 30 days)',
        'expired'       => 'Expired',
        'created_at'    => 'Uploaded At',
        'deleted_at'    => 'Deleted At',
    ],

    'types' => [
        'contract'               => 'Employment Contract',
        'national_id'            => 'National ID / Iqama',
        'qualification'          => 'Educational Degree',
        'experience_certificate' => 'Experience Certificate',
        'certificate'            => 'Certified / Training Certificate',
        'medical_check'          => 'Medical Examination',
        'driving_license'        => 'Driving License',
        'passport'               => 'Passport',
        'other'                  => 'Other Documents',
    ],

    'messages' => [
        'created'       => 'Document uploaded successfully',
        'updated'       => 'Document updated successfully',
        'deleted'       => 'Document moved to trash successfully',
        'restored'      => 'Document restored successfully',
        'force_deleted' => 'Document permanently deleted',
        'empty_trash'   => 'Documents trash is empty',
    ],

    'hints' => [
        'title' => 'Leave empty to use the document type name. Only fill it when you have more than one document of the same type.',
        'expiry' => 'Leave empty for documents that never expire (qualifications, certificates).',
    ],

    'placeholders' => [
        'title' => 'e.g. Contract 2026 (optional)',
    ],

    'confirm' => [
        'verify_title'  => 'Approve this document?',
        'verify_text'   => 'You are about to officially approve ":document" for :employee.',
        'verify_button' => 'Yes, approve',
    ],

];

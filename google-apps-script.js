/**
 * ╔══════════════════════════════════════════════════════════════════╗
 * ║  ⚠️  DEPRECATED — DO NOT USE                                    ║
 * ║                                                                  ║
 * ║  This Google Apps Script was the old onboarding workflow that     ║
 * ║  stored tenant data in Google Sheets.                            ║
 * ║                                                                  ║
 * ║  REPLACED BY: Laravel Backend API                                ║
 * ║  • Onboarding form → POST /api/v1/onboarding/{token}/submit     ║
 * ║  • Document upload → same endpoint (multipart form data)         ║
 * ║  • Tenant data → MySQL database (tenants, tenant_documents)      ║
 * ║  • Admin approval → POST /api/v1/admin/onboarding/{id}/approve   ║
 * ║                                                                  ║
 * ║  New Flow:                                                       ║
 * ║  1. Admin generates secure onboarding link (expires in 72hrs)    ║
 * ║  2. Candidate opens link → fills form + uploads docs             ║
 * ║  3. Data stored in MySQL (onboarding_invitations table)          ║
 * ║  4. Admin reviews & approves → Tenant ID auto-generated          ║
 * ║  5. User account created → credentials shared via WhatsApp       ║
 * ║                                                                  ║
 * ║  Backend location: /backend/app/Http/Controllers/Api/            ║
 * ║                    OnboardingController.php                       ║
 * ║                                                                  ║
 * ║  This file is kept for reference only. Remove when ready.        ║
 * ╚══════════════════════════════════════════════════════════════════╝
 */

// ─── OLD CODE (for reference) ────────────────────────────────────────

// Replace with your actual Google Sheet ID
const SHEET_ID = 'YOUR_GOOGLE_SHEET_ID'; // ← replace this
const SHEET_NAME = 'Tenants';

function doPost(e) {
  try {
    const data = JSON.parse(e.postData.contents);
    const ss = SpreadsheetApp.openById(SHEET_ID);
    let sheet = ss.getSheetByName(SHEET_NAME);

    // If sheet doesn't exist, create it with headers
    if (!sheet) {
      sheet = ss.insertSheet(SHEET_NAME);
      sheet.appendRow(getHeaders());
    }

    // Add headers if first row is empty
    if (sheet.getLastRow() === 0) {
      sheet.appendRow(getHeaders());
    }

    // Append data row
    sheet.appendRow([
      data.tenantId,
      data.timestamp,
      data.pg,
      data.fullName,
      data.roomNo,
      data.mobile,
      data.dob,
      data.companyName,
      data.address,
      data.parentMobile,
      data.reference1,
      data.reference2 || 'N/A',
      data.bloodGroup || 'N/A',
      data.onboarding,
      data.docType,
      data.selfieUrl,
      data.aadhaarPdfUrl || 'N/A',
      data.docFrontUrl || 'N/A',
      data.docBackUrl || 'N/A',
      data.companyDocUrl
    ]);

    return ContentService
      .createTextOutput(JSON.stringify({ status: 'success' }))
      .setMimeType(ContentService.MimeType.JSON);

  } catch (error) {
    return ContentService
      .createTextOutput(JSON.stringify({ status: 'error', message: error.message }))
      .setMimeType(ContentService.MimeType.JSON);
  }
}

function getHeaders() {
  return [
    'Tenant ID',
    'Timestamp',
    'PG Name',
    'Full Name',
    'Room No',
    'Mobile Number',
    'Date of Birth',
    'Company/College Name',
    'Company/College Address',
    'Parent Mobile',
    'Reference Number 1',
    'Reference Number 2',
    'Blood Group',
    'Date of Onboarding',
    'Document Type',
    'Selfie URL',
    'Aadhaar PDF URL',
    'Voter ID Front URL',
    'Voter ID Back URL',
    'Company/College ID URL'
  ];
}

// Required for GET requests (testing)
function doGet(e) {
  return ContentService
    .createTextOutput(JSON.stringify({ status: 'active', message: 'PG Verification API is running' }))
    .setMimeType(ContentService.MimeType.JSON);
}

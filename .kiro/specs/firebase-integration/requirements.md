# Requirements Document

## Introduction

This feature integrates Firebase (Firestore) into the Harish PG (PG A1) static website to store tenant information, capture queries from potential tenants, and enable a contact/message system. Currently, the website is a purely static HTML site with property data served from a local JSON file. Firebase will provide a serverless backend for data persistence without requiring a traditional server setup.

## Glossary

- **Website**: The PG A1 static HTML website served at the custom domain
- **Firebase_App**: The Firebase application instance initialized in the browser with project configuration
- **Firestore**: The Firebase Cloud Firestore NoSQL database used for storing all application data
- **Tenant_Record**: A document in Firestore representing a current or past PG tenant with personal and occupancy details
- **Query_Record**: A document in Firestore representing an inquiry submitted by a potential tenant interested in a property
- **Message_Record**: A document in Firestore representing a general message or contact form submission from a website visitor
- **Contact_Form**: The HTML form on the website that visitors use to submit queries or messages
- **Admin**: The PG owner or operator who manages tenant records and reads submitted queries and messages
- **Visitor**: Any person browsing the PG A1 website who has not yet become a tenant

## Requirements

### Requirement 1: Firebase Initialization

**User Story:** As a developer, I want Firebase to be properly initialized in the website, so that all Firebase services are available for data operations.

#### Acceptance Criteria

1. WHEN the website loads in a browser, THE Firebase_App SHALL initialize using the project configuration (API key, auth domain, project ID, storage bucket, messaging sender ID, app ID)
2. WHEN the Firebase_App initializes successfully, THE Website SHALL have access to the Firestore database instance
3. IF the Firebase_App fails to initialize, THEN THE Website SHALL log the error to the browser console and continue displaying the static content without data features
4. THE Firebase_App SHALL load the Firebase SDK scripts from the official Firebase CDN

### Requirement 2: Tenant Information Storage

**User Story:** As an admin, I want to store tenant information in Firebase, so that I can manage current and past tenants digitally without paper records.

#### Acceptance Criteria

1. THE Firestore SHALL store each Tenant_Record with the following fields: tenant name, phone number, email (optional), PG property ID, room number, move-in date, monthly rent amount, security deposit paid, and status (active or vacated)
2. WHEN a new Tenant_Record is created, THE Firestore SHALL generate a unique document ID and record the creation timestamp
3. WHEN a Tenant_Record is updated, THE Firestore SHALL record the last-modified timestamp
4. THE Firestore SHALL enforce that tenant name, phone number, PG property ID, and move-in date are present in every Tenant_Record
5. WHILE a Tenant_Record has status "active", THE Website SHALL count that tenant toward the occupancy of the associated PG property

### Requirement 3: Query Submission from Potential Tenants

**User Story:** As a visitor, I want to submit an inquiry about a PG property through the website, so that the PG owner can contact me back with availability and details.

#### Acceptance Criteria

1. THE Website SHALL display a Contact_Form on each property detail page with fields: visitor name, phone number, email (optional), preferred move-in date (optional), and message
2. WHEN a visitor submits the Contact_Form from a property detail page, THE Website SHALL create a Query_Record in Firestore with the form data and the associated PG property ID
3. WHEN the Contact_Form is submitted successfully, THE Website SHALL display a confirmation message to the visitor indicating the inquiry was received
4. IF the Contact_Form submission fails due to a network error, THEN THE Website SHALL display an error message asking the visitor to try again or contact via WhatsApp
5. THE Website SHALL validate that visitor name and phone number are filled before allowing form submission
6. WHEN a Query_Record is created, THE Firestore SHALL record the submission timestamp and set the status to "new"

### Requirement 4: General Message Submission

**User Story:** As a visitor, I want to send a general message to the PG owner without selecting a specific property, so that I can ask general questions about availability or services.

#### Acceptance Criteria

1. THE Website SHALL display a general Contact_Form accessible from the navigation or home page with fields: visitor name, phone number, email (optional), subject, and message body
2. WHEN a visitor submits the general Contact_Form, THE Website SHALL create a Message_Record in Firestore with the form data
3. WHEN the general Contact_Form is submitted successfully, THE Website SHALL display a confirmation message to the visitor
4. IF the general Contact_Form submission fails due to a network error, THEN THE Website SHALL display an error message asking the visitor to try again or contact via WhatsApp
5. THE Website SHALL validate that visitor name, phone number, and message body are filled before allowing form submission
6. WHEN a Message_Record is created, THE Firestore SHALL record the submission timestamp

### Requirement 5: Firestore Security Rules

**User Story:** As an admin, I want the database to be secured so that only authorized actions are permitted and visitor data is protected.

#### Acceptance Criteria

1. THE Firestore SHALL allow any website visitor to create Query_Record and Message_Record documents (write-only for visitors)
2. THE Firestore SHALL prevent website visitors from reading, updating, or deleting Query_Record and Message_Record documents
3. THE Firestore SHALL restrict read and write access to Tenant_Record documents to authenticated admin users only
4. IF an unauthenticated user attempts to read Tenant_Record documents, THEN THE Firestore SHALL deny the request

### Requirement 6: Form UI Integration

**User Story:** As a visitor, I want the contact forms to match the existing website design, so that the experience feels cohesive and trustworthy.

#### Acceptance Criteria

1. THE Contact_Form SHALL use the same color scheme (amber accent, cream background, dark text), typography (DM Sans, Playfair Display), and border-radius styles as the existing Website components
2. THE Contact_Form SHALL be responsive and usable on mobile devices with a minimum touch target size of 44x44 pixels for buttons and inputs
3. WHILE the Contact_Form is submitting data to Firestore, THE Website SHALL display a loading indicator on the submit button and disable the button to prevent duplicate submissions
4. WHEN the form submission completes (success or failure), THE Website SHALL re-enable the submit button

# Data Privacy Compliance Note

**System:** GuidanceSched  
**Applicable law:** Data Privacy Act of 2012 (Republic Act No. 10173)  
**Status:** Academic prototype. Use synthetic, non-identifying data only until the school has reviewed and approved real-data use.

## 1. Purpose and privacy principles

GuidanceSched is a school guidance workflow prototype for user accounts, student consultations, guidance case records, and follow-up actions. Its intended use is to help authorized school personnel coordinate consultations and maintain relevant guidance records. It does not make automated decisions about students.

The system should be used according to the Data Privacy Act’s principles of transparency, legitimate purpose, and proportionality. Information should be collected only when needed for a stated school function, used only for that function, and made available only to people who need it for their duties. This note describes the prototype and its current limitations; it is not a certification of legal compliance or approval to process actual student information. The school is responsible for confirming its authority, policies, and safeguards before deployment. ([RA 10173, Sections 11–13](https://privacy.gov.ph/data-privacy-act/))

## 2. Personal data collected and purposes

The application stores the following categories of information to support its workflows:

| Data category | Examples | Purpose |
| --- | --- | --- |
| Account and authentication data | Name, email address, role, password hash, and session information | Identify users, authenticate sign-in, administer accounts, and maintain a signed-in session. Passwords are stored as hashes rather than readable text. |
| Student and school details | Student number, grade level, section, address, contact number, assigned teacher | Identify a student, support school contact, and associate the student with the appropriate teacher and guidance workflow. |
| Parent or guardian details | Name, relationship to the student, and contact number | Support appropriate school contact when needed for the guidance process. |
| Consultation and appointment data | Student and staff links, title, reason, proposed or scheduled date and time, duration, status, and staff notes | Request, assign, schedule, and track consultations. Reasons and staff notes may reveal sensitive personal information depending on what is entered. |
| Guidance case and follow-up data | Case title and category, description, dates, remarks, findings, recommendations, follow-up notes, and actions | Document a guidance case and coordinate follow-up by authorized personnel. These records may contain sensitive personal information. |
| Technical request information | Information needed to process a website or API request and maintain a session | Operate, secure, and troubleshoot the service. The prototype does not describe a comprehensive activity audit trail. |

Users should not enter unnecessary details into free-text fields. In particular, sensitive information should not be included unless the school has approved the purpose, access, and handling rules for it. The prototype is intended for synthetic records and should not be populated with real student, family, or case details at this stage.

## 3. Lawful basis for processing

For current demonstrations, synthetic data is used; this note does not establish a lawful basis for processing real personal data. Before real-data use, the school, as the proposed personal information controller, must determine and document the applicable lawful condition for each purpose. The Data Privacy Act requires a condition under Section 12 for personal information and an applicable condition under Section 13 for sensitive personal information. Guidance notes may include information covered by the sensitive-personal-information rules.

The correct basis depends on the school’s status, the specific activity, applicable education laws and policies, and the individuals concerned. The school must also determine what authority applies when processing information about minors. Consent should not automatically be treated as the basis for every school record; the basis must be assessed for the actual purpose and circumstances. The school’s Data Protection Officer (DPO) or privacy officer should review and document this assessment and approve the privacy notice before any real student or family information is entered. ([RA 10173, Sections 12–13](https://privacy.gov.ph/data-privacy-act/))

## 4. Access and access control

Access requires a user account and sign-in. Server-side role and record checks restrict what authenticated users can view or change; hiding an option in the interface is not the only control. Current application roles have these broad permissions:

- **Administrators / guidance personnel:** manage user accounts and access consultation, case, and follow-up workflows. Case and follow-up records are restricted to administrators.
- **Teachers:** work with students assigned to them and related consultation workflows. Their access is limited by assignment and role checks.
- **Students:** view their own assigned consultation date, time, and status. They cannot access guidance case notes. Student-facing appointment responses omit internal staff notes.

Accounts and assignments should be issued to named individuals, with access limited to the duties they perform. The school should remove or adjust access promptly when roles change, periodically review active accounts and teacher-student assignments, and prohibit shared accounts. The prototype does not provide a comprehensive record-access audit trail, so access review and accountability procedures require additional school processes.

## 5. Retention and disposal

The prototype has no automatic retention schedule or expiry-based deletion. For class demonstrations and evaluation, retain only synthetic data for the period needed to complete the activity. At the end, an operator should remove the demonstration database and dataset exports, screenshots, and any backups that contain the data, then confirm that the demo instance no longer holds those copies.

For real records, the school must approve a retention schedule for each record type, taking account of the original purpose and applicable education, archival, and other legal requirements. Keep identifiable records only as long as necessary or legally required. At the end of the approved period, securely erase or irreversibly anonymize the records. Backup copies should be removed according to an approved backup rotation and disposal procedure. Disposal should be documented by the responsible person. No fixed retention period is stated here because the school’s schedule has not been supplied. ([NPC Implementing Rules and Regulations](https://privacy.gov.ph/implementing-rules-regulations-data-privacy-act-2012/))

## 6. Security measures and relationship to R4

The prototype includes several controls relevant to the security measures described in **R4**:

- Authentication is required for protected application functions; there is no public self-registration flow.
- Server-side role and ownership or assignment checks limit access to records.
- Passwords are stored as hashes, and application forms validate submitted values and use CSRF protection.
- Student-facing consultation responses limit displayed information and omit internal staff notes.
- The local application and database communicate through a private Docker network, with the database bound to localhost in the development setup.

These measures help with access control, credential protection, input handling, and limiting disclosure. They do not by themselves make the prototype suitable for real student records. The local development setup may use HTTP and has not verified production HTTPS, protected secrets, least-privilege production database credentials, encryption at rest, monitored security logs, backup rotation, or a tested incident-response process. A comprehensive access audit trail is also not demonstrated. Before real-data use, the school and deployment operator must configure and verify secure connections, database and backup protections, account and access reviews, monitoring, restoration procedures, and incident reporting and response. This aligns with the Act’s requirement for reasonable and appropriate organizational, physical, and technical safeguards. ([RA 10173, Section 20](https://privacy.gov.ph/data-privacy-act/))

## 7. Data subject access and correction

The current prototype has no student self-service feature to download or edit personal data. A student, or a parent or guardian where legally authorized, should contact the school office or DPO using the contact information in the school’s approved privacy notice. The school must publish its controller and DPO contact details and request procedure before collecting real information.

When a request is received, the school should verify the requester’s identity and, where applicable, their authority to act for the student. It should locate the relevant records, provide the requester secure access to information they are entitled to receive, and correct inaccurate or incomplete data through an authorized staff member. The response should avoid disclosing another person’s information. Corrections to guidance notes may require review by authorized personnel and should follow the school’s record-history policy. The school should keep only the request documentation needed for accountability and handle the request within the applicable legal and school-policy timeframes. ([RA 10173, Section 16](https://privacy.gov.ph/data-privacy-act/); [NPC: Data Subject Rights](https://privacy.gov.ph/data-subject-rights/))

## 8. Review and approval before real-data use

Before deployment with real student or family information, the school should have its DPO or privacy officer review this note, confirm the lawful basis and retention schedule, approve the privacy notice and access policy, and verify the R4 security measures in the intended hosting environment. Until that review and approval are complete, use synthetic data only.

**School DPO / privacy officer:** ______________________________  
**Review date:** ____________________  **Approval date:** ____________________

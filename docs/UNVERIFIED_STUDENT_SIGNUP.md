# MARKADO self-service Student signup without email verification

**Status:** Optional new mode on a feature branch; not approved for production deployment. No Railway service or production data was changed.

## What this mode does

With MARKADO_UNVERIFIED_STUDENT_SIGNUP_ENABLED=true, and the existing institutional/presentation registration flags **off**, MARKADO can create a real Student login from Student Number, Email, Password, and Confirm Password, without a verification email or professor approval.

The account is assigned only the Student role and is logged in immediately. Its password is hashed and its self-reported Student Number is stored in student_self_reported_claims, **never** in the trusted school_student_records table. The Student Dashboard shows an unverified identity warning. This mode cannot grant access to restricted quizzes, other students' grades, or academic results without independently verified school identity and a quiz assignment.

A matching Student Number is not proof of enrollment, and the claimed email is not proof of mailbox ownership. Duplicate unverified Student Numbers may exist; they cannot reserve or change official roster records. Existing account email ownership is not reassigned through signup.

## Configuration

- MARKADO_UNVERIFIED_STUDENT_SIGNUP_ENABLED=false by default
- MARKADO_PRESENTATION_REGISTRATION_ENABLED=false
- MARKADO_PUBLIC_STUDENT_REGISTRATION_ENABLED=false

The mode is mutually exclusive with the existing institutional email verification signup routes. It requires a local PostgreSQL development database or, in hosted environments, HTTPS, an exact allowed host, PostgreSQL, non-debug mode, and a valid Laravel APP_KEY. It has CSRF protection and the existing Student registration rate limits. Route/configuration caches must be rebuilt after approved variable changes.

This creates an **unverified Student login**, not an academically verified identity. A school must provide a separate trusted verification and recovery process before the Student may take restricted assessments. Without email ownership verification, email-claim squatting and unrecoverable accounts remain a risk. Do not advertise the submitted email as verified, and do not loosen the quiz eligibility gate.

## Testing and deployment boundary

Run the new UnverifiedStudentSignupTest with SQLite and PostgreSQL and execute a local browser signup/login/dashboard check. Recheck invalid/duplicate emails, role escalation, self-reported number duplication, and the lack of roster or grade access. The regular institutional verification signup flow must remain intact.

The mode has not been deployed or tested on Railway. Staging and production remain unchanged and metered staging workloads must not be restarted without budget authorization. A production enablement requires a security/privacy review, account recovery ownership, backup safeguards, and an explicit release authorization. Do not enable the mode simply to hide the registration-unavailable warning.

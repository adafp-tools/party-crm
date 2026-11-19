# Party CRM — User Guide

A quick reference for the most common tasks.

---

## Signing in

Go to `https://your-party-domain.org/login`. Enter your email and password. If your role requires it, you will be prompted for a 6-digit two-factor authentication (2FA) code from your authenticator app.

**First login:** you will be redirected to set up 2FA before you can access the dashboard.

---

## Dashboard

The dashboard shows:
- **Member counts** by status (active, applicant, suspended, …)
- **Growth chart** — new members over the last 12 months
- **Dues summary** — payments confirmed this month
- **Active members by unit** (counts below 5 are shown as `<5` to protect privacy)
- **Upcoming events** and **overdue tasks**

---

## Members

### Finding a member
Go to **Members**. Use the search box (name, email, or phone) and the status/unit filters to narrow the list.

### Adding a member (web)
Go to **Members → Add member**. Fill in the required fields (name, gender, date of birth, unit, consent channel and date). Click **Save**. The new member starts as an **Applicant**.

### Approving an applicant
Open the member's profile. The status banner shows "Applicant". Enter an optional approval note and click **Approve**. A membership number is assigned automatically.

### Downloading a membership card
Once a member has a membership number, open their profile and click **Download Card** to get a PDF card with a QR code for field verification.

### Logging an interaction
On a member's profile, click the **Interactions** tab. Select the interaction type (call, meeting, visit, SMS, email, other), add a summary, and click **Log**.

---

## Dues

### Plans
Go to **Dues → Plans** to create or edit dues plans (name, amount, currency, period: monthly or yearly).

### Recording a payment
Go to **Dues → Record payment**, find the member, choose the plan, enter the reference (mobile money or receipt number), and click **Save**. Status is `recorded` until confirmed.

### Confirming payments
Go to **Dues → Confirm queue** to review recorded payments and confirm or reject them.

---

## Events

Go to **Events** to see upcoming and past events. Click an event to take attendance — tick members as they arrive, or use the **Scan QR** button to scan a membership card.

---

## Tasks

Go to **Tasks** to see your assigned tasks. Click a task to mark it complete or update its status. Use **New task** to create one and assign it to a team member.

---

## Campaigns

Go to **Campaigns** to draft and send SMS or email campaigns. Select a unit to scope recipients, write the message, and click **Send**.

---

## Reports

Go to **Reports** to see:
- Membership breakdown by status and unit
- Dues summary for the current month
- Growth charts

**Exporting members (national_admin and regional_coordinator only):** Click **Export members (CSV)**. A reason is required (minimum 5 characters) — this is logged for data protection compliance.

**Audit log (national_admin only):** Click **Audit log** to view all system actions. Filter by user, action, or date range.

---

## Data requests (national_admin only)

Open a member's profile and click the **Data requests** tab to log a formal data subject request (access, correction, or deletion).

---

## Settings (national_admin only)

Go to **Settings** to update:
- Party name, logo, website
- Enabled languages and default language
- Currency and timezone
- Privacy notice text (shown at `/privacy`)

---

## Security

- **2FA is mandatory** for national_admin, national_leadership, treasurer, communications_officer, and regional_coordinator.
- Sessions expire after 60 minutes of inactivity.
- Failed login attempts are limited to 5 before a temporary lockout.
- Never share your password. Contact the national_admin to reset it.

---

## Getting help

Contact your party's national administrator or ADAFP support.

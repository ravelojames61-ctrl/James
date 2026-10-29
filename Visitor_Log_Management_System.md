# Visitor Log Management System

## Description

A digital visitor registration system that records visitor information and monitors entry and exit times.

## Main Features

1. **Visitor Registration** — Record visitor name, contact information, purpose of visit, and person to visit.
2. **Time-In Recording** — Automatically record the visitor's arrival date and time.
3. **Time-Out Recording** — Record departure time when the visitor leaves.
4. **Visitor History** — Maintain a searchable history of visitor records.
5. **Visitor Search** — Search records by visitor name, date, or purpose.
6. **Current Visitor Monitoring** — Display visitors who are currently inside the facility.
7. **Visitor Reports** — Generate daily, weekly, and monthly visitor summaries.
8. **Dashboard** — Display total visitors, current visitors, and completed visits.

## User Roles

- **Administrator:** Manages users, visitor records, reports, and system settings.
- **Security Personnel:** Registers visitors, records time-in and time-out, and monitors visitors inside the facility.
- **Receptionist:** Registers visitors, searches visitor records, generates reports, and views the dashboard, depending on assigned permissions.

## System Flowchart

```mermaid
flowchart TD
    A([Start]) --> B[Login: Username and Password]
    B --> C{Valid User?}
    C -- No --> D[Show Error Message]
    D --> B
    C -- Yes --> E[Select User Role]
    E --> F{Role}

    F --> G[Administrator]
    F --> H[Security Personnel]
    F --> I[Receptionist]

    G --> J[Manage Users and System Settings]
    J --> K[View Visitor Records]
    K --> L[Generate Visitor Reports]

    H --> M[Register Visitor]
    M --> N[Record Time-In Automatically]
    N --> O[Monitor Current Visitors]
    O --> P[Record Time-Out When Visitor Leaves]

    I --> Q[Register Visitor]
    Q --> R[Record Time-In Automatically]
    R --> S[Search Visitor Records]
    S --> T[View Dashboard and Reports]

    L --> U[Save and Update Visitor Records]
    P --> U
    T --> U

    U --> V[View Visitor History]
    V --> W[Logout]
    W --> X([End])
```

## Main Visitor Process

```mermaid
flowchart TD
    A([Start]) --> B[Enter Visitor Information]
    B --> C[Save Visitor Record]
    C --> D[Automatically Record Time-In]
    D --> E[Set Status to Inside]
    E --> F[Display in Current Visitors]
    F --> G[Visitor Leaves Facility]
    G --> H[Record Time-Out]
    H --> I[Update Status to Completed]
    I --> J[Save Record in Visitor History]
    J --> K[Update Dashboard and Reports]
    K --> L([End])
```

## Basic System Rules

- The system should record the arrival date and time automatically.
- Only visitors with an active visit should appear in the **Current Visitors** list.
- When a visitor leaves, the system records the time-out and changes the status from **Inside** to **Completed**.
- Visitor records should be searchable by name, date, and purpose of visit.
- Reports should summarize visitor activity by day, week, and month.
- User access should be based on the permissions assigned to each role.

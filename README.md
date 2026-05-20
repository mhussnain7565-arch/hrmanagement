# HR Management & University ERP System

A comprehensive, database-driven Enterprise Resource Planning (ERP) solution designed for both Human Resource management and academic administration. Featuring a modern **Cobalt Blue & Slate** UI, Dynamic Role-Based Access Control (RBAC), and a modular architecture.

## 🛡️ Core Architecture

- **Dynamic Access Control**: Permissions are managed via the `role_access` table, allowing for hot-swapping page permissions without changing code.
- **Intelligent Sidebar**: The sidebar is recursively generated from the `sys_pages` database, ensuring users only see what they are authorized to access.
- **Dual-Mode Theme**: Optimized for both Light and "Midnight" Dark modes with premium **Outfit** typography.

---

## 📂 Module Mapping

### Super Admin Dashboard
The command center for system administrators and managers.

| Page Name | Description |
| :--- | :--- |
| `manage_users.php` | Full-cycle user management (CRUD) with role assignment and status control. |
| `manage_roles.php` | Define system roles and manage entry points for different user portals. |
| `manage_pages.php` | Control the dynamic sidebar and URL permissions mapping. |
| `manage_departments.php` | Define and organize company or university departments. |
| `manage_subjects.php` | Manage academic courses and professional subjects available in the system. |
| `leave_categories.php` | Configure leave types (Sick, Annual, etc.) and set day allowances. |
| `apply_leave.php` | Interface for administrators to apply for leave for themselves or on behalf of staff. |
| `approval_leave.php` | Specialized portal for reviewing, approving, or rejecting leave requests. |
| `assign_departments.php` | Map existing personnel and users to their respective organizational units. |
| `assign_faculty.php` | Map faculty members to specialized academic departments or subject areas. |
| `category_applications.php` | Categorized view of applications for better organizational reporting. |

### Staff & Faculty Dashboards
Personal portals for employees to manage their work-life balance.
- **`index.php`**: Summarized view of personal leave stats, recent applications, and quick actions.

### API Services (`/api`)
Backend logic powering the dynamic frontend.
- `leave_applications.php`: Core logic for request submission and status updates.
- `leave_categories.php`: Backend CRUD operations for leave types.
- `assign_subjects.php`: Logic for linking subjects to faculty members.
- `get_users_by_role.php`: Dynamic retrieval of user lists for dropdowns and selectors.

---

## 🔍 Gap Analysis (Missing Features)

To evolve this system into a "full-fledged" enterprise solution, the following core modules are currently identified as missing:

1.  **Attendance Management**: No active module for biometric integration or manual attendance tracking.
2.  **Payroll & Compensation**: Missing salary calculation engines, tax management, and automated payslip generation.
3.  **Inventory & Asset Tracking**: No system to manage organizational hardware, books, or stationary.
4.  **Performance Appraisal (KPIs)**: No mechanism for manager reviews or setting Key Result Areas for staff.
5.  **Academic Portal (Student)**: While a student portal exists, it lacks grading systems, course registration, and exam scheduling.

---

## 🚀 Strategic Recommendations

### 1. Advanced Analytics (BI)
Integrate **Chart.js** to provide managers with visual trends on leave patterns, staff distribution, and department performance directly on the dashboard.

### 2. Global Search & Quick Actions
Implement a global command bar (Command Palette) allowing admins to jump to a specific user profile or page instantly from anywhere in the app.

### 3. Automated Notification Engine
Implement a notification service using **PHPMailer** to send automated email alerts to staff when their leave is approved, rejected, or when new policies are posted.

### 4. Document Management System (DMS)
Add secure file storage for employee contracts, identity documents, and student transcripts, reducing paper reliance.

---

## 🛠️ Technical Stack
- **Backend**: PHP 8.x + PDO (MySQL/MariaDB)
- **Frontend**: Bootstrap 5, AdminLTE 4, Vanilla CSS
- **Database**: Relational mapping with foreign key integrity
- **Security**: Session-based auth + Database RBAC Gatekeeper

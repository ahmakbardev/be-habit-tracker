# Tasks Module Documentation

This document provides a comprehensive overview of the **Tasks Module** in the Habit Tracker application, including all current features and technical requirements for backend implementation.

---

## 1. Feature Overview

### A. Hierarchical Organization
The tasks module uses a three-tier hierarchy to manage productivity:
1.  **Folders:** Top-level containers (e.g., "Work", "Personal", "Side Projects"). Folders can be renamed, deleted, and managed via a dedicated **Folder Dashboard**.
2.  **Projects (Workspaces):** Second-level containers within Folders (e.g., "Project Alpha", "Daily Habits"). 
    - **Customization:** Each project can have its own description, status (Planning, Active, etc.), and custom Lucide icons.
    - **Metadata:** Projects support tracking owners, target dates, and overall project priority.
3.  **Tasks:** Individual action items within a Project.

### B. Multi-View System
Users can visualize and manage their tasks through three distinct layouts:
- **Kanban Board:** A dynamic board with drag-and-drop capabilities.
    - **Custom Columns:** Unlike static boards, each project can define its own columns (e.g., "Backlog", "To Do", "In Progress", "Review", "Done").
    - **Visual Indicators:** Task cards show priority levels, due dates, and linked note status.
- **Table View:** A structured list view for quick scanning and bulk management. 
- **Timeline View:** A chronological visualization of tasks based on their start and due dates, ideal for project scheduling.

### C. Task Management & Details
- **Detailed Task Sidebar:** Selecting a task opens a comprehensive side panel to edit:
    - **Core Info:** Title and rich description.
    - **Status & Priority:** Dropdowns to move tasks between columns and set priority (Low, Medium, High).
    - **Scheduling:** Precise Start Date and Due Date selection.
    - **Tagging:** Categorize tasks with multiple tags.
- **Note Integration:** Tasks can be linked to a specific note from the **Note Module** via `linkedNoteId`. This allows users to jump directly to relevant documentation.
- **Quick Add:** "New Task" modals and in-column "Add Task" buttons for rapid entry.

### D. UI/UX & Navigation
- **Task Sidebar:** Persistent left navigation for switching between folders and projects.
- **Dashboards:** 
    - **Folder Dashboard:** Overview of all task folders.
    - **Project Dashboard:** Visual grid of all projects within a selected folder.
- **Right Sidebar Workflow:** Task details are managed in a slide-out panel, keeping the main board/list visible.

---

## 2. Backend Requirements (Technical Specification)

To support the dynamic nature of this module, the backend must handle hierarchical data and flexible project configurations.

### A. Database Schema

#### `task_folders` Table
- `id`: UUID (Primary Key)
- `user_id`: UUID (Foreign Key)
- `name`: String
- `icon_name`: String
- `order_index`: Integer
- `timestamps`: created_at, updated_at

#### `task_projects` Table
- `id`: UUID (Primary Key)
- `folder_id`: UUID (Foreign Key)
- `name`: String
- `description`: Text
- `icon_name`: String
- `status`: Enum ('planning', 'active', 'on-hold', 'completed')
- `start_date`: DateTime
- `end_date`: DateTime
- `metadata`: JSON (Stores optional fields like `owner`, `priority`)
- `order_index`: Integer
- `timestamps`: created_at, updated_at

#### `task_columns` Table
- `id`: UUID (Primary Key)
- `project_id`: UUID (Foreign Key)
- `title`: String
- `order_index`: Integer (Crucial: Persists the horizontal order of columns on the Kanban board)
- `timestamps`: created_at, updated_at

#### `tasks` Table
- `id`: UUID (Primary Key)
- `project_id`: UUID (Foreign Key)
- `column_id`: UUID (Foreign Key)
- `title`: String
- `description`: Text (Must support multi-line/whitespace-pre-wrap formatting)
- `priority`: Enum ('low', 'medium', 'high')
- `start_date`: DateTime
- `due_date`: DateTime
- `tags`: JSON/Array (List of strings, e.g., `["Urgent", "Work"]`)
- `linked_note_id`: String (Supports internal protocol: `notes://folder/workspace/noteId`)
- `order_index`: Integer (Vertical position within a column)
- `timestamps`: created_at, updated_at

### B. API Endpoints (RESTful)

#### Organization
- `GET /api/tasks/folders`: Fetch hierarchical structure.
- `POST /api/tasks/folders`: Create folder.
- `PATCH /api/tasks/folders/:id`: Update folder name/icon.
- `POST /api/tasks/projects`: Create project.
- `PATCH /api/tasks/projects/:id`: Update metadata/description.
- `DELETE /api/tasks/projects/:id`: Cascade delete columns and tasks.

#### Kanban & Column Management
- `POST /api/tasks/columns`: Add new column to project.
- `PATCH /api/tasks/columns/:id`: Rename column.
- `PUT /api/tasks/columns/reorder`: Batch update `order_index` for horizontal column movement.
- `DELETE /api/tasks/columns/:id`: Remove column (Logic: Must either delete or move associated tasks to a "Default" column).

#### Task Operations
- `GET /api/tasks/projects/:projectId/tasks`: Fetch tasks.
- `POST /api/tasks`: Create task.
- `PATCH /api/tasks/:id`: Update task (Supports partial updates for title, status, priority, dates, and tags).
- `PUT /api/tasks/reorder`: Vertical and Cross-column drag-and-drop handler.
- `DELETE /api/tasks/:id`: Hard delete.

### C. Essential Backend Logic
1.  **Default Initialization:** New projects must auto-initialize with "To Do", "In Progress", and "Done" columns.
2.  **Tag Normalization:** Although stored as JSON, consider a normalization strategy if global tag searching becomes a requirement.
3.  **Horizontal Column Reordering:** When a user drags a column (e.g., moves "Done" to the first position), the backend must update `order_index` for all columns in that project.
4.  **Formatting Preservation:** The `description` field must not strip whitespace or newlines, as users often use manual formatting.
5.  **Linked Resource Integrity:** If a `linked_note_id` is formatted as `notes://...`, the backend should ideally verify the target note exists (though not strictly required if using an internal protocol string).

---

## 3. Future Roadmap
- **Reminders:** Push notifications and email alerts for upcoming due dates.
- **Sub-tasks:** Checklists within a single task card.
- **Collaborative Projects:** Shared folders/projects with real-time updates.
- **Statistics:** Project progress charts (burn-down charts) and productivity metrics.

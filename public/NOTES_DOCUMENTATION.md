# Note Module Documentation

This document provides a comprehensive overview of the **Note Module** in the Habit Tracker application, including all current features and technical requirements for backend implementation.

---

## 1. Feature Overview

### A. Hierarchical Organization
The note module uses a three-tier hierarchy to keep information organized:
1.  **Folders:** Top-level containers (e.g., "Work", "Personal"). Folders can be renamed, deleted, and assigned icons.
2.  **Workspaces:** Second-level containers within Folders (e.g., "Project Alpha", "Journal"). 
    - **Custom Icons:** When creating a workspace, users can choose from a curated library of Lucide icons to visually distinguish projects.
3.  **Notes:** Individual documents within a Workspace.

### B. Rich Text Editor (Advanced)
A premium editing experience built from the ground up:
- **Text Formatting:** Bold, Italic, Underline, Strikethrough, Code, and variable text colors (Color Popover).
- **Headings:** Support for H1, H2, and H3 for structured documentation.
- **Lists:** Smart bulleted and numbered lists.
- **Tables:** Advanced table support with a floating "Bubble Menu" to add/remove rows and columns dynamically.
- **Images:** Image insertion via URL with a custom **`ImageResizer`** component for drag-and-drop scaling.
- **Hyperlinks:** Integrated link management with a dedicated popover.
- **Markdown Support:** A fallback **`SimpleMarkdownEditor`** for developers or users who prefer markdown syntax.

### C. UI/UX & Data Management
- **Visual Dashboards:** 
    - **Folder Dashboard:** Grid view of all folders with task/note counts.
    - **Workspace Dashboard:** Visual card layout for all workspaces within a folder.
- **Search Functionality:** Real-time filtering of notes within a workspace using the integrated search bar.
- **Note Actions:**
    - **Duplication:** One-click "Duplicate" button to clone a note and its entire content.
    - **Highlighting:** UI-level support for "High-Priority" notes (visualized with a distinct background/border).
    - **Soft Deletion:** "Delete" action with confirmation popovers to prevent accidental loss.
- **Drag & Drop Reordering:** Notes can be reordered manually within a workspace using `framer-motion`'s Reorder API.
- **Side Panel Workflow:** Selecting a note opens it in a slide-out panel, enabling "multi-tasking" where you can browse other notes while keeping the editor open.

### D. Advanced Task Integration
- **Internal Linking:** Every note has a unique "Share Link" (Format: `notes://folder/workspace/noteId`).
- **Task Association:** Tasks in the Project module can be linked to specific notes via a hierarchical folder/workspace selector or by pasting a direct internal link.
- **Direct Preview:** Linked notes can be opened and read directly within the Task Detail Sidebar via a premium overlay, maintaining project context without switching modules.

---

## 2. Backend Requirements (Technical Specification)

To transform this from a frontend prototype to a production-ready feature, the following backend architecture is required.

### A. Database Schema

#### `folders` Table
- `id`: UUID (Primary Key)
- `user_id`: UUID (Foreign Key)
- `name`: String
- `icon_name`: String (Lucide icon identifier)
- `order_index`: Integer
- `timestamps`: created_at, updated_at

#### `workspaces` Table
- `id`: UUID (Primary Key)
- `folder_id`: UUID (Foreign Key)
- `name`: String
- `icon_name`: String
- `order_index`: Integer
- `timestamps`: created_at, updated_at

#### `notes` Table
- `id`: UUID (Primary Key)
- `workspace_id`: UUID (Foreign Key)
- `title`: String
- `content`: Text/JSON (Store HTML or Tiptap JSON)
- `plain_text_preview`: String (Auto-generated for search indexing)
- `highlight`: Boolean
- `order_index`: Integer
- `timestamps`: created_at, updated_at

### B. API Endpoints (RESTful)

#### Folders & Workspaces
- `GET /api/folders`: Fetch hierarchical structure for the authenticated user.
- `POST /api/folders`: Create folder.
- `PATCH /api/folders/:id`: Rename/Update icon.
- `DELETE /api/folders/:id`: Cascade delete.
- `POST /api/workspaces`: Create workspace.

#### Notes
- `GET /api/workspaces/:id/notes`: Fetch note list.
- `POST /api/notes`: Create new note.
- `POST /api/notes/:id/duplicate`: Server-side cloning of a note.
- `PUT /api/notes/:id`: Full update (triggered by auto-save).
- `PATCH /api/notes/:id`: Partial updates (toggle highlight, rename).
- `DELETE /api/notes/:id`: Remove note.
- `PUT /api/notes/reorder`: Batch update order indices.

### C. Essential Backend Logic
1.  **HTML Sanitization:** Critical to prevent XSS. All note content must be cleaned before storage.
2.  **Full-Text Search:** Implement a search index (e.g., PostgreSQL GIN) on `title` and `plain_text_preview`.
3.  **File Management:** Add an endpoint for image uploads that returns persistent URLs (S3 integration).
4.  **Auto-Save Debouncing:** The backend should be optimized for frequent, small updates from the editor.

---

## 3. Future Roadmap
- **Collaboration:** Real-time multi-user editing (WebSockets + CRDTs).
- **Offline Mode:** Local-first caching for mobile/unstable connections.
- **Templates:** "Meeting Minutes", "Project Brief", and "Daily Journal" presets.

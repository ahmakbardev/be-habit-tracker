# Habits Module Documentation

This document provides a comprehensive overview of the **Habits Module** in the Habit Tracker application, including all current features and technical requirements for backend implementation.

---

## 1. Feature Overview

### A. Habit Types & Scheduling
The module supports two primary types of habits to accommodate different behavioral patterns:
1.  **Daily Habits:** Simple habits tracked once per day (e.g., "No Alcohol").
2.  **Periodic (Scheduled) Habits:** Habits that require multiple check-ins at specific times during the day (e.g., "Drink Water" at 08:00, 10:30, 13:00, etc.). 

### B. Visualization Views
- **Weekly View:** A granular table showing habits and their completion status for each day of the current week. 
    - For periodic habits, the table expands to show individual time slots.
    - Includes a "Today" indicator and supports navigating between weeks.
- **Monthly View:** A high-level calendar visualization.
    - **Progress Rings:** Each calendar cell features a circular progress ring indicating the overall completion percentage of all habits for that day.
    - **Hover Details:** Hovering over a date reveals a tooltip with a detailed breakdown of habit completion (e.g., "Drink Water: 4/6").

### C. Tracking & Quick Actions
- **Quick Check-in:** A sidebar component for rapid logging of habits for the current day without navigating the main table.
- **Efficiency Metric:** A visual card showing the user's overall productivity/completion rate with a historical bar chart.
- **Color Coding:** Each habit can be assigned a custom color (e.g., Blue for health, Cyan for hydration) which is reflected across all UI components.

---

## 2. Backend Requirements (Technical Specification)

The backend must efficiently manage high-frequency check-in data and calculate progress metrics on-the-fly or via cached aggregations.

### A. Database Schema

#### `habits` Table
- `id`: UUID (Primary Key)
- `user_id`: UUID (Foreign Key)
- `name`: String
- `icon_type`: String (Lucide icon identifier, e.g., 'circle', 'x', 'shield', 'check', 'clock')
- `color`: String (Tailwind class or Hex code, e.g., 'bg-blue-600')
- `goal`: Integer (Total slots per day. For daily: 1. For periodic: length of `schedules`)
- `schedules`: JSON/Array (List of specific time strings, e.g., `["08:00", "12:00", "20:00"]`)
- `created_at`: DateTime (Crucial: Completion rings/progress only calculate from this date onwards)
- `updated_at`: DateTime

#### `habit_completions` Table
- `id`: UUID (Primary Key)
- `habit_id`: UUID (Foreign Key)
- `user_id`: UUID (Foreign Key)
- `date`: Date (Format: YYYY-MM-DD)
- `time_slot`: String (Required for periodic habits to distinguish slots, e.g., "08:00". For daily habits, use a constant like "daily")
- `status`: Integer (0: incomplete, 1: complete)
- `timestamps`: created_at, updated_at
- **Constraint:** Unique index on `(habit_id, date, time_slot)` to prevent duplicate logs.

### B. API Endpoints (RESTful)

#### Habit Configuration
- `GET /api/habits`: Fetch all active habits for the user.
- `POST /api/habits`: Create a new habit. 
    - Body: `{ name, color, icon_type, schedules: [] }`
- `PATCH /api/habits/:id`: Update habit configuration (name, color, icon).
- `DELETE /api/habits/:id`: Hard delete or Soft delete (archiving recommended to keep historical data).

#### Completion & Tracking
- `GET /api/habits/completions?start_date=YYYY-MM-DD&end_date=YYYY-MM-DD`: Fetch logs for a range.
- `POST /api/habits/toggle`: Toggle completion.
    - Body: `{ habit_id, date, time_slot }` (Server toggles status 0 <-> 1).
- `GET /api/habits/stats`: Fetch efficiency percentage and historical bar chart data (last 7 days).

### C. Essential Backend Logic
1.  **Efficiency Calculation:** 
    - `Efficiency = (Completed Slots / Total Possible Slots) * 100`.
    - **Note:** "Total Possible Slots" only includes days after `habit.created_at`.
2.  **Date Validation:** 
    - Prevent logging for future dates.
    - Prevent logging for dates before `habit.created_at`.
3.  **Timezone Sensitivity:** All `date` fields in `habit_completions` should be stored based on the user's local "day" (YYYY-MM-DD) to ensure consistency when the user travels or changes timezones.
4.  **Auto-Generation Logic:** When a habit is created in "Periodic/Auto" mode, the frontend generates a list of times. The backend simply receives this as a static array in `schedules`.
5.  **Icon Metadata:** Even if the frontend uses a limited set of icons now, the backend should store a string identifier to allow for future expansion of the icon library.

---

## 3. Future Roadmap
- **Streak Tracking:** Calculate "Current Streak" and "Longest Streak" for each habit.
- **Reminders:** Integration with browser/mobile notifications based on the `schedules` array.
- **Habit Categories:** Group habits into categories like "Health", "Productivity", or "Mindfulness".
- **Social Challenges:** Compete with friends on habit consistency.

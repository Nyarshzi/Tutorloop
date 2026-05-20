# TutorLoop Code Review Report

## Executive Summary

TutorLoop is a well-structured peer tutoring platform built with HTML, CSS, JavaScript, PHP, and MySQL. The project demonstrates comprehensive implementation of core tutoring platform features including user authentication, tutor profile management, session scheduling, messaging, and feedback systems.

---

## 1. REGISTRIES (Database Tables)

Based on code analysis, the following tables are inferred to exist in the `tutorloop_db` database:

| Table | Status | Evidence |
|-------|--------|----------|
| **Users Registry** | ✅ DONE | `users` table with `user_id`, `name`, `email`, `password`, `role`, `student_id`, `profile_pic`, `is_verified` |
| **Tutor Profile Registry** | ✅ DONE | `tutor_profiles` table with `tutor_id`, `description`, `phone_number`, `average_rating`, `tutoring_rate` |
| **Subjects Registry** | ✅ DONE | `subjects` table with `subject_id`, `subject_name` |
| **Tutor Subjects** | ✅ DONE | `tutor_subjects` table with `id`, `tutor_id`, `subject_id`, `rate` |
| **Tutor Availability** | ✅ DONE | `tutor_availability` table with `id`, `tutor_subject_id`, `day_of_week`, `start_time`, `end_time` |
| **Session Registry** | ✅ DONE | `sessions` table with `session_id`, `tutor_id`, `tutee_id`, `subject_id`, `requested_schedule`, `session_status`, `request_note` |
| **Messaging Registry** | ✅ DONE | `messages` table with message storage, `sender_id`, `receiver_id`, `message_content`, `date_sent` |
| **Feedback and Rating Registry** | ✅ DONE | `feedback_ratings` table with `feedback_id`, `session_id`, `tutor_id`, `rating`, `feedback_comment` |

**Note:** No SQL schema file was found in the project. The database structure is inferred from PHP code queries.

---

## 2. SYSTEM MODULES

### User Authentication Module
| Requirement | Status | Notes |
|-------------|--------|-------|
| User registration with Full Name, Student ID, Email, Password, Role | ✅ DONE | `register.php` - All fields implemented with domain validation (@students.isatu.edu.ph) |
| Login and logout functionality | ✅ DONE | `login.php`, `logout.php` - Session-based authentication with password hashing |
| Role-based dashboard redirection | ✅ DONE | Tutors → `tutor_dashboard.php`, Tutees → `tutee_dashboard.php` |

### Tutor Profile Management Module
| Requirement | Status | Notes |
|-------------|--------|-------|
| Tutors can create and update their profile | ✅ DONE | `create_tutor_profile.php` - Full CRUD operations |
| Fields: biography, subjects, tutoring rate, profile picture | ✅ DONE | Bio, phone, profile picture upload implemented |
| Updated profile visible to tutees | ✅ DONE | `tutor_view_profile.php` displays tutor info to tutees |

### Tutor Availability Module
| Requirement | Status | Notes |
|-------------|--------|-------|
| Tutors can set available time slots per day | ✅ DONE | `tutor_myschedule.php` - Day of week + time range |
| Tutors can add, edit, and remove availability | ✅ DONE | JavaScript-driven UI with add/remove functionality |
| Availability used for session requests | ⚠️ PARTIAL | Availability is displayed but not enforced during booking |

### Tutor Search Module
| Requirement | Status | Notes |
|-------------|--------|-------|
| Tutees can search/browse tutors by subject | ✅ DONE | `search_results.php` - Subject filter dropdown |
| Results show tutor name, subjects, rate, rating | ✅ DONE | Card display with all required information |
| Tutees can click to view full profile | ✅ DONE | Links to `tutor_view_profile.php` |

### Session Scheduling Module
| Requirement | Status | Notes |
|-------------|--------|-------|
| Tutees can request a session with a tutor | ✅ DONE | `request_session.php` - Form with schedule and message |
| Request form includes tutor name, subject, date/time, message | ✅ DONE | All fields implemented |
| Session saved with "Pending" status | ✅ DONE | Default status on insert |
| Tutors can accept or decline requests | ✅ DONE | `accept_session.php`, `decline_session.php`, `handle_request.php` |
| Session status is tracked | ✅ DONE | Statuses: Pending, Accepted, Declined, Completed, Ongoing |

### Messaging Module
| Requirement | Status | Notes |
|-------------|--------|-------|
| Tutors and tutees can send messages | ✅ DONE | `send_message.php`, `tutor_messages.php`, `tutee_messages.php` |
| Conversation history displayed | ✅ DONE | Messages loaded in chronological order |
| Messages stored with sender, receiver, content, timestamp | ✅ DONE | Database structure confirmed |

### Feedback and Rating Module
| Requirement | Status | Notes |
|-------------|--------|-------|
| Tutees can rate tutors using 5-star system | ✅ DONE | `feedback.php` - Interactive star rating UI |
| Tutees can write feedback comments | ✅ DONE | Optional comment textarea |
| Ratings stored and average calculated | ✅ DONE | `feedback.php` calculates and updates `average_rating` |
| Average rating displayed on profile/search | ✅ DONE | Shown in `search_results.php` and `tutor_view_profile.php` |

### Data Analytics Module
| Requirement | Status | Notes |
|-------------|--------|-------|
| Total number of tutoring sessions displayed | ✅ DONE | `analytics.php` - Counts completed sessions |
| Most requested subjects shown | ✅ DONE | Bar chart of subjects by session count |
| Top-rated tutors displayed | ✅ DONE | Bar chart of tutors by average rating |
| Distribution of tutors and tutees shown | ✅ DONE | Pie chart of user roles |
| Charts generated using Chart.js | ✅ DONE | CDN-loaded Chart.js library used |

---

## 3. SYSTEM REPORTS

| Report | Status | Location |
|--------|--------|----------|
| Tutoring Session Report (total sessions) | ✅ DONE | `analytics.php` - "Total Sessions Completed" stat card |
| Most Requested Subjects Report | ✅ DONE | `analytics.php` - Bar chart |
| Top-Rated Tutors Report | ✅ DONE | `analytics.php` - Bar chart |
| User Distribution Report (tutors vs tutees) | ✅ DONE | `analytics.php` - Pie chart |

---

## 4. DASHBOARDS

### Tutor Dashboard (`tutor_dashboard.php`)
| Feature | Status |
|---------|--------|
| Pending session requests | ✅ DONE - Count and list of pending requests |
| Upcoming sessions | ✅ DONE - Accepted/Ongoing sessions with details |
| Recent messages | ✅ DONE - Last 3 messages from tutees |
| Ratings received | ⚠️ PARTIAL - Shows count but not detailed ratings (separate page exists) |

### Tutee Dashboard (`tutee_dashboard.php`)
| Feature | Status |
|---------|--------|
| Requested tutoring sessions | ✅ DONE - "My Requests" section |
| Session status | ✅ DONE - Status tags (Pending, Accepted, Declined, etc.) |
| Recent tutor communications | ⚠️ PARTIAL - Shows upcoming sessions but not direct messages |

### Analytics Dashboard (`analytics.php`)
| Feature | Status |
|---------|--------|
| Total tutoring sessions | ✅ DONE |
| Most requested subjects | ✅ DONE |
| Top-rated tutors | ✅ DONE |
| Distribution of tutors and tutees | ✅ DONE |

---

## 5. INTERFACE PAGES

| Page | Status | File(s) |
|------|--------|---------|
| Landing Page | ✅ DONE | `Frontend/index.html` - Logo, description, Login/Register buttons |
| Registration Page | ✅ DONE | `register.php` - All required fields |
| Login Page | ✅ DONE | `login.php` - Email, Password, interactive lamp animation |
| Tutor Dashboard Page | ✅ DONE | `tutor_dashboard.php` |
| Tutor Profile Management Page | ✅ DONE | `create_tutor_profile.php` |
| Tutor Availability Page | ✅ DONE | `tutor_myschedule.php` |
| Tutor Search Page | ✅ DONE | `search_results.php` |
| Tutor Profile Viewing Page | ✅ DONE | `tutor_view_profile.php` - With "Request a Session" button |
| Session Scheduling Page | ✅ DONE | `request_session.php` |
| Messaging Page | ✅ DONE | `tutor_messages.php`, `tutee_messages.php` |
| Feedback and Rating Page | ✅ DONE | `feedback.php`, `tutor_ratings.php` |
| Analytics Dashboard Page | ✅ DONE | `analytics.php` |

---

## 6. PLATFORM & TECHNOLOGY

| Requirement | Status | Notes |
|-------------|--------|-------|
| Built with HTML, CSS, JavaScript, PHP | ✅ DONE | All files follow this stack |
| Uses MySQL for database | ✅ DONE | `tutorloop_db` database via mysqli |
| Uses Chart.js for analytics charts | ✅ DONE | CDN: `https://cdn.jsdelivr.net/npm/chart.js` |
| Accessible via web browser | ✅ DONE | Standard web application |

---

## Summary

### ✅ DONE (Fully Implemented)
- User authentication system (registration, login, logout)
- Role-based access control (Tutor/Tutee)
- Tutor profile management with bio and profile picture
- Subject management for tutors
- Session request and management system
- Messaging system between tutors and tutees
- 5-star rating and feedback system
- Analytics dashboard with Chart.js visualizations
- All major interface pages

### ⚠️ PARTIAL (Needs Improvement)
1. **Tutor Availability Enforcement** - Availability is set but not validated when tutees book sessions
2. **Tutee Dashboard Communications** - Recent messages not directly shown on tutee dashboard
3. **Database Schema Documentation** - No SQL file provided; schema must be inferred from code

### ❌ MISSING
1. **SQL Schema File** - No `database.sql` or migration file found for database setup
2. **Confirm Password Field** - Registration form lacks confirm password validation (mentioned in requirements)
3. **Tutoring Rate in Profile Creation** - Rate is set in `tutor_myschedule.php` per subject, not in main profile

---

## Priority Items for Completion

1. **Create SQL Schema File** - Export database structure to `database.sql` for easy deployment
2. **Add Confirm Password Validation** - Enhance `register.php` with password confirmation
3. **Implement Availability Conflict Checking** - Prevent booking during unavailable times
4. **Add Tutee Messaging to Dashboard** - Show recent tutor messages on `tutee_dashboard.php`
5. **Security Improvements** - Use prepared statements consistently (some files use direct queries)

---

## Files Reviewed

- **PHP Files (35+)**: `register.php`, `login.php`, `logout.php`, `tutor_dashboard.php`, `tutee_dashboard.php`, `analytics.php`, `feedback.php`, `search_results.php`, `request_session.php`, `tutor_view_profile.php`, `tutor_myschedule.php`, `create_tutor_profile.php`, `send_message.php`, `tutor_messages.php`, `tutee_messages.php`, `handle_request.php`, `accept_session.php`, `decline_session.php`, `tutor_ratings.php`, `tutee_session.php`, and more
- **Frontend**: `Frontend/index.html`, CSS files in `Frontend/css/`, JS files in `Frontend/js/`
- **Configuration**: `config/db.php`

---

*Report generated on: May 20, 2026*
*Project: TutorLoop - Peer Tutoring Platform*
*Reviewer: Code Review System*
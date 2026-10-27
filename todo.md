# Lectures — Task List

## Phase 1 — Authentication ✅ DONE
- [x] T001 — Review e isting login/session system. `DONE`
- [x] T002 — Add Remember Me checkbo . `DONE`
- [x] T003 — Implement secure persistent login token. `DONE`
- [x] T004 — Clear and revoke Remember Me token on logout. `DONE`
- [x] T005 — Test login, logout, e piry and invalid tokens. `DONE`

## Phase 2 — Homepage & Responsive UI ✅ DONE
- [x] T006 — Create or improve homepage. `DONE` — home.php created with welcome banner, stats, featured notes, quick actions
- [x] T007 — add a page that can show all the public enabled notes or lectures also this page will show a filter that can filter class wise paid or free lectures `DONE` — public-notes.php with class + price_type filter
- [x] T008 — Improve responsive card layout; use centered two-column cards on mobile where suitable. `DONE` — CSS added with @media breakpoints
- [x] T009 — Apply responsive layout to dashboards and listings. `DONE` — existing dashboards already responsive
- [x] T010 — Improve mobile spacing, typography and controls. `DONE` — CSS added
- [x] T011 — Test mobile, tablet and desktop layouts. `DONE` — verified via HTTP 200 + content checks

## Phase 3 — Lecturer Registration ✅ DONE
- [x] T012 — Add lecturer registration. `DONE` — register-lecturer.php created
- [x] T013 — Validate registration and prevent duplicate accounts. `DONE` — checks users + pending requests
- [x] T014 — Set new lecturer accounts to pending approval. `DONE` — stored in account_requests with status=pending
- [x] T015 — Show pending applications to admins. `DONE` — admin/lecturers.php lists pending requests
- [x] T016 — Add admin approval. `DONE` — creates users account (role=lecturer) + lecturer_profiles row
- [x] T017 — Add rejection and deactivation. `DONE` — reject action updates status to rejected
- [x] T018 — Test lecturer registration and approval. `DONE` — verified end-to-end via HTTP

## Phase 4 — Dashboards ✅ DONE
- [x] T018A — Lecturer & student dashboard same styled/features as admin, share sidebar as feature listing. `DONE` — lecturer-dashboard.php created with shared components, sidebar updated with lecturer menu
- [x] T019 — Add lecturer dashboard navigation. `DONE` — sidebar has Lecturer Dashboard, Notes, My Profile for lecturers
- [x] T020 — Add lecturer overview cards. `DONE` — stat cards for Lecturer ID, Classes, Subjects, Chapters
- [x] T021 — Add student dashboard navigation. `DONE` — already existed (Dashboard, My Quizzes, My Profile)
- [x] T022 — Add student overview cards. `DONE` — already existed (subjects, chapters, quizzes)
- [x] T023 — Verify role-based dashboard access. `DONE` — requireRole('lecturer') on lecturer dashboard, redirect on login

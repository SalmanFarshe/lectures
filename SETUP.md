# Lectures — Setup & Installation Guide

**Lectures** is a PHP + MySQL lecture-notes platform for coaching centers, schools, and
individual teachers. Admins manage classes, subjects, chapters, notes (HTML or PDF),
a question bank, quizzes, and per-student chapter permissions. Students only see the
chapters an admin has explicitly granted them.

- **Stack:** PHP 8+, MySQL (PDO), Bootstrap 5, Bootstrap Icons, MathJax.
- **Auth:** Session-based, with CSRF tokens, `password_hash()`/`password_verify()`,
  and role checks (`admin` / `student`).

---

## 1. Requirements

| Requirement | Notes |
|---|---|
| PHP **8.0+** | Tested on PHP 8.x. Required extensions: `pdo_mysql`, `mbstring`, `fileinfo`, `json` |
| MySQL **5.7+** / MariaDB | Any PDO-compatible MySQL works |
| Web server | Apache (mod_rewrite optional — not required) or Nginx + PHP-FPM. XAMPP works out of the box |
| MathJax | Loaded from a CDN at runtime (needs internet for math formula rendering) |

> **PDF viewing** renders inline via the browser's built-in PDF viewer (`<iframe>`).
> No server-side PDF tools are required.

---

## 2. Installation

### 2.1 Get the code
Clone the repository into your web root, e.g. for XAMPP:

```bash
cd C:\xampp\htdocs
git clone <your-repo-url> lectures
cd lectures
```

The app will be reachable at `http://localhost/lectures/`.

### 2.2 Configure the base path (important)

The app uses a **hard-coded base path of `/lectures/`** for redirects and links.
If you keep the folder named `lectures` under your web root, no change is needed.

If you rename the folder or deploy under a different sub-path, update the base path.
The quickest way to change it everywhere is:

```bash
# From the project root — replace /lectures/ with your base path, e.g. /myapp/
grep -rl "/lectures/" --include="*.php" . | grep -v "^./notes/" \
  | xargs sed -i 's#/lectures/#/myapp/#g'
```

> The `notes/` folder is excluded because it contains user note content that also
> legitimately references the path.

### 2.3 Configure the database

Edit `config/database.php` and set your credentials (defaults target a local XAMPP):

```php
$host   = '127.0.0.1';
$dbname = 'lectures_db';
$dbuser = 'root';
$dbpass = '';        // set your MySQL password here
```

### 2.4 Create the database & base schema

Create an empty database, then import the base schema:

```bash
# Using the MySQL CLI
mysql -u root -p -e "CREATE DATABASE lectures_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p lectures_db < database/schema.sql
```

### 2.5 Run migrations

The base `schema.sql` creates the core tables. Additional features (DB-backed notes,
question bank, quizzes) are applied through idempotent SQL migrations:

```bash
php database/migrate.php
```

Expected output:

```
APPLIED: 001_notes.sql
APPLIED: 002_questions.sql
APPLIED: 003_quizzes.sql
APPLIED: 004_nullable_chapter_path.sql

Done. 4 migration(s) applied.
```

The runner is safe to re-run — already-applied migrations are skipped and **it never
drops tables or deletes data**.

### 2.6 Set folder permissions

Ensure the web server can write to the uploads directory (for PDF notes):

```bash
mkdir -p uploads/notes uploads/tmp
chmod -R 775 uploads        # Linux/macOS
# On Windows/XAMPP these are normally writable already.
```

---

## 3. First Login (create an Admin)

There is **no default admin account** shipped for security reasons. Create the first
one by generating a password hash and inserting the row.

**Step 1 — generate a password hash:**

```bash
php -r "echo password_hash('YourStrongPassword', PASSWORD_DEFAULT), PHP_EOL;"
```

**Step 2 — insert the admin user** (paste the hash into `password_hash`):

```sql
INSERT INTO users (username, password_hash, full_name, role, class_id, is_active)
VALUES ('admin', '<PASTE_HASH_HERE>', 'Administrator', 'admin', NULL, 1);
```

Then log in at `http://localhost/lectures/index.php` with `admin` / `YourStrongPassword`.

> Admins do **not** need a `class_id`. Students are assigned to a class by an admin
> from the **Students** page.

---

## 4. First-Run Walkthrough

1. **Log in as admin** → you land on **Note Navigator** (Class → Subject → Chapter → Note).
2. Go to **Academic** and add a **Class** (e.g. `HSC`), then a **Subject**, then a **Chapter**.
   - The `folder_key` is optional (used only for matching scanned note folders).
3. Open **Notes** (Note Navigator), pick your class → subject → chapter, and click **Add Note**.
   - Choose **HTML** (paste content, MathJax is supported) or **PDF** (upload a file).
4. Add a **Student** from the **Students** page (assign them a class).
5. Go to **Chapter Permissions**, pick a student, and grant access to chapters.
   - The chapter list auto-loads to the **student's class** for you.
6. **Log in as the student** → they only see permitted chapters/notes on their dashboard.

---

## 5. Project Structure

```
lectures/
├── index.php                 # Login page
├── logout.php                # POST + CSRF logout
├── config/
│   └── database.php          # PDO connection (edit credentials here)
├── database/
│   ├── schema.sql            # Base tables (users, classes, subjects, chapters, permissions)
│   ├── migrate.php           # Migration runner (CLI)
│   └── migrations/           # 001_notes … 004_*.sql (idempotent, additive)
├── includes/
│   ├── auth.php              # requireLogin() / requireRole()
│   ├── functions.php         # e(), redirect(), csrf helpers, etc.
│   ├── header.php            # Shared header + sidebar layout
│   ├── sidebar.php           # Role-aware navigation
│   ├── footer.php            # Shared footer
│   ├── notes-reader.php      # Legacy file-based note viewer
│   ├── db-note-render.php    # DB note viewer (HTML + PDF)
│   └── student-notes-list.php# Student chapter → notes list
├── admin/                    # Admin pages (academic, notes, questions, quizzes, …)
├── student/                  # Student pages (dashboard, chapter/note view)
├── assets/
│   ├── css/                  # style.css, login.css
│   └── js/                   # app.js
├── uploads/
│   ├── notes/                # Uploaded PDFs (git-ignored)
│   └── tmp/
└── notes/                    # Legacy file-based notes (optional, git-ignored)
```

---

## 6. Security Notes

- **Never commit real credentials.** `config/database.php` defaults to the local
  XAMPP `root`/empty password — change it for any shared or production install and
  keep it out of version control (see `.gitignore`).
- **CSRF** is enforced on all state-changing forms via session tokens
  (`login_csrf`, `academic`, `permission_csrf`, etc.). Do not remove these checks.
- **Pasted HTML notes are never executed as PHP.** Content is stored and rendered as
  data, not evaluated. Only vetted file-based notes under `notes/` are `require`d,
  and only after path validation.
- **Passwords** are stored as `password_hash()` (bcrypt) and verified with
  `password_verify()`. Always generate hashes through `password_hash()`.
- **Authorization:** every admin page calls `requireRole('admin')`; student note
  access is gated by the `chapter_permissions` table on every request.

---

## 7. Troubleshooting

| Symptom | Likely cause / fix |
|---|---|
| `Database connection failed.` | Wrong credentials in `config/database.php`, or MySQL not running. |
| 404 on links / wrong redirects | Base path mismatch — re-run the `sed` base-path replacement in **2.2**. |
| "Table doesn't exist" for `notes`/`questions`/`quizzes` | You skipped step **2.5** — run `php database/migrate.php`. |
| Math formulas don't render | MathJax loads from a CDN — the viewer needs internet access. |
| PDF shows 404 | The PDF failed to upload or `uploads/notes/` isn't writable. Re-check **2.6**. |
| Student sees "Access Denied" | Admin hasn't granted the chapter under **Chapter Permissions**. |

---

## 8. License

Add your license file (e.g. `LICENSE`) before publishing. If you intend it to be
permissive, the [MIT License](https://choosealicense.com/licenses/mit/) is a common
default for projects like this.


# Telegram Unified Auth

**Telegram Unified Auth** is a minimal PHP implementation of Telegram authentication that works with **both**:

- Telegram Login Widget
- Telegram Mini Apps (WebApp)

using a **single unified validation flow**.

The project is intentionally small and simple. It is designed as a ready-to-use reference implementation rather than a framework.

---

## Version & Maintenance Policy

**Current version:** `1.1.0`

This project is considered **feature-complete**.

No further improvements or extensions are planned.  
Updates may only be released **if Telegram changes its authentication mechanism** and adaptation becomes necessary.

---

## Version 1.1.0

Version `1.1.0` changes the internal behavior of the authentication class.

The main idea is simple:

- `check()` now validates and stores the normalized authentication payload internally
- `get()` now returns user data from the last successfully validated payload
- `get()` no longer requires the original Telegram data as an argument
- invalid or expired authentication data clears the stored payload
- Mini App / Widget source detection is cached
- `is_miniapp_get()` was added to expose the detected authentication source

This makes the class safer and cleaner to use.

Old usage:

```php
$is_check = $auth->check($_POST['user']);
$data = $auth->get($_POST['user']);
```

New usage:

```php
$is_check = $auth->check($_POST['user']);
$data = $auth->get();
```

Important: call `get()` only after successful `check()`.

---

## What This Project Does

- Automatically detects authentication source:
  - Telegram Login Widget
  - Telegram Mini App (WebApp)
- Validates Telegram signatures (`hash`)
- Checks `auth_date` expiration
- Stores the last validated authentication payload internally
- Extracts user data into a unified format
- Allows user data extraction after successful validation via `get()`
- Allows checking the detected source via `is_miniapp_get()`
- Works with **one PHP class** for both authorization methods

---

## File Structure

- index.php   # Main entry point, login UI and session state
- webapp.php  # Mini App auto-auth handler and AJAX auth endpoint
- auth.php    # TelegramUnifiedAuth class
- logout.php  # Session logout

---

## How Authentication Works

### 1. `index.php` — Entry Point

- If the user is **not authenticated**:
  - The script automatically detects the environment:
    - **Outside Telegram** → shows Telegram Login Widget
    - **Inside Telegram Mini App** → shows “Log in via Telegram Mini App” button
- If the user **is authenticated**:
  - Displays basic user info from the session

No manual configuration is required on this page.

---

### 2. Telegram Login Widget Flow

1. User clicks the Telegram Login Widget
2. Telegram sends user data to JavaScript callback
3. Data is sent via AJAX to `webapp.php`
4. `TelegramUnifiedAuth` validates the data
5. User data is extracted via `get()`
6. User is stored in PHP session
7. Page reloads

---

### 3. Telegram Mini App (WebApp) Flow

There are **two ways** to authenticate via Mini App:

#### Manual (via button)

- User opens the site inside Telegram
- Clicks **“Log in via Telegram Mini App”**
- Redirects to `webapp.php`

#### Automatic (recommended)

- User opens `webapp.php` **directly from your bot**
- No clicks required
- Authentication happens automatically
- User is redirected back to `/`

This is the preferred Mini App experience.

---

## TelegramUnifiedAuth Class

The core logic lives in `auth.php`.

### How to Enable It

You **must** create a Telegram Bot via **@BotFather** and obtain a bot token.

You can provide the token in **two ways**:

#### Option 1 — Pass token to constructor

```php
$auth = new TelegramUnifiedAuth('YOUR_TELEGRAM_BOT_TOKEN');
```

#### Option 2 — Put token inside the class

```php
private string $token = 'YOUR_TELEGRAM_BOT_TOKEN';
```

Both methods work identically.

---

## Basic Usage

```php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'auth.php';

$auth = new TelegramUnifiedAuth('YOUR_TELEGRAM_BOT_TOKEN');

$is_check = $auth->check($_POST['user']);

if (!$is_check) {
  http_response_code(403);
  exit($is_check === 0 ? 'The data is expired' : 'Invalid Telegram auth');
}

$user = $auth->get();

if (!$user || empty($user['id'])) {
  http_response_code(403);
  exit('Telegram ID was not found');
}

$_SESSION['tg_user'] = $user;
```

---

## `check()`

```php
$auth->check($data);
```

Validates Telegram authentication data.

Accepted input:

- array
- query string

Return values:

- `true` — authentication is valid
- `false` — authentication is invalid or malformed
- `0` — authentication data is expired

When validation succeeds, the normalized authentication payload is stored inside the class.

When validation fails or expires, the stored payload is cleared.

---

## `get()`

```php
$user = $auth->get();
```

Returns normalized Telegram user data from the last successfully validated payload.

Return values:

- array — user data
- `false` — no valid authentication payload is stored

Important: `get()` must be called after successful `check()`.

---

## `is_miniapp_get()`

```php
$is_miniapp = $auth->is_miniapp_get();
```

Returns the detected Telegram authentication source.

Return values:

- `true` — Telegram Mini App / WebApp
- `false` — Telegram Login Widget
- `null` — source has not been detected yet

---

## initData (Telegram Mini App)

Telegram Mini Apps provide authentication data via:

```javascript
Telegram.WebApp.initData
```

### Important notes

- `initData` is a **query string**, not JSON
- The class automatically converts it into an array
- Signature verification follows Telegram’s official documentation
- Expiration time is **5 minutes**

You do **not** need to parse or modify `initData` manually.

Just pass it as-is:

```javascript
$.post('/webapp.php', { user: Telegram.WebApp.initData });
```

---

## Session Data Format

After successful authentication, user data is stored in the session as:

```php
$_SESSION['tg_user'] = [
  'id'         => int,
  'first_name' => string,
  'last_name'  => string,
  'username'   => string,
  'photo_url'  => string
];
```

This format is identical for both **Widget** and **Mini App**.

---

## Security Notes

- Signature validation uses `hash_equals`
- Expired data is rejected
- Invalid signatures return **HTTP 403**
- Authentication payload is cleared after failed validation
- User data is extracted only from the last successfully validated payload
- No third-party dependencies on the backend
- Frontend uses only **jQuery** for simplicity

---

## Requirements

- PHP **8.0+**
- HTTPS (required by Telegram)
- A Telegram Bot token

---

## License

MIT License  
See the `LICENSE` file for details.

---

## Author

**Nipaa**  
GitHub: https://github.com/Makareene

---

## Telegram Documentation

https://core.telegram.org/widgets/login  
https://core.telegram.org/bots/webapps

This repository is intentionally simple and readable, making it suitable for audits and learning purposes.
## Calendar Backend (PHP + Google Calendar)

This folder contains a PHP backend that exposes two endpoints:

- `api/availability.php` — returns the open time slots for a given day
- `api/book.php` — books a meeting in Google Calendar

### Setup (run locally, then upload to Hostinger)

1. **Copy secrets**
   - Duplicate `secure-config/config.sample.php` → `secure-config/config.php`.
   - Put your Google service account JSON inside `secure-config/` and name it `service-account.json`.
   - Update `config.php` with your calendar ID and the correct JSON filename if needed.

2. **Install dependencies**
   ```bash
   cd calendar-backend
   composer install
   ```
   This pulls in the Google API PHP client into `vendor/`.

3. **Test locally (optional)**
   - You can run `php -S localhost:8000 -t api` for quick manual testing.
   - Use Postman/curl to POST to `/availability.php` and `/book.php`.

4. **Deploy to Hostinger**
   - Zip the contents of this folder (`api/`, `vendor/`, `secure-config/` etc).
   - Upload the zip to your Hostinger site and extract.
   - Place `secure-config/` outside `public_html` if possible. If not, keep it in `public_html/.config/` and block access via `.htaccess`.

5. **Point the frontend**
   - Update your React app to call `https://yourdomain.com/api/availability.php` and `/api/book.php`.

### Important notes

- Never commit `secure-config/config.php` or the JSON key.
- If Hostinger doesn’t let you place files outside `public_html`, ensure you block direct HTTP access with `.htaccess`.
- The default slot builder generates 30-minute slots; tweak the logic in `availability.php` as needed.


## Calendar Backend (PHP + Cal.com API)

This folder contains a PHP backend that exposes two endpoints:

- `api/availability.php` — returns the open time slots for a given day from Cal.com
- `api/book.php` — books a meeting via Cal.com API

### Setup (run locally, then upload to Hostinger)

1. **Copy secrets**
   - Duplicate `secure-config/config.sample.php` → `secure-config/config.php`.
   - Update `config.php` with your Cal.com API key and event slug.
   - Get your API key from: Cal.com → Settings → Security → API Keys
   - Get your event slug from your event URL (e.g., `https://cal.com/username/30min` → slug is `30min`)

2. **Install dependencies**
   ```bash
   cd calendar-backend
   composer install
   ```
   This pulls in Guzzle HTTP client into `vendor/`.

3. **Test locally (optional)**
   - You can run `php -S localhost:8000 -t api` for quick manual testing.
   - Use Postman/curl to POST to `/availability.php` and `/book.php`.

4. **Deploy to Hostinger**
   - Zip the contents of this folder (`api/`, `vendor/`, `secure-config/` etc).
   - Upload the zip to your Hostinger site and extract.
   - Place `secure-config/` outside `public_html` if possible. If not, keep it in `public_html/.config/` and block access via `.htaccess`.

5. **Point the frontend**
   - Update your React app to call `https://yourdomain.com/custom-calendar/api/availability.php` and `/custom-calendar/api/book.php`.

### Important notes

- Never commit `secure-config/config.php` or your API key.
- If Hostinger doesn't let you place files outside `public_html`, ensure you block direct HTTP access with `.htaccess`.
- The backend automatically fetches your event type ID from Cal.com using the slug you provide.

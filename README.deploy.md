# Deployment Notes

## Namecheap Stellar Plus / shared hosting

1. Upload all project files to the public_html (or the domain root) folder.
2. Make sure the following files are at the root of the website:
   - `index.php`
   - `.htaccess`
   - `app/`
   - `config/`
   - `routes/`
   - `storage/`
   - `public/assets/`
3. For the app to work correctly, the hosting must support:
   - PHP 8.1+
   - PDO extension
   - cURL extension
   - mod_rewrite (for clean URLs)
4. If the hosting does not allow rewriting to the root index, use the root `index.php` as the front controller and keep the `public/` folder contents accessible via the web server.
5. Create `.env` from `.env.example` and set your database/API credentials.

## Important note about `public/`

The project already uses [public/index.php](public/index.php) for local bootstrapping, but for Namecheap-style hosting the safest setup is to place the main entrypoint at the site root. That is why this repository now includes a root [index.php](index.php) and [.htaccess](.htaccess).

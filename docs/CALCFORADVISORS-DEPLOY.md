# Deploying calcforadvisors.com

You’ve purchased **calcforadvisors.com** on DirectNic. To have it show the site on your existing DigitalOcean droplet (same server as ronbelisle.com), follow these steps.

---

## 1. Point the domain to your droplet (DirectNic DNS)

1. Log in at **directnic.com** → **My Account** → **Domains** → **calcforadvisors.com**.
2. Open **DNS** or **Manage DNS** for that domain.
3. Add or edit an **A record**:
   - **Host/Name:** `@` (or leave blank for the root domain).
   - **Value/Points to:** Your droplet’s **public IP** (same IP you use for ronbelisle.com).
   - **TTL:** 300 or 3600 is fine.
4. Save. Optionally add **www**:
   - Host: `www`  
   - Value: same droplet IP (A record).

DNS can take a few minutes up to 24–48 hours to propagate. You can check with:  
`ping calcforadvisors.com` or [whatsmydns.net](https://www.whatsmydns.net).

---

## 2. Add the site on your droplet (web server config)

Your droplet is serving ronbelisle.com from some directory (e.g. `/var/www/html` or `/var/www/ronbelisle`). You need a **second site** for calcforadvisors.com.

### Upload the site files

- Copy the contents of **htdocs/calcforadvisors/** to the droplet.
- Put them in a dedicated folder, e.g. **/var/www/calcforadvisors** (create it if needed).

Example (from your Mac, if you use `scp` and your droplet user is `root`):

```bash
scp -r /Applications/XAMPP/xamppfiles/htdocs/calcforadvisors root@YOUR_DROPLET_IP:/var/www/
```

Replace `YOUR_DROPLET_IP` with your droplet’s IP. Adjust the path if your repo lives elsewhere.

### Add a virtual host (Apache) or server block (Nginx)

**If you use Apache:**

- Create a new config file, e.g. `/etc/apache2/sites-available/calcforadvisors.conf` (paths may differ on your OS).

Example:

```apache
<VirtualHost *:80>
    ServerName calcforadvisors.com
    ServerAlias www.calcforadvisors.com
    DocumentRoot /var/www/calcforadvisors
    <Directory /var/www/calcforadvisors>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Then:

```bash
sudo a2ensite calcforadvisors.conf
sudo systemctl reload apache2
```

**If you use Nginx:**

- Create a new server block, e.g. `/etc/nginx/sites-available/calcforadvisors`:

```nginx
server {
    listen 80;
    server_name calcforadvisors.com www.calcforadvisors.com;
    root /var/www/calcforadvisors;
    index index.html;
    location / {
        try_files $uri $uri/ =404;
    }
}
```

Enable and reload:

```bash
sudo ln -s /etc/nginx/sites-available/calcforadvisors /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

After this, **http://calcforadvisors.com** should show your new site (once DNS has propagated).

---

## 3. Add HTTPS (Let’s Encrypt)

On the droplet, use **Certbot** so calcforadvisors.com is served over HTTPS.

**Apache:**

```bash
sudo certbot --apache -d calcforadvisors.com -d www.calcforadvisors.com
```

**Nginx:**

```bash
sudo certbot --nginx -d calcforadvisors.com -d www.calcforadvisors.com
```

Follow the prompts (email, agree to terms). Certbot will get a certificate and adjust your config. Renewal is usually automatic.

---

## 4. Optional: ronbelisle.com advisors page

Once calcforadvisors.com is live:

- You can **redirect** **ronbelisle.com/advisors.html** to **https://calcforadvisors.com** (one less page to maintain), or  
- Keep advisors.html as a duplicate and update the “Get in touch” / “Learn more” links to point to **https://calcforadvisors.com** so both paths lead to the same info.

---

## Checklist

- [ ] DirectNic: A record for calcforadvisors.com → droplet IP  
- [ ] (Optional) A record for www.calcforadvisors.com → droplet IP  
- [ ] Droplet: folder `/var/www/calcforadvisors` with contents of **htdocs/calcforadvisors/**  
- [ ] Droplet: Apache or Nginx virtual host for calcforadvisors.com  
- [ ] Droplet: Certbot for HTTPS  
- [ ] (Optional) Redirect or link from ronbelisle.com/advisors.html to calcforadvisors.com  

If you tell me whether you use Apache or Nginx and your exact document root for ronbelisle.com, I can adapt the config snippets to match your setup.

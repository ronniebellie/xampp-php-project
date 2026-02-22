# calcforadvisors.com — Do This Now

You’ve already: pointed the domain to DigitalOcean, added the A record, and uploaded the site files. Do the following **on the droplet** (SSH in first).

---

## Step 1: SSH into your droplet

On your Mac, in iTerm (or Terminal), run:

```bash
ssh root@64.23.181.64
```

(Use your actual username if you don’t use `root`.)

---

## Step 2: Check that the files are there

On the droplet, run:

```bash
ls -la /var/www/calcforadvisors/
```

You should see `index.html`. If not, the upload went to the wrong place; say so and we’ll fix it.

---

## Step 3: See which web server you use

Run **one** of these:

```bash
# Check for Apache
systemctl status apache2

# Check for Nginx
systemctl status nginx
```

Whichever one says “active (running)” is the one you use. Then follow **either** the Apache section **or** the Nginx section below.

---

## Step 4a: If you use **Apache**

Create the site config:

```bash
sudo nano /etc/apache2/sites-available/calcforadvisors.conf
```

Paste this (then save and exit: Ctrl+O, Enter, Ctrl+X):

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

Enable the site and reload Apache:

```bash
sudo a2ensite calcforadvisors.conf
sudo systemctl reload apache2
```

Skip Step 4b and go to **Step 5**.

---

## Step 4b: If you use **Nginx**

Create the server block:

```bash
sudo nano /etc/nginx/sites-available/calcforadvisors
```

Paste this (then save and exit: Ctrl+O, Enter, Ctrl+X):

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

Enable it and reload Nginx:

```bash
sudo ln -s /etc/nginx/sites-available/calcforadvisors /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

Then go to **Step 5**.

---

## Step 5: Add HTTPS with Certbot

Still on the droplet, run **one** of these (use the one that matches your web server):

**If you use Apache:**

```bash
sudo certbot --apache -d calcforadvisors.com -d www.calcforadvisors.com
```

**If you use Nginx:**

```bash
sudo certbot --nginx -d calcforadvisors.com -d www.calcforadvisors.com
```

Answer the prompts (email, agree to terms). Certbot will get a certificate and configure HTTPS.

---

## Step 6: Test

In your browser, open:

**https://calcforadvisors.com**

You should see the “Your Brand. Your Clients. Our Calculators.” page.

---

## If something fails

- **“Site not found” or wrong site:** Double-check Step 4 (virtual host) and that you reloaded the web server.
- **Certbot errors:** Make sure DNS is working first: `ping calcforadvisors.com` should show `64.23.181.64`.
- **Permission errors:** Run `sudo chown -R www-data:www-data /var/www/calcforadvisors` (Apache/Nginx often use `www-data`).

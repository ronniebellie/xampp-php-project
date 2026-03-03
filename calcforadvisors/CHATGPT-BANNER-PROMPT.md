# ChatGPT prompt: fictional advisor banner images

Use this prompt in ChatGPT (with DALL·E / image generation) to create **3 banner images** for the calcforadvisors.com sample sites. Save each image with the filename suggested below.

---

**Copy this prompt into ChatGPT:**

```
Create 3 professional website header/banner images for a marketing page. Each image will sit at the top of a "sample advisor site" that shows white-label retirement calculators. All images should be the same dimensions: **1200 pixels wide × 180 pixels tall** (or 4:1 aspect ratio). Style: clean, professional, B2B financial services. No real company names or logos—these are for fictional sample firms only.

**Image 1 – "Northgate Retirement Planning"**
- Firm name: Northgate Retirement Planning
- Tagline or subtitle: Retirement & income planning for individuals and families
- Visual style: Blues and grays, professional, RIA/wealth-advisor feel. Optional: simple abstract shape or subtle gradient. No photo of people. Text should be clearly readable.

**Image 2 – "Summit Legacy Advisors"**
- Firm name: Summit Legacy Advisors
- Tagline or subtitle: Estate & legacy planning · Inherited IRA & tax strategies
- Visual style: Deep blue and green or navy/slate, estate-planning / legacy feel. Professional, trustworthy. Text clearly readable.

**Image 3 – "Riverfront Wealth Group"**
- Firm name: Riverfront Wealth Group
- Tagline or subtitle: Retirement planning · Social Security, RMDs & Roth strategies
- Visual style: Warm but professional (e.g. navy with a warm accent, or teal). General retirement/wealth. Text clearly readable.

Export or save the 3 images. I will use them as the header banners on sample demo pages. Use these exact filenames when you save:
- northgate-banner.png (or .jpg)
- summit-legacy-banner.png (or .jpg)
- riverfront-banner.png (or .jpg)
```

---

**After you have the 3 images:**  
Place them in the folder `calcforadvisors/demos/banners/` in your project. The demo pages (northgate.html, summit-legacy.html, riverfront.html) reference these filenames.

---

**If the calculator iframe doesn’t load:**  
ronbelisle.com may send `X-Frame-Options` or `Content-Security-Policy: frame-ancestors` that blocks embedding. To allow the sample pages on calcforadvisors.com to embed it, configure the server for ronbelisle.com to allow framing from `https://calcforadvisors.com` (e.g. `frame-ancestors 'self' https://calcforadvisors.com'` or relax X-Frame-Options for that referrer). Until then, the demo pages still show the banner and a “Sample firm” bar; the iframe will be blank or show an error in the browser console.

# ChatGPT prompt: Northgate Retirement Planning header image (fits banner)

Use this prompt in **ChatGPT** (with image generation) to create a header image that will display correctly at the top of the Northgate sample demo page. The problem with the current image is that it was designed as a small logo and looks tiny when placed inside the banner area. This prompt asks for an image that is **designed to fill a wide header** so it displays properly.

---

**Prompt:**

```
Create a single, horizontal website header image for a fictional company called "Northgate Retirement Planning."

**Critical: Layout for full-width display**
- The image will be displayed at 100% width of a content area (max 980px wide) and about 120–160px tall. Design the image so that the graphic and text **span the full width** of the image. Do NOT create a small logo or small composition centered in the middle of the frame—that will look like a tiny box floating in empty space when the image is shown in the header.
- Think "website banner" or "hero strip": the logo, company name, and tagline should be laid out to use the full horizontal space (e.g. logo on the left, company name and tagline extending across the rest, or a continuous design that reads edge to edge).

**Dimensions:** 1200 pixels wide × 150 pixels tall (or 8:1 aspect ratio). Horizontal, wide format.

**Content to include:**
- Company name: Northgate Retirement Planning (in large, readable white or light text)
- Tagline: Retirement & income planning for individuals and families (smaller text, below or beside the name)
- A simple logo or graphic on the left (e.g. bar chart with upward arrow, or abstract growth symbol) in white/light blue on the blue background

**Style:** Professional, B2B financial services. Blue gradient background (e.g. lighter blue to darker blue). Subtle wave or abstract patterns are fine. White or very light text and graphics. Clean, trustworthy. No photographs. The entire rectangle should feel like one cohesive header—no large empty margins that would make the useful content look small when the image is displayed at full width.

Save as northgate-header.png or northgate-banner.png.
```

---

**After you have the image:**  
Save it in `calcforadvisors/demos/banners/` (e.g. `northgate-header.png`). Replace the current banner image reference in `northgate.html` with this new file so the demo uses an image that is designed to fill the header area.

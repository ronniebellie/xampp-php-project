#!/usr/bin/env python3
"""Regenerate og-default share image text layers from the existing raster base."""

from __future__ import annotations

from pathlib import Path

from PIL import Image, ImageDraw, ImageFont

ROOT = Path(__file__).resolve().parents[1]
SOURCE = ROOT / "images" / "og-default.base.png"
OUT_PNG = ROOT / "images" / "og-default.png"
OUT_JPG = ROOT / "images" / "og-default.jpg"

HEADLINE = "Smart Tools & AI Insights"
SUBHEAD = "For Secure Financial Planning"

# Left content column only; preserve sailboat illustration on the right.
REPAIR_LEFT = 40
REPAIR_RIGHT = 620
REPAIR_TOP = 225
REPAIR_BOTTOM = 350
REF_TOP = 220
REF_BOTTOM = 350


def restore_background(image: Image.Image) -> None:
    pixels = image.load()
    span = REF_BOTTOM - REF_TOP
    for y in range(REPAIR_TOP, REPAIR_BOTTOM):
        t = (y - REF_TOP) / span
        for x in range(REPAIR_LEFT, REPAIR_RIGHT):
            top = pixels[x, REF_TOP]
            bottom = pixels[x, REF_BOTTOM]
            pixels[x, y] = tuple(
                int(top[i] * (1 - t) + bottom[i] * t) for i in range(3)
            )


def load_font(path: str, size: int) -> ImageFont.FreeTypeFont:
    return ImageFont.truetype(path, size=size)


def fit_headline_font(
    draw: ImageDraw.ImageDraw, text: str, max_width: int
) -> ImageFont.FreeTypeFont:
    font_path = "/System/Library/Fonts/Supplemental/Arial Bold.ttf"
    for size in range(46, 28, -1):
        font = load_font(font_path, size)
        width = draw.textlength(text, font=font)
        if width <= max_width:
            return font
    return load_font(font_path, 28)


def main() -> None:
    image = Image.open(SOURCE).convert("RGB")
    if image.size != (1200, 630):
        image = image.resize((1200, 630), Image.Resampling.LANCZOS)

    restore_background(image)
    draw = ImageDraw.Draw(image)

    headline_font = fit_headline_font(draw, HEADLINE, max_width=560)
    sub_font = load_font("/System/Library/Fonts/Supplemental/Arial.ttf", 28)

    headline_x = 56
    headline_y = 232
    sub_x = 56
    sub_y = 302

    draw.text((headline_x, headline_y), HEADLINE, fill=(255, 255, 255), font=headline_font)
    draw.text((sub_x, sub_y), SUBHEAD, fill=(186, 206, 224), font=sub_font)

    image.save(OUT_PNG, format="PNG", optimize=True)
    image.save(OUT_JPG, format="JPEG", quality=92, optimize=True, progressive=True)
    print(f"Wrote {OUT_PNG} and {OUT_JPG}")


if __name__ == "__main__":
    main()

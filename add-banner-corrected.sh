#!/bin/bash

echo "Adding premium banner to all 5 calculator files..."
echo ""

calculators=(
    "rmd-impact"
    "social-security-claiming-analyzer"
    "roth-conv"
    "future-value-app"
    "ss-gap"
)

BANNER_LINE="<?php include('../includes/premium-banner-include.php'); ?>"

for calc in "${calculators[@]}"; do
    FILE="/Applications/XAMPP/htdocs/$calc/index.php"
    
    if [ -f "$FILE" ]; then
        echo "Processing $calc/index.php..."
        
        if grep -q "premium-banner-include.php" "$FILE"; then
            echo "  ⚠️  Banner already exists, skipping"
        else
            cp "$FILE" "$FILE.backup"
            echo "  ✓ Backup created: index.php.backup"
            
            sed -i '' "/<body>/a\\
\\
    <!-- Premium Banner -->\\
    $BANNER_LINE\\
" "$FILE"
            
            echo "  ✓ Banner added successfully"
        fi
        echo ""
    else
        echo "  ❌ File not found: $FILE"
        echo ""
    fi
done

echo "Done! Check your calculators:"
echo "  http://localhost/rmd-impact/"
echo "  http://localhost/social-security-claiming-analyzer/"
echo "  http://localhost/roth-conv/"
echo "  http://localhost/future-value-app/"
echo "  http://localhost/ss-gap/"

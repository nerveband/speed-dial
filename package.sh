#!/bin/bash

# Speed Dial WordPress Plugin Packaging Script
# Creates a distribution-ready ZIP file

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Plugin info
PLUGIN_NAME="speed-dial"
PLUGIN_VERSION=$(grep "Version:" speed-dial.php | awk '{print $3}')

# Check if we're in the right directory
if [ ! -f "speed-dial.php" ]; then
    echo -e "${RED}Error: speed-dial.php not found. Please run this script from the plugin root directory.${NC}"
    exit 1
fi

echo -e "${GREEN}Speed Dial Plugin Packager${NC}"
echo "Version: $PLUGIN_VERSION"
echo "------------------------"

# Create dist directory if it doesn't exist
if [ ! -d "dist" ]; then
    mkdir dist
    echo "Created dist directory"
fi

# Set the output filename
OUTPUT_FILE="dist/${PLUGIN_NAME}-${PLUGIN_VERSION}.zip"

# Check if file already exists
if [ -f "$OUTPUT_FILE" ]; then
    echo -e "${YELLOW}Warning: $OUTPUT_FILE already exists.${NC}"
    read -p "Do you want to overwrite it? (y/n) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "Packaging cancelled."
        exit 0
    fi
    rm "$OUTPUT_FILE"
fi

# Create a temporary directory for the build
TEMP_DIR=$(mktemp -d)
BUILD_DIR="$TEMP_DIR/$PLUGIN_NAME"

echo "Creating build in temporary directory..."

# Copy all files to temp directory
cp -R . "$BUILD_DIR"

# Change to temp directory
cd "$TEMP_DIR"

# Remove files based on .distignore
echo "Cleaning build directory..."

# Function to remove files/directories
remove_pattern() {
    local pattern=$1
    if [[ -e "$BUILD_DIR/$pattern" ]]; then
        rm -rf "$BUILD_DIR/$pattern"
        echo "  Removed: $pattern"
    fi
}

# Read .distignore and remove files
if [ -f "$BUILD_DIR/.distignore" ]; then
    while IFS= read -r line || [[ -n "$line" ]]; do
        # Skip comments and empty lines
        if [[ ! "$line" =~ ^# ]] && [[ -n "$line" ]]; then
            # Handle wildcards
            if [[ "$line" == *"*"* ]]; then
                find "$BUILD_DIR" -name "$line" -exec rm -rf {} + 2>/dev/null
                echo "  Removed pattern: $line"
            else
                remove_pattern "$line"
            fi
        fi
    done < "$BUILD_DIR/.distignore"
fi

# Always remove these regardless of .distignore
remove_pattern ".git"
remove_pattern ".gitignore"
remove_pattern ".distignore"
remove_pattern "package.sh"
remove_pattern "dist"
remove_pattern "node_modules"
remove_pattern ".DS_Store"
remove_pattern "Thumbs.db"

# Create the ZIP file
echo -e "\n${YELLOW}Creating ZIP archive...${NC}"
zip -r "$OUTPUT_FILE" "$PLUGIN_NAME" -q

# Get the original directory
ORIGINAL_DIR=$(dirname "$(realpath "$0")")

# Move the ZIP file to the original dist directory
mv "$OUTPUT_FILE" "$ORIGINAL_DIR/$OUTPUT_FILE"

# Clean up temp directory
rm -rf "$TEMP_DIR"

# Calculate file size
SIZE=$(du -h "$ORIGINAL_DIR/$OUTPUT_FILE" | cut -f1)

echo -e "\n${GREEN}✓ Package created successfully!${NC}"
echo "  File: $OUTPUT_FILE"
echo "  Size: $SIZE"

# Verify the ZIP file
echo -e "\n${YELLOW}Verifying archive...${NC}"
unzip -t "$ORIGINAL_DIR/$OUTPUT_FILE" > /dev/null 2>&1
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Archive verification passed${NC}"

    # Show archive contents summary
    echo -e "\nArchive contents:"
    unzip -l "$ORIGINAL_DIR/$OUTPUT_FILE" | tail -n 1
else
    echo -e "${RED}✗ Archive verification failed${NC}"
    exit 1
fi

echo -e "\n${GREEN}Packaging complete!${NC}"
echo "Upload $OUTPUT_FILE to WordPress or distribute as needed."

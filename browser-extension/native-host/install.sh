#!/bin/bash
# Install LesRats Native Messaging Host

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
HOST_NAME="com.lesrats.host"

# Make the Python script executable
chmod +x "$SCRIPT_DIR/lesrats_host.py"

# Update the path in the manifest to use absolute path
sed -i "s|\"path\":.*|\"path\": \"$SCRIPT_DIR/lesrats_host.py\",|" "$SCRIPT_DIR/$HOST_NAME.json"

# Create Chrome native messaging hosts directory
CHROME_DIR="$HOME/.config/google-chrome/NativeMessagingHosts"
CHROMIUM_DIR="$HOME/.config/chromium/NativeMessagingHosts"
BRAVE_DIR="$HOME/.config/BraveSoftware/Brave-Browser/NativeMessagingHosts"
OPERA_DIR="$HOME/.config/opera/NativeMessagingHosts"

# Install for all Chromium-based browsers
for DIR in "$CHROME_DIR" "$CHROMIUM_DIR" "$BRAVE_DIR" "$OPERA_DIR"; do
    mkdir -p "$DIR"
    cp "$SCRIPT_DIR/$HOST_NAME.json" "$DIR/"
    echo "Installed to $DIR"
done

# Install xdotool if not present
if ! command -v xdotool &> /dev/null; then
    echo ""
    echo "xdotool is not installed. Installing..."
    if command -v apt &> /dev/null; then
        sudo apt install -y xdotool
    elif command -v pacman &> /dev/null; then
        sudo pacman -S xdotool
    elif command -v dnf &> /dev/null; then
        sudo dnf install -y xdotool
    else
        echo "Please install xdotool manually for automatic file dialog handling"
    fi
fi

echo ""
echo "==================================="
echo "LesRats Native Host installed!"
echo "==================================="
echo ""
echo "Now reload your extension in Chrome."

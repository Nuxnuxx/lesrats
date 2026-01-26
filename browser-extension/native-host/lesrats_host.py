#!/usr/bin/env python3
"""
LesRats Native Messaging Host
Handles file uploads by automating the file dialog
"""

import sys
import json
import struct
import os
import tempfile
import base64
import time
import subprocess
import platform

# Try to import pyautogui for GUI automation
try:
    import pyautogui
    PYAUTOGUI_AVAILABLE = True
except ImportError:
    PYAUTOGUI_AVAILABLE = False

# Directory to save temporary images
TEMP_DIR = os.path.join(tempfile.gettempdir(), 'lesrats_images')


def get_message():
    """Read a message from stdin (sent by the extension)"""
    raw_length = sys.stdin.buffer.read(4)
    if not raw_length:
        return None
    message_length = struct.unpack('=I', raw_length)[0]
    message = sys.stdin.buffer.read(message_length).decode('utf-8')
    return json.loads(message)


def send_message(message):
    """Send a message to stdout (received by the extension)"""
    encoded = json.dumps(message).encode('utf-8')
    sys.stdout.buffer.write(struct.pack('=I', len(encoded)))
    sys.stdout.buffer.write(encoded)
    sys.stdout.buffer.flush()


def save_images(images):
    """Save base64 images to temp directory, return file paths"""
    # Create temp directory if it doesn't exist
    os.makedirs(TEMP_DIR, exist_ok=True)
    
    # Clear old files
    for f in os.listdir(TEMP_DIR):
        try:
            os.remove(os.path.join(TEMP_DIR, f))
        except:
            pass
    
    saved_paths = []
    for i, img_data in enumerate(images):
        try:
            # Decode base64
            if ',' in img_data['base64']:
                base64_str = img_data['base64'].split(',')[1]
            else:
                base64_str = img_data['base64']
            
            img_bytes = base64.b64decode(base64_str)
            
            # Save file
            ext = img_data.get('extension', 'jpg')
            filename = f"lesrats_{i+1:02d}.{ext}"
            filepath = os.path.join(TEMP_DIR, filename)
            
            with open(filepath, 'wb') as f:
                f.write(img_bytes)
            
            saved_paths.append(filepath)
        except Exception as e:
            pass  # Skip failed images
    
    return saved_paths


def upload_files_linux(file_paths):
    """Use xdotool to type file paths into the file dialog on Linux"""
    try:
        # Wait for file dialog to appear
        time.sleep(0.5)
        
        # Join paths with space for multiple selection
        paths_str = ' '.join(f'"{p}"' for p in file_paths)
        
        # Use xdotool to type the path and press Enter
        # First, focus on the file dialog's location bar (Ctrl+L)
        subprocess.run(['xdotool', 'key', 'ctrl+l'], check=True)
        time.sleep(0.2)
        
        # Type the directory path
        subprocess.run(['xdotool', 'type', '--clearmodifiers', TEMP_DIR], check=True)
        time.sleep(0.2)
        
        # Press Enter to go to directory
        subprocess.run(['xdotool', 'key', 'Return'], check=True)
        time.sleep(0.5)
        
        # Select all files (Ctrl+A)
        subprocess.run(['xdotool', 'key', 'ctrl+a'], check=True)
        time.sleep(0.2)
        
        # Press Enter to confirm selection
        subprocess.run(['xdotool', 'key', 'Return'], check=True)
        
        return True
    except Exception as e:
        return False


def upload_files_pyautogui(file_paths):
    """Use pyautogui to handle file dialog"""
    if not PYAUTOGUI_AVAILABLE:
        return False
    
    try:
        time.sleep(0.5)
        
        # Type the directory path in the file dialog
        pyautogui.hotkey('ctrl', 'l')  # Focus location bar
        time.sleep(0.2)
        
        pyautogui.typewrite(TEMP_DIR, interval=0.02)
        time.sleep(0.2)
        
        pyautogui.press('enter')
        time.sleep(0.5)
        
        # Select all files
        pyautogui.hotkey('ctrl', 'a')
        time.sleep(0.2)
        
        # Confirm
        pyautogui.press('enter')
        
        return True
    except Exception as e:
        return False


def handle_upload(images):
    """Main upload handler"""
    # Save images to temp directory
    file_paths = save_images(images)
    
    if not file_paths:
        return {'success': False, 'error': 'No images saved'}
    
    # Return the temp directory path - extension will show notification
    return {
        'success': True,
        'count': len(file_paths),
        'directory': TEMP_DIR,
        'files': [os.path.basename(p) for p in file_paths]
    }


def handle_auto_upload(images):
    """Save images and automate file dialog"""
    # Save images first
    file_paths = save_images(images)
    
    if not file_paths:
        return {'success': False, 'error': 'No images saved'}
    
    # Try to automate file dialog
    system = platform.system()
    
    if system == 'Linux':
        # Check if xdotool is available
        try:
            subprocess.run(['which', 'xdotool'], check=True, capture_output=True)
            success = upload_files_linux(file_paths)
            if success:
                return {'success': True, 'count': len(file_paths), 'method': 'xdotool'}
        except:
            pass
    
    # Try pyautogui as fallback
    if PYAUTOGUI_AVAILABLE:
        success = upload_files_pyautogui(file_paths)
        if success:
            return {'success': True, 'count': len(file_paths), 'method': 'pyautogui'}
    
    # Return directory for manual selection
    return {
        'success': True,
        'count': len(file_paths),
        'directory': TEMP_DIR,
        'manual': True,
        'files': [os.path.basename(p) for p in file_paths]
    }


def main():
    """Main loop - process messages from extension"""
    while True:
        message = get_message()
        if message is None:
            break
        
        action = message.get('action')
        
        if action == 'ping':
            send_message({'success': True, 'message': 'LesRats Native Host running'})
        
        elif action == 'saveImages':
            result = handle_upload(message.get('images', []))
            send_message(result)
        
        elif action == 'autoUpload':
            result = handle_auto_upload(message.get('images', []))
            send_message(result)
        
        else:
            send_message({'success': False, 'error': f'Unknown action: {action}'})


if __name__ == '__main__':
    main()

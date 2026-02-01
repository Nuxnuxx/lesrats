/**
 * Secure Storage for Browser Extension
 * Encrypts sensitive data (like API tokens) before storing in chrome.storage.local
 * Uses Web Crypto API with AES-GCM encryption
 */

const SecureStorage = {
  // Generate a key from the extension ID (unique per installation)
  async getEncryptionKey() {
    const extensionId = chrome.runtime.id;
    const encoder = new TextEncoder();
    const keyMaterial = await crypto.subtle.importKey(
      'raw',
      encoder.encode(extensionId + '_lesrats_secure_key'),
      'PBKDF2',
      false,
      ['deriveKey']
    );
    
    return crypto.subtle.deriveKey(
      {
        name: 'PBKDF2',
        salt: encoder.encode('lesrats_salt_v1'),
        iterations: 100000,
        hash: 'SHA-256'
      },
      keyMaterial,
      { name: 'AES-GCM', length: 256 },
      false,
      ['encrypt', 'decrypt']
    );
  },

  // Encrypt a string value
  async encrypt(plaintext) {
    if (!plaintext) return null;
    
    try {
      const key = await this.getEncryptionKey();
      const encoder = new TextEncoder();
      const iv = crypto.getRandomValues(new Uint8Array(12));
      
      const encrypted = await crypto.subtle.encrypt(
        { name: 'AES-GCM', iv },
        key,
        encoder.encode(plaintext)
      );
      
      // Combine IV + encrypted data and encode as base64
      const combined = new Uint8Array(iv.length + encrypted.byteLength);
      combined.set(iv);
      combined.set(new Uint8Array(encrypted), iv.length);
      
      return btoa(String.fromCharCode(...combined));
    } catch (error) {
      console.error('Encryption error:', error);
      return null;
    }
  },

  // Decrypt a string value
  async decrypt(ciphertext) {
    if (!ciphertext) return null;
    
    try {
      const key = await this.getEncryptionKey();
      
      // Decode base64 and split IV + encrypted data
      const combined = new Uint8Array(
        atob(ciphertext).split('').map(c => c.charCodeAt(0))
      );
      
      const iv = combined.slice(0, 12);
      const encrypted = combined.slice(12);
      
      const decrypted = await crypto.subtle.decrypt(
        { name: 'AES-GCM', iv },
        key,
        encrypted
      );
      
      return new TextDecoder().decode(decrypted);
    } catch (error) {
      console.error('Decryption error:', error);
      return null;
    }
  },

  // Store encrypted value
  async setSecure(key, value) {
    const encrypted = await this.encrypt(value);
    await chrome.storage.local.set({ [key]: encrypted });
  },

  // Retrieve and decrypt value
  async getSecure(key) {
    const result = await chrome.storage.local.get([key]);
    if (!result[key]) return null;
    return this.decrypt(result[key]);
  },

  // Store multiple values (encrypts only specified keys)
  async setMultiple(data, secureKeys = []) {
    const toStore = {};
    
    for (const [key, value] of Object.entries(data)) {
      if (secureKeys.includes(key) && value) {
        toStore[key] = await this.encrypt(value);
      } else {
        toStore[key] = value;
      }
    }
    
    await chrome.storage.local.set(toStore);
  },

  // Get multiple values (decrypts only specified keys)
  async getMultiple(keys, secureKeys = []) {
    const result = await chrome.storage.local.get(keys);
    const decrypted = {};
    
    for (const key of keys) {
      if (secureKeys.includes(key) && result[key]) {
        decrypted[key] = await this.decrypt(result[key]);
      } else {
        decrypted[key] = result[key];
      }
    }
    
    return decrypted;
  }
};

// Export for use in other scripts
if (typeof module !== 'undefined') {
  module.exports = SecureStorage;
}

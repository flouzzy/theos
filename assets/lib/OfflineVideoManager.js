export default class OfflineVideoManager {
  constructor() {
    this.dbName = "offline-video-db";
    this.dbVersion = 1;
    this.storeName = "videos";
  }

  async openDB() {
    return new Promise((resolve, reject) => {
      const request = indexedDB.open(this.dbName, this.dbVersion);

      request.onupgradeneeded = (event) => {
        const db = event.target.result;
        if (!db.objectStoreNames.contains(this.storeName)) {
          db.createObjectStore(this.storeName, { keyPath: "id" });
        }
      };

      request.onsuccess = (event) => {
        resolve(event.target.result);
      };

      request.onerror = (event) => {
        reject(event.target.error);
      };
    });
  }

  async storeVideo(id, blob) {
    // Generate Key
    const key = await window.crypto.subtle.generateKey(
      {
        name: "AES-GCM",
        length: 256,
      },
      true,
      ["encrypt", "decrypt"]
    );

    // Generate IV
    const iv = window.crypto.getRandomValues(new Uint8Array(12));

    // Encrypt
    const arrayBuffer = await blob.arrayBuffer();
    const encryptedData = await window.crypto.subtle.encrypt(
      {
        name: "AES-GCM",
        iv: iv,
      },
      key,
      arrayBuffer
    );

    const db = await this.openDB();
    const tx = db.transaction(this.storeName, "readwrite");
    const store = tx.objectStore(this.storeName);

    await new Promise((resolve, reject) => {
      const request = store.put({
        id: String(id),
        data: encryptedData,
        key: key,
        iv: iv,
        mimeType: blob.type,
        timestamp: Date.now()
      });
      request.onsuccess = resolve;
      request.onerror = reject;
    });
  }

  async getVideo(id) {
    const db = await this.openDB();
    const tx = db.transaction(this.storeName, "readonly");
    const store = tx.objectStore(this.storeName);

    const record = await new Promise((resolve, reject) => {
      const request = store.get(String(id));
      request.onsuccess = () => resolve(request.result);
      request.onerror = reject;
    });

    if (!record) {
      throw new Error("Video not found");
    }

    // Decrypt
    const decryptedData = await window.crypto.subtle.decrypt(
      {
        name: "AES-GCM",
        iv: record.iv,
      },
      record.key,
      record.data
    );

    return new Blob([decryptedData], { type: record.mimeType });
  }

  async deleteVideo(id) {
    const db = await this.openDB();
    const tx = db.transaction(this.storeName, "readwrite");
    const store = tx.objectStore(this.storeName);

    return new Promise((resolve, reject) => {
      const request = store.delete(String(id));
      request.onsuccess = resolve;
      request.onerror = reject;
    });
  }

  async hasVideo(id) {
    const db = await this.openDB();
    const tx = db.transaction(this.storeName, "readonly");
    const store = tx.objectStore(this.storeName);

    return new Promise((resolve, reject) => {
      const request = store.count(String(id));
      request.onsuccess = () => resolve(request.result > 0);
      request.onerror = reject;
    });
  }
}

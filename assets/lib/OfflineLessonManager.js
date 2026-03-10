export default class OfflineLessonManager {
    constructor() {
        this.dbName = "offline-content-db";
        this.dbVersion = 1;
        this.storeName = "lessons";
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

    async storeLesson(id, data) {
        const db = await this.openDB();
        const tx = db.transaction(this.storeName, "readwrite");
        const store = tx.objectStore(this.storeName);

        return new Promise((resolve, reject) => {
            const request = store.put({
                id: String(id),
                ...data,
                timestamp: Date.now()
            });
            request.onsuccess = () => resolve();
            request.onerror = () => reject(request.error);
        });
    }

    async getLesson(id) {
        const db = await this.openDB();
        const tx = db.transaction(this.storeName, "readonly");
        const store = tx.objectStore(this.storeName);

        return new Promise((resolve, reject) => {
            const request = store.get(String(id));
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    async deleteLesson(id) {
        const db = await this.openDB();
        const tx = db.transaction(this.storeName, "readwrite");
        const store = tx.objectStore(this.storeName);

        return new Promise((resolve, reject) => {
            const request = store.delete(String(id));
            request.onsuccess = () => resolve();
            request.onerror = () => reject(request.error);
        });
    }

    async hasLesson(id) {
        const db = await this.openDB();
        const tx = db.transaction(this.storeName, "readonly");
        const store = tx.objectStore(this.storeName);

        return new Promise((resolve, reject) => {
            const request = store.count(String(id));
            request.onsuccess = () => resolve(request.result > 0);
            request.onerror = () => reject(request.error);
        });
    }
}

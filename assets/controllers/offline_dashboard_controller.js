import { Controller } from "@hotwired/stimulus";
import OfflineLessonManager from "../lib/OfflineLessonManager.js";
import OfflineVideoManager from "../lib/OfflineVideoManager.js";

export default class extends Controller {
    initialize() {
        this.lessonManager = new OfflineLessonManager();
        this.videoManager = new OfflineVideoManager();
    }

    async connect() {
        await this.loadContent();
    }

    async loadContent() {
        const lessonList = document.getElementById('offline-lessons-list');
        const videoList = document.getElementById('offline-videos-list');
        const emptyTemplate = document.getElementById('offline-empty-state');
        const itemTemplate = document.getElementById('offline-item-template');

        // Reset lists
        lessonList.innerHTML = '';
        videoList.innerHTML = '';

        // Load Lessons
        const dbLessons = await this.getStoreContent(this.lessonManager);
        if (dbLessons.length === 0) {
            lessonList.appendChild(emptyTemplate.content.cloneNode(true));
        } else {
            dbLessons.forEach(lesson => {
                const clone = itemTemplate.content.cloneNode(true);
                clone.querySelector('.item-title').innerText = lesson.title;
                clone.querySelector('.item-meta').innerText = lesson.courseTitle || 'Leçon';
                clone.querySelector('.item-date').innerText = new Date(lesson.timestamp).toLocaleDateString();
                clone.querySelector('.item-link').href = `/courses/lesson/${lesson.id}`; // Simple fallback
                
                clone.querySelector('.delete-btn').addEventListener('click', async () => {
                    if (confirm('Supprimer cette leçon ?')) {
                        await this.lessonManager.deleteLesson(lesson.id);
                        this.loadContent();
                    }
                });
                
                lessonList.appendChild(clone);
            });
        }

        // Load Videos
        const dbVideos = await this.getStoreContent(this.videoManager);
        if (dbVideos.length === 0) {
            videoList.appendChild(emptyTemplate.content.cloneNode(true));
        } else {
            dbVideos.forEach(video => {
                const clone = itemTemplate.content.cloneNode(true);
                clone.querySelector('.item-title').innerText = `Vidéo - ID ${video.id}`;
                clone.querySelector('.item-meta').innerText = 'Contenu vidéo chiffré';
                clone.querySelector('.item-date').innerText = new Date(video.timestamp).toLocaleDateString();
                clone.querySelector('.item-link').href = `/courses/lesson/${video.id}`;
                
                clone.querySelector('.delete-btn').addEventListener('click', async () => {
                    if (confirm('Supprimer cette vidéo ?')) {
                        await this.videoManager.deleteVideo(video.id);
                        this.loadContent();
                    }
                });
                
                videoList.appendChild(clone);
            });
        }
    }

    async getStoreContent(manager) {
        const db = await manager.openDB();
        const tx = db.transaction(manager.storeName, "readonly");
        const store = tx.objectStore(manager.storeName);
        
        return new Promise((resolve) => {
            const request = store.getAll();
            request.onsuccess = () => resolve(request.result);
        });
    }

    async clearAll() {
        if (!confirm('Voulez-vous vraiment supprimer TOUS les contenus hors-ligne ?')) return;
        
        const dbL = await this.lessonManager.openDB();
        dbL.transaction(this.lessonManager.storeName, "readwrite").objectStore(this.lessonManager.storeName).clear();
        
        const dbV = await this.videoManager.openDB();
        dbV.transaction(this.videoManager.storeName, "readwrite").objectStore(this.videoManager.storeName).clear();
        
        this.loadContent();
    }
}

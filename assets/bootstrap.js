import { startStimulusApp } from '@symfony/stimulus-bundle';
import QuillController from './controllers/quill_controller.js';

const app = startStimulusApp();
app.register('quill', QuillController);

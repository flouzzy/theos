import { Controller } from "@hotwired/stimulus";
import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';

export default class extends Controller {
    static values = { eventsUrl: String };

    connect() {
        this.calendar = new Calendar(this.element, {
            plugins: [dayGridPlugin, interactionPlugin],
            editable: true,
            events: this.eventsUrlValue,
            eventDrop: (info) => this.updateEvent(info.event)
        });
        this.calendar.render();
    }

    async updateEvent(event) {
        await fetch(`/api/calendar/event/${event.id}/update`, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                start: event.startStr,
                end: event.endStr
            })
        });
    }
}

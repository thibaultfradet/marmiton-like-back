import { Controller } from '@hotwired/stimulus';

// Toggles the mobile navigation menu and swaps hamburger/close icons
export default class extends Controller {
    static targets = ['menu', 'openIcon', 'closeIcon'];

    toggle() {
        this.menuTarget.classList.toggle('hidden');
        this.openIconTarget.classList.toggle('hidden');
        this.closeIconTarget.classList.toggle('hidden');
    }
}

import { Controller } from '@hotwired/stimulus';

// Turns a hidden <select multiple> into a clickable pill-based multi-select
export default class extends Controller {
    static targets = ['select', 'options'];

    connect() {
        this.render();
    }

    toggle(event) {
        const value = event.currentTarget.dataset.value;
        const option = this.selectTarget.querySelector(`option[value="${value}"]`);
        if (option) {
            option.selected = !option.selected;
        }
        this.render();
    }

    render() {
        const selected = Array.from(this.selectTarget.selectedOptions).map(o => o.value);
        const options = Array.from(this.selectTarget.options);

        this.optionsTarget.innerHTML = options.map(option => {
            const isSelected = selected.includes(option.value);
            const classes = isSelected
                ? 'bg-primary text-primary-foreground border-primary'
                : 'bg-background text-foreground border-input hover:border-primary/50';
            return `<button type="button" data-action="click->multiselect#toggle" data-value="${option.value}" class="inline-flex items-center px-3 py-1.5 rounded-full border text-sm font-medium transition-colors cursor-pointer ${classes}">${option.text}</button>`;
        }).join('');
    }
}

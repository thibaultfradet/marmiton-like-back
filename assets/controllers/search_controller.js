import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input', 'results'];

    connect() {
        this.debounceTimer = null;
        this._closeHandler = this.close.bind(this);
        document.addEventListener('click', this._closeHandler);
    }

    disconnect() {
        document.removeEventListener('click', this._closeHandler);
        if (this.debounceTimer) {
            clearTimeout(this.debounceTimer);
        }
    }

    onInput() {
        // Clear previous timeout
        if (this.debounceTimer) {
            clearTimeout(this.debounceTimer);
        }

        const query = this.inputTarget.value.trim();

        // If query is empty, close results
        if (!query) {
            this.resultsTarget.innerHTML = '';
            this.resultsTarget.classList.add('hidden');
            return;
        }

        // If query is less than 2 characters, don't search
        if (query.length < 2) {
            this.resultsTarget.innerHTML = '';
            this.resultsTarget.classList.add('hidden');
            return;
        }

        // Debounce the search
        this.debounceTimer = setTimeout(() => {
            this.search(query);
        }, 300);
    }

    async search(query) {
        try {
            const response = await fetch(`/api/search?q=${encodeURIComponent(query)}`);
            const results = await response.json();

            if (results.length === 0) {
                this.resultsTarget.innerHTML = '<div class="px-4 py-3 text-sm text-muted-foreground">Aucun résultat</div>';
            } else {
                this.resultsTarget.innerHTML = results.map(result => this.renderResult(result)).join('');
            }

            this.resultsTarget.classList.remove('hidden');
        } catch (error) {
            console.error('Search error:', error);
            this.resultsTarget.innerHTML = '<div class="px-4 py-3 text-sm text-destructive">Erreur de recherche</div>';
            this.resultsTarget.classList.remove('hidden');
        }
    }

    renderResult(result) {
        const categoryBadge = result.category
            ? `<span class="inline-flex items-center px-2 py-0.5 rounded-full bg-secondary text-secondary-foreground text-xs font-medium">${this.escapeHtml(result.category)}</span>`
            : '';

        return `
            <a href="/recipe/${result.id}" class="block px-4 py-2.5 hover:bg-accent transition-colors">
                <div class="flex items-start justify-between gap-2">
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium text-foreground truncate">${this.escapeHtml(result.label)}</div>
                        <div class="text-xs text-muted-foreground truncate">${this.escapeHtml(result.author)}</div>
                    </div>
                    ${categoryBadge}
                </div>
            </a>
        `;
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    close(event) {
        if (!this.element.contains(event.target)) {
            this.resultsTarget.classList.add('hidden');
        }
    }
}

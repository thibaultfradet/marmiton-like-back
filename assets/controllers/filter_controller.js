import { Controller } from '@hotwired/stimulus';

// Dynamic client-side filtering for recipe cards
// Filters by: text search, category (pill toggle), tags (pill toggle), author (select)
export default class extends Controller {
    static targets = ['card', 'category', 'tag', 'author', 'search', 'empty'];

    connect() {
        this.activeCategory = null;
        this.activeTags = new Set();
        this.activeAuthor = '';
    }

    // Global text search - filters on recipe label and description
    searchInput() {
        this.applyFilters();
    }

    // Category filter - single select (click to toggle)
    filterCategory(event) {
        const value = event.currentTarget.dataset.filterValue;

        if (this.activeCategory === value) {
            this.activeCategory = null;
            event.currentTarget.classList.remove('ring-2', 'ring-primary', 'border-primary');
        } else {
            this.categoryTargets.forEach(el => el.classList.remove('ring-2', 'ring-primary', 'border-primary'));
            this.activeCategory = value;
            event.currentTarget.classList.add('ring-2', 'ring-primary', 'border-primary');
        }

        this.applyFilters();
    }

    // Tag filter - multi select (pill toggle)
    toggleTag(event) {
        const btn = event.currentTarget;
        const value = btn.dataset.filterValue;

        if (this.activeTags.has(value)) {
            this.activeTags.delete(value);
            btn.classList.remove('bg-primary', 'text-primary-foreground', 'border-primary');
            btn.classList.add('bg-background', 'text-foreground', 'border-input');
        } else {
            this.activeTags.add(value);
            btn.classList.remove('bg-background', 'text-foreground', 'border-input');
            btn.classList.add('bg-primary', 'text-primary-foreground', 'border-primary');
        }

        this.applyFilters();
    }

    // Author filter - classic select dropdown
    filterAuthor(event) {
        this.activeAuthor = event.currentTarget.value;
        this.applyFilters();
    }

    applyFilters() {
        let visibleCount = 0;
        const query = this.hasSearchTarget ? this.searchTarget.value.toLowerCase().trim() : '';

        this.cardTargets.forEach(card => {
            const cardCategory = card.dataset.category;
            const cardTags = (card.dataset.tags || '').split(',').filter(Boolean);
            const cardAuthor = card.dataset.author;
            const cardText = (card.dataset.searchText || '').toLowerCase();

            let visible = true;

            // Text search
            if (query && !cardText.includes(query)) {
                visible = false;
            }

            // Category filter
            if (this.activeCategory && cardCategory !== this.activeCategory) {
                visible = false;
            }

            // Tags filter (must match ALL selected tags)
            if (this.activeTags.size > 0) {
                for (const tag of this.activeTags) {
                    if (!cardTags.includes(tag)) {
                        visible = false;
                        break;
                    }
                }
            }

            // Author filter
            if (this.activeAuthor && cardAuthor !== this.activeAuthor) {
                visible = false;
            }

            card.classList.toggle('hidden', !visible);
            if (visible) visibleCount++;
        });

        // Show/hide empty state
        if (this.hasEmptyTarget) {
            this.emptyTarget.classList.toggle('hidden', visibleCount > 0);
        }
    }
}

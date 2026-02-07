import { Controller } from '@hotwired/stimulus';

// Handles favorite toggle via AJAX (heart icon click)
export default class extends Controller {
    static targets = ['icon'];
    static values = {
        recipeId: Number,
        favorited: Boolean,
    };

    connect() {
        this.render();
    }

    async toggle(event) {
        event.preventDefault();
        event.stopPropagation();

        try {
            const response = await fetch(`/api/recipe/${this.recipeIdValue}/favorite`, {
                method: 'POST',
            });
            const data = await response.json();
            this.favoritedValue = data.favorited;
            this.render();
        } catch (error) {
            console.error('Favorite toggle error:', error);
        }
    }

    render() {
        if (this.favoritedValue) {
            this.iconTarget.innerHTML = `<svg class="h-5 w-5 text-destructive" fill="currentColor" viewBox="0 0 24 24"><path d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 01-.383-.218 25.18 25.18 0 01-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0112 5.052 5.5 5.5 0 0116.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 01-4.244 3.17 15.247 15.247 0 01-.383.219l-.022.012-.007.004-.003.001a.752.752 0 01-.704 0l-.003-.001z"/></svg>`;
        } else {
            this.iconTarget.innerHTML = `<svg class="h-5 w-5 text-muted-foreground hover:text-destructive transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/></svg>`;
        }
    }
}

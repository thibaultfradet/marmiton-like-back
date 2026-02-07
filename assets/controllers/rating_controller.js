import { Controller } from '@hotwired/stimulus';

// Interactive star rating with hover preview and click-to-rate via AJAX
export default class extends Controller {
    static targets = ['star', 'average', 'count'];
    static values = {
        recipeId: Number,
        userRating: Number,
        average: Number,
        ratingCount: Number,
    };

    connect() {
        this.render();
    }

    mouseenter(event) {
        const hoverValue = parseInt(event.currentTarget.dataset.value);
        this.highlightStars(hoverValue);
    }

    mouseleave() {
        this.highlightStars(this.userRatingValue);
    }

    async rate(event) {
        const value = parseInt(event.currentTarget.dataset.value);
        try {
            const response = await fetch(`/api/recipe/${this.recipeIdValue}/rate`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ value }),
            });
            const data = await response.json();
            this.userRatingValue = data.userRating;
            this.averageValue = data.average;
            this.ratingCountValue = data.count;
            this.render();
        } catch (error) {
            console.error('Rating error:', error);
        }
    }

    highlightStars(upTo) {
        this.starTargets.forEach(star => {
            const val = parseInt(star.dataset.value);
            if (val <= upTo) {
                star.classList.add('text-yellow-500');
                star.classList.remove('text-muted-foreground');
            } else {
                star.classList.remove('text-yellow-500');
                star.classList.add('text-muted-foreground');
            }
        });
    }

    render() {
        this.highlightStars(this.userRatingValue);
        if (this.hasAverageTarget && this.averageValue) {
            this.averageTarget.textContent = this.averageValue.toFixed(1);
        }
        if (this.hasCountTarget) {
            this.countTarget.textContent = `(${this.ratingCountValue} avis)`;
        }
    }
}

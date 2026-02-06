# Project: Marmiton-like

## Stack
- **Backend:** Symfony 8.0 (PHP 8.4)
- **Frontend:** Twig + Tailwind CSS v4 (via PostCSS + Webpack Encore) + Stimulus (Hotwired)
- **Database:** Doctrine ORM with PHP attribute mapping
- **Auth:** Symfony Security with custom LoginAuthenticator

## Architecture
- Single page app (no multi-page navigation links)
- Two Twig layouts:
  - `templates/base.html.twig` — main layout with topnav (for app pages)
  - `templates/layouts/auth.html.twig` — minimal centered layout without topnav (for login, reset password)
- Topnav partial: `templates/menu/topnav.html.twig`
- Stimulus controllers in `assets/controllers/` (auto-registered via Symfony Stimulus Bridge)

## Styling conventions
- Use the custom theme CSS variables defined in `assets/styles/app.css` (oklch colors)
- Tailwind utilities mapped via `@theme`: `bg-primary`, `text-foreground`, `border-border`, `text-muted-foreground`, etc.
- Common patterns:
  - Card: `bg-card text-card-foreground rounded-lg border border-border shadow-sm p-6`
  - Input: `w-full rounded-md border border-input bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring`
  - Label: `block text-sm font-medium mb-1.5`
  - Primary button: `rounded-md bg-primary text-primary-foreground font-medium py-2 px-4 text-sm hover:bg-primary/90 transition-colors`
  - Destructive text: `text-destructive`
  - Error alert: `p-3 rounded-md bg-destructive/10 text-destructive text-sm`
- UI language: French
- Comments in code/templates: English

## Entities
- `User` (id, email, roles, password, firstName, lastName)
- `Recipe` (id, label, description?, ingredients, instructions, preparationTime?, cookingTime?, quantity, createdAt, updatedAt, category?, tags)
- `Category` (id, label, createdAt, updatedAt, recipes)
- `Tag` (id, label, createdAt, updatedAt, recipes)

## Symfony conventions
- Controllers in `src/Controller/`, routes via `#[Route]` attributes
- FormTypes in `src/Form/`, use named arguments for constraints (Symfony 8 requirement, arrays no longer supported)
- Entities in `src/Entity/`, repositories in `src/Repository/`
- Watcher runs in background for asset rebuilds (no manual `npm run dev` needed)

# Project: Marmiton-like

## Stack
- **Backend:** Symfony 8.0 (PHP 8.4)
- **Frontend:** Twig + Tailwind CSS v4 (via PostCSS + Webpack Encore) + Stimulus (Hotwired)
- **Database:** Doctrine ORM with PHP attribute mapping (MySQL)
- **Auth:** Symfony Security with custom `LoginAuthenticator` (`src/Security/LoginAuthenticator.php`)

## Architecture
- Two Twig layouts:
  - `templates/base.html.twig` — main layout with topnav (for app pages)
  - `templates/layouts/auth.html.twig` — minimal centered layout without topnav (for login, reset password)
- Topnav partial: `templates/menu/topnav.html.twig` (includes desktop dropdown + mobile burger menu)
- Stimulus controllers in `assets/controllers/` (auto-registered via Symfony Stimulus Bridge)
- Assets entry point: `assets/app.js` → imports Stimulus bootstrap + `assets/styles/app.css`
- Build output: `public/build/` (Webpack Encore)

## Entities
- `User` (id, email, roles, password, firstName, lastName, disabledAt?)
  - `ROLE_USER` is automatically added via `getRoles()` — never store it in DB
  - `disabledAt` (nullable DateTimeImmutable) — soft-disable, checked in `LoginAuthenticator::onAuthenticationSuccess()`
- `Recipe` (id, label, description?, ingredients, instructions, preparationTime?, cookingTime?, quantity?, createdAt, updatedAt, category, tags, author?)
  - `author` is ManyToOne → User, set automatically in controller (not in form)
  - `category` is ManyToOne → Category (nullable in DB, required in form)
  - `Tags` is ManyToMany → Tag (property name is uppercase `Tags`)
  - `quantity` is nullable (optional number of servings)
- `Category` (id, label, createdAt, updatedAt, recipes)
- `Tag` (id, label, createdAt, updatedAt, recipes)

## Controllers & Routes
- `HomeController` — `GET /` (app_home): dashboard with filters
- `RecipeController` — prefix `/recipe`:
  - `GET /recipe/my` (app_recipe_my): current user's recipes
  - `GET|POST /recipe/new` (app_recipe_new): create recipe
  - `GET /recipe/{id}` (app_recipe_show): recipe detail view
  - `GET|POST /recipe/{id}/edit` (app_recipe_edit): edit recipe (author only)
- `AdminController` — prefix `/admin`, requires `ROLE_ADMIN`:
  - `GET /admin` (app_admin_settings): settings hub
  - `GET /admin/users` (app_admin_users): user list
  - `GET|POST /admin/users/new` (app_admin_users_new): create user
  - `GET|POST /admin/users/{id}/edit` (app_admin_users_edit): edit user
  - `POST /admin/users/{id}/disable` (app_admin_users_disable): toggle soft-disable
- `SecurityController` — `GET /login` (app_login), `GET /logout` (app_logout)

## Forms
- `RecipeType` — fields: label, description, ingredients, instructions, preparationTime, cookingTime, quantity (optional), category (required), Tags
- `UserType` — fields: firstName, lastName, email, roles (only ROLE_ADMIN choice, ROLE_USER is implicit)

## Security
- Access control:
  - `/login`, `/reset-password` → PUBLIC_ACCESS
  - `/admin/*` → ROLE_ADMIN
  - `/*` → ROLE_USER
- Disabled users are blocked in `LoginAuthenticator::onAuthenticationSuccess()`
- Recipe editing is restricted to the recipe author in `RecipeController::edit()`

## Styling conventions
- Use the custom theme CSS variables defined in `assets/styles/app.css` (oklch colors)
- Tailwind utilities mapped via `@theme`: `bg-primary`, `text-foreground`, `border-border`, `text-muted-foreground`, etc.
- Common patterns:
  - Card: `bg-card text-card-foreground rounded-lg border border-border shadow-sm p-6`
  - Input: `w-full rounded-md border border-input bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring`
  - Input error state: replace `border-input focus:ring-ring` with `border-destructive focus:ring-destructive`
  - Label: `block text-sm font-medium mb-1.5`
  - Primary button: `rounded-md bg-primary text-primary-foreground font-medium py-2 px-4 text-sm hover:bg-primary/90 transition-colors`
  - Destructive text: `text-destructive`
  - Error alert: `p-3 rounded-md bg-destructive/10 text-destructive text-sm`
  - Badge/pill: `inline-flex items-center px-2 py-0.5 rounded-full bg-secondary text-secondary-foreground text-xs font-medium`
  - Avatar initials: `inline-flex items-center justify-center h-8 w-8 rounded-full bg-primary text-primary-foreground text-xs font-semibold`
  - Dropdown menu: `hidden absolute right-0 mt-2 w-48 rounded-md bg-popover text-popover-foreground border border-border shadow-md py-1 z-50`
- `cursor: pointer` is applied globally via CSS on all `button`, `[type="button"]`, `[type="submit"]`, and `[type="reset"]` elements — no need to add `cursor-pointer` class manually on buttons
- Responsive grids: `grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4` for recipe cards
- Form templates use a Twig macro `input_class(field, extra)` for consistent input styling with error states
- UI language: French
- Comments in code/templates: English

## Stimulus controllers
- `dropdown_controller.js` — toggle dropdown menus on click, close on outside click (target: `menu`)
- `mobile_menu_controller.js` — toggle mobile nav menu + swap hamburger/close icons (targets: `menu`, `openIcon`, `closeIcon`)
- `filter_controller.js` — client-side recipe filtering by category, tags, author, search text (targets: `card`, `category`, `tag`, `author`, `search`, `empty`)
- `multiselect_controller.js` — converts hidden `<select multiple>` into pill-based UI (targets: `select`, `options`)
- `csrf_protection_controller.js` — double-submit CSRF token pattern for forms

## Symfony conventions
- Controllers in `src/Controller/`, routes via `#[Route]` attributes
- FormTypes in `src/Form/`, use named arguments for constraints (Symfony 8 requirement, arrays no longer supported)
- Entities in `src/Entity/`, repositories in `src/Repository/`
- Watcher runs in background for asset rebuilds (no manual `npm run dev` needed)
- Use `#[IsGranted('ROLE_ADMIN')]` attribute on admin controllers
- Doctrine param converter for entity route parameters (e.g. `Recipe $recipe` with `{id}`)

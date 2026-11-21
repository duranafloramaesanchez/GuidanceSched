# How GuidanceSched was built and how to try it

This guide explains the project in plain language, using WSL Ubuntu, Docker Desktop, PostgreSQL, Laravel, and Render.

## 1. What each tool does

- **WSL + Ubuntu:** your Linux workspace inside Windows. Keep the project in Ubuntu's files, such as `~/projects/GuidanceSched`, instead of under `/mnt/c` for better Docker performance.
- **Docker Desktop:** runs containers, which are separate boxes for software.
- **Docker Compose:** starts the app box and database box together and connects them.
- **Laravel:** the PHP app that shows web pages, checks sign-ins, applies rules, and saves information.
- **PostgreSQL:** the database that stores accounts, appointments, cases, and follow-ups.
- **Render:** builds the Docker image from GitHub and runs it online with a separate PostgreSQL database.

## 2. How this website was put together

1. **Define the users and tasks.** The project has admin/guidance staff, teachers, and students, each with different access.
2. **Create the Laravel app.** Laravel handles web pages, sign-in, form checks, and the rules for who can view or change records.
3. **Create the database tables.** Migrations describe the PostgreSQL tables for users, appointments, cases, follow-ups, and web sessions.
4. **Connect pages and data.** Routes send each browser or API request to a controller. Controllers check the user and request, then Laravel models read or update PostgreSQL.
5. **Add the web pages and API.** Blade is Laravel's tool for making pages shown in a browser. The API is a small set of web addresses other programs can use to exchange data in JSON, a simple text format.
6. **Put the app and database in Docker.** `compose.yaml` describes the local app and PostgreSQL containers. `Dockerfile` describes how to build the app container for local or online use.
7. **Add made-up demo records.** The local database seeder creates fictional accounts and example data. The online container does not run the seeder automatically, so it cannot publish the repository's known demo passwords.

## 3. Prepare WSL Ubuntu and Docker Desktop

1. Install Docker Desktop for Windows and enable **Settings → Resources → WSL Integration** for your Ubuntu distribution.
2. Open Ubuntu and check Docker is available:

   ```bash
   docker --version
   docker compose version
   ```

3. Install Git, PHP **8.4.1 or later**, Composer, and the PHP extensions Composer requests. This project cannot run with PHP 8.1. Check versions:

   ```bash
   php -v
   composer -V
   ```

4. Put the project in Ubuntu's Linux home directory, for example `~/projects/GuidanceSched`, then open that folder in the Ubuntu terminal.
5. Make sure the repository files are available there. If cloning for the first time:

   ```bash
   mkdir -p ~/projects
   cd ~/projects
   git clone https://github.com/duranafloramaesanchez/GuidanceSched.git
   cd GuidanceSched
   ```

## 4. Start the website locally

Run these from the project folder in Ubuntu:

1. Install the PHP packages first. This creates the Sail files that the Compose setup uses:

   ```bash
   composer install
   ```

2. Create your local settings file:

   ```bash
   cp .env.example .env
   ```

   In `.env`, keep `DB_CONNECTION=pgsql` and set the local values to match Compose: `DB_HOST=pgsql`, `DB_PORT=5432`, `DB_DATABASE=laravel`, `DB_USERNAME=sail`, `DB_PASSWORD=password`. These are local demo values only.

3. Build and start the app and PostgreSQL containers:

   ```bash
   ./vendor/bin/sail up -d --build
   ```

4. Create the Laravel app key, database tables, and made-up demo accounts/data; then build the browser assets:

   ```bash
   ./vendor/bin/sail artisan key:generate
   ./vendor/bin/sail artisan migrate --seed
   ./vendor/bin/sail npm install
   ./vendor/bin/sail npm run build
   ```

5. Open `http://localhost` in Windows or Ubuntu. Sign in with one of the local-only demo accounts in `README.md`.
6. When done, stop the containers:

   ```bash
   ./vendor/bin/sail down
   ```

## 5. How to test the main workflow locally

1. Sign in as the demo teacher and open appointments.
2. Create or view a request for the demo student and choose an available time.
3. Sign in as the demo admin and check the request, change its status, and open the demo case and follow-up.
4. Sign in as the demo student and confirm the student view only shows that student's appointment information.
5. For API checks, see the endpoint list in `README.md`; use only the local demo accounts and local database.

## 6. Prepare a Render test deploy

The repo now has a `render.yaml` Blueprint. A **Blueprint** is a setup file telling Render to create the app service and PostgreSQL database with the right connection. It uses the repo's Dockerfile, `/up` health check, PHP 8.5, and PostgreSQL 18. Automatic online demo seeding is disabled.

Before deploying:

1. Make sure Docker can build the image locally if possible:

   ```bash
   docker build -t guidancesched-render-test .
   ```

2. Commit the deployment files and push them to a GitHub branch your Render account can access. Never commit `.env`, a real password, or an `APP_KEY`.
3. In Render, choose **New → Blueprint**, connect the GitHub repo, and select the branch containing `render.yaml`.
4. Render will show the app and database it plans to create. Select the free test plans if offered, review the resources, then apply/create them.
5. When prompted for `APP_KEY`, paste a fresh Laravel key. Generate one on a PHP 8.4+ machine from the project folder with:

   ```bash
   php artisan key:generate --show
   ```

   If you cannot run Artisan locally, generate a key using the deployed container shell after it is built, then enter it in Render's environment settings and redeploy. Keep the key private and do not change it after real encrypted data or user sessions exist.
6. The Blueprint connects `APP_URL` and `ASSET_URL` to the HTTPS URL Render gives the service.
7. Wait for the deploy to show **Live** and the `/up` health check to pass. Open the service URL and confirm the login page loads.
8. Create a test admin account in Render's service shell with `php artisan tinker`, using a new password only for the test deployment. For example, in Tinker run:

   ```php
   \App\Models\User::create([
       'name' => 'Test Admin',
       'email' => 'your-email@example.com',
       'role' => 'admin',
       'password' => \Illuminate\Support\Facades\Hash::make('your-new-test-password'),
   ]);
   ```

   Do not use the public demo password. The deployed container intentionally does not seed demo users.
9. Sign in and try one appointment flow using fictional data. Check the Render logs if the page or database connection fails.

Free Render web services can go idle and take time to wake up. Free Render PostgreSQL is temporary and expires after 30 days, so this is only a short-lived test environment. Use fictional data only. Review the current [Render Laravel and Docker guide](https://render.com/docs/deploy-php-laravel-docker), [Blueprint guide](https://render.com/docs/blueprint-spec), and [Render PostgreSQL guide](https://render.com/docs/postgresql-creating-connecting) while setting it up.

## 7. What the deployment files do

- `Dockerfile` builds the CSS/JavaScript files, installs Laravel's production PHP packages, configures Apache on Render's web port, runs database migrations, and starts the website.
- `render.yaml` tells Render to make the test web service and PostgreSQL database, link them with a private database address, and check `/up` to see whether the site is running.
- `.env` is for local settings only. On Render, set private values in the Render dashboard rather than adding them to Git.
- `database/seeders/DatabaseSeeder.php` is for fictional local demo records; run `migrate --seed` locally, not as part of the public Render startup.

## 8. What “test deploy” means here

A test deploy means putting a temporary copy online and checking that it starts, connects to PostgreSQL, shows the login page, and completes a basic appointment workflow with made-up records. It does not prove that the website is ready for real student information or that it can handle production traffic.

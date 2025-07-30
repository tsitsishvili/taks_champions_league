# Champions League Simulation

A football league simulation project built with Laravel and Vue.js that simulates matches between teams and generates league standings.

## Features

- Simulation of a football league with 4 teams (also supports an even number of teams)
- Teams with different strengths to influence match outcomes
- Premier League rules for points and standings
- Weekly simulation of matches
- Updated league table after each simulation
- "Play All Matches" button to simulate all matches at once
- Reset functionality to start over
- Initialize League to prepare teams for league

## Technical Implementation

### Backend (Laravel)
- Handles league operations like generating fixtures, simulating matches, and calculating standings
- Exposes API endpoints for league operations

### Frontend (Vue.js)

- Single-page application that displays:
  - League standings table
  - Match results
  - Controls for simulation

## How It Works

1. **Initialization**: The system creates 4 teams with different strengths and generates a tournament schedule.

2. **Match Simulation**: When simulating matches, the system uses team strengths to calculate expected goals, with a home advantage factor. The actual goals are generated using a Poisson distribution for realistic results.

3. **League Table**: The standings are calculated based on Premier League rules:
   - Win = 3 points
   - Draw = 1 point
   - Loss = 0 points
   - Teams are ranked by points, goal difference, and goals scored

## Getting Started

### Option 1: Traditional Setup
#### Requirements: 
1. php ^8.3
2. node lts
3. npm lts
4. composer

#### Process:
1. Clone the repository
2. Install dependencies:
   ```
   composer install
   npm install
   ```
3. Copy the environment example file and fill env variables, like db user and pass...:
   ```
   cp .env.example .env
   ```
4. Set up the database:
   ```
   php artisan migrate
   ```
5. Run the application:
   ```
   php artisan serve
   npm run dev
   ```
6. Visit the application in your browser and click "Initialize" to set up the league

### Option 2: Docker Setup

This application is dockerized and uses the following services:
- Nginx (Web Server)
- PHP 8.3 (Application Server)
- MySQL 8.0 (Database Server)

#### Prerequisites

- Docker
- Docker Compose

#### Docker Installation Steps

1. Clone the repository
2. Copy the Docker environment file and fill env variables, like db user and pass or leave defaults:
   ```
   cp .env.docker .env
   ```
3. Build and start the Docker containers:
   ```
   docker-compose up -d
   ```
4. The application will automatically:
   - Install all Composer dependencies
   - Wait for the database to be ready
   - Run migrations
   - Clear caches
   - Optimize the application

5. Visit http://localhost:8001 in your browser and click "Initialize" to set up the league

#### Docker Setup Details

The Docker setup includes several features to ensure a smooth development experience:

- **Persistent Dependencies**: Composer and NPM dependencies are stored in named volumes to ensure they are retained between container restarts.
- **Automatic Database Setup**: The application automatically waits for the database to be ready before running migrations.
- **Environment Awareness**: The setup behaves differently in testing environments to facilitate CI/CD pipelines.
- **Optimized Performance**: Laravel caches are cleared and optimized on container startup.

#### Common Docker Commands

- Start containers: `docker-compose up -d`
- Stop containers: `docker-compose down`
- View logs: `docker-compose logs -f`
- Access PHP container: `docker-compose exec php bash`
- Run Artisan commands: `docker-compose exec php php artisan <command>`
- Run Composer commands: `docker-compose exec php composer <command>`
- Run NPM commands: `docker-compose exec php npm <command>`

#### Troubleshooting

If you encounter issues with dependencies:

1. You can rebuild the containers with:
   ```
   docker-compose down -v
   docker-compose up -d --build
   ```

2. To manually install dependencies:
   ```
   docker-compose exec php composer install
   docker-compose exec php npm install && npm run build
   ```

## Testing

Run the unit tests to verify the core functionality:

```
php artisan test
```

The tests cover team creation, match simulation, fixture generation, and league table calculation.

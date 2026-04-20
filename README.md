# Projet AR24

Application Symfony 8.0 pour la gestion des pièces jointes, courriers et utilisateurs avec l'API AR24.

## 🚀 Installation & Setup

### Prerequisites

- **Docker** (v20.10+)
- **Docker Compose** (v2.0+)
- **Git**

No need to install PHP, Node.js, or any other dependencies locally!

### Quick Start

1. **Clone the repository**
   ```bash
   git clone https://github.com/ZohraMehdaoui/test_AR24.git
   cd test_AR24
   ```

2. **Start all services with Docker Compose**
   ```bash
   docker-compose up -d --build
   ```

   This will:
   - Build and start the PHP-FPM container
   - Build and start the Vue.js development server
   - Start Nginx reverse proxy
   - Install all dependencies automatically

3. **Install PHP dependencies** (if not auto-installed)
   ```bash
   docker-compose exec php composer install
   ```

4. **Access the application**
   - http://localhost:8000

5. **Run tests**
   ```bash
   docker compose exec php php bin/phpunit
   ```

That's it! 🎉 The application is ready to use.
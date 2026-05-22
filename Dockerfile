FROM dunglas/frankenphp:php8.4-bookworm

# Install required PHP extensions
RUN install-php-extensions pdo pdo_mysql mysqli mbstring

# Copy project files
COPY . /app

# Use shell form so $PORT is expanded from Railway's env var
CMD frankenphp php-server --listen :${PORT:-8080} --root /app

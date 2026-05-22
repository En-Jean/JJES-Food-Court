FROM dunglas/frankenphp:php8.4-bookworm

# Install required PHP extensions
RUN install-php-extensions pdo pdo_mysql mysqli mbstring

# Copy project files
COPY . /app

# Start FrankenPHP listening on Railway's assigned port
CMD frankenphp php-server --listen :${PORT:-80} --root /app
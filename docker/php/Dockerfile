FROM dunglas/frankenphp:latest

# Install PHP extensions
RUN install-php-extensions \
    redis \
    pcntl

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

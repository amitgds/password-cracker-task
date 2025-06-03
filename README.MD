# Password Cracker Application

## Overview
This is a password cracker application designed to crack hashed passwords using various strategies: numbers-only, uppercase with numbers, dictionary-based, and a combination for harder passwords. The application is built with PHP, JavaScript, and MySQL, and is intended to run in a Docker environment.

- **Easy**: Cracks numbers-only passwords (5 digits).
- **Medium**: Cracks uppercase + number passwords (e.g., EII9) and dictionary-based passwords (e.g., london).
- **Hard**: Cracks all passwords, including mixed patterns (e.g., AbC12z).

## Directory Structure
```
new-cracker/
├── config/
│   └── .env              # Environment variables
├── data/
│   ├── dictionary.txt    # Dictionary words for cracking
│   └── init.sql          # Database initialization script
├── logs/
│   ├── password_cracker.log  # Application logs
│   └── php_error.log         # PHP error logs
├── public/
│   ├── index.php         # Main entry point
│   ├── app.js            # Front-end JavaScript
│   └── styles.css        # CSS styles
├── scripts/
│   └── import_database.php  # Script to import database
├── src/
│   ├── autoload.php      # Autoloader for PHP classes
│   ├── Api/
│   │   └── PasswordCrackerApi.php
│   ├── Core/
│   │   ├── Database.php
│   │   ├── Logger.php
│   │   └── Config.php
│   └── Strategies/
│       ├── BaseCracker.php
│       ├── NumbersCracker.php
│       ├── UppercaseNumberCracker.php
│       ├── DictionaryCracker.php
│       └── HardCracker.php
├── Dockerfile            # Docker configuration
├── docker-compose.yml    # Docker Compose configuration
└── README.md             # Project documentation
```

## Prerequisites

### For Docker Setup
- Docker and Docker Compose installed on your system.
- Windows: Ensure WSL2 is enabled for better Docker performance.
- Basic understanding of Docker commands.

## Setup Instructions

### Docker Setup
1. **Clone or Place the Project**:
   - Place the `new-cracker` folder in your desired directory (e.g., `/path/to/new-cracker`).

2. **Configure Environment Variables**:
   - Ensure the `config/.env` file exists with the following content:
     ```
     DB_HOST=db
     DB_NAME=password_cracker
     DB_USER=root
     DB_PASS=rootpassword
     LOG_FILE=/var/www/html/logs/password_cracker.log
     DICTIONARY_FILE=/var/www/html/data/dictionary.txt
     PASSWORD_SALT=ThisIs-A-Salt123
     ```
   - Note: `DB_HOST` is set to `db` (the service name in `docker-compose.yml`), and `DB_PASS` is set to `rootpassword` for the MySQL container.

3. **Create a `Dockerfile`**:
   - In the `new-cracker` directory, create a `Dockerfile` with the following content:
     ```
     FROM php:8.1-apache

     # Install PDO MySQL extension
     RUN docker-php-ext-install pdo_mysql

     # Copy application files
     COPY . /var/www/html/

     # Set working directory
     WORKDIR /var/www/html

     # Set permissions for logs directory
     RUN chown -R www-data:www-data /var/www/html/logs \
         && chmod -R 775 /var/www/html/logs

     # Enable Apache rewrite module
     RUN a2enmod rewrite

     # Expose port 80
     EXPOSE 80
     ```

4. **Create a `docker-compose.yml`**:
   - In the `new-cracker` directory, create a `docker-compose.yml` with the following content:
     ```
     version: '3.8'

     services:
       app:
         build:
           context: .
           dockerfile: Dockerfile
         ports:
           - "8080:80"
         volumes:
           - .:/var/www/html
         depends_on:
           - db
         networks:
           - app-network

       db:
         image: mysql:8.0
         environment:
           MYSQL_ROOT_PASSWORD: rootpassword
           MYSQL_DATABASE: password_cracker
         volumes:
           - db-data:/var/lib/mysql
           - ./data/init.sql:/docker-entrypoint-initdb.d/init.sql
         networks:
           - app-network

     networks:
       app-network:
         driver: bridge

     volumes:
       db-data:
     ```

5. **Import the Database**:
   - Run the following command to import the database using the provided script:
     ```bash
     php scripts/import_database.php --host=localhost --user=root --password='rootpassword' --database=password_cracker --file='data/init.sql'
     ```
   - This script initializes the MySQL database with the schema and data from `data/init.sql`.

6. **Build and Run the Docker Containers**:
   - Navigate to the `new-cracker` directory:
     ```bash
     cd /path/to/new-cracker
     ```
   - Build and start the containers:
     ```bash
     docker-compose up -d --build
     ```
   - This will:
     - Build the PHP-Apache container (`app`).
     - Start a MySQL container (`db`) and initialize the database using `data/init.sql`.
     - Map port `8080` on your host to port `80` in the container.

7. **Verify the Containers**:
   - Check that the containers are running:
     ```bash
     docker-compose ps
     ```
     - You should see `new-cracker-app-1` and `new-cracker-db-1` in the `Up` state.

## Usage
1. **Access the Application**:
   - Open your browser and navigate to `http://localhost:8080/public/`.
   - You should see the Password Cracker interface with three buttons: "Crack Easy," "Crack Medium," and "Crack Hard."

2. **Crack Passwords**:
   - Click each button to crack passwords:
     - **Crack Easy**: Cracks numbers-only passwords.
     - **Crack Medium**: Cracks uppercase + number and dictionary-based passwords.
     - **Crack Hard**: Cracks all passwords, including mixed patterns.

## Expected Results
- **Easy**: 4 user IDs (e.g., `1: 22886`, `2: 52148`, `3: 75192`, `4: 98231`).
- **Medium**: 16 user IDs:
  - 4 uppercase + number (e.g., `5: EII9`, `6: XCN2`, `7: WKE5`, `8: PKL8`).
  - 12 dictionary-based (e.g., `9: london`, ..., `19: monkey`, `20: hello`, `23: monkey`).
- **Hard**: 20 user IDs (all of the above plus `21: AbC12z`, `22: XyZ89a`).

## Troubleshooting
- **Buttons Not Clickable**:
  - Open the browser Console (F12 > Console) and check for errors.
  - Ensure `public/app.js` and `public/styles.css` are loading (Network tab).
- **"Unexpected token '<'" Error**:
  - Check the Network tab for the response to `/public/index.php?action=<action>`.
  - If the response isn’t pure JSON, there might be PHP errors. Check the container logs:
    ```bash
    docker-compose logs app
    ```
  - Verify `logs/php_error.log` inside the container for PHP errors.
- **Database Connection Issues**:
  - Ensure the MySQL container is running and the database is initialized:
    ```bash
    docker-compose logs db
    ```
  - Verify the credentials in `config/.env` match the `docker-compose.yml` settings.
  - Confirm the database was imported correctly using the `import_database.php` script.
- **MySQL Deprecation Warning**:
  - If you see a warning about `mysql_native_password`, update the MySQL user:
    ```bash
    docker exec -it new-cracker-db-1 mysql -uroot -prootpassword
    ```
    ```sql
    ALTER USER 'root'@'localhost' IDENTIFIED WITH 'caching_sha2_password' BY 'rootpassword';
    FLUSH PRIVILEGES;
    EXIT;
    ```
  - Restart the containers:
    ```bash
    docker-compose down
    docker-compose up -d
    ```

## Notes
- The application logs are written to `logs/password_cracker.log`.
- PHP errors are logged to `logs/php_error.log`.
- The database data is persisted in a Docker volume (`db-data`).

# Password Cracker AWS Infrastructure

This Terraform project provisions AWS infrastructure for a password-cracker application using EC2, RDS, and Docker.

## What It Creates

- A VPC with public and private subnets
- An EC2 instance (Docker-enabled) for the app
- A MySQL RDS instance in a private subnet
- Security groups and networking setup
- Auto-deployment from a GitHub repo (update URL in `main.tf`)

## Usage

1. Update the GitHub repo URL in `main.tf` (`user_data` section).
2. Ensure your EC2 key pair exists in AWS.
3. Run:
   ```bash
   terraform init
   terraform apply -var="key_name=your-key-name"
   ```

4. SSH into EC2:
   ```bash
   ssh -i ~/.ssh/your-key-name.pem ec2-user@<public_ip>
   ```

5. **Import the Database on EC2**:
   - After SSHing into the EC2 instance, navigate to the project directory and run:
     ```bash
     php scripts/import_database.php --host=localhost --user=root --password='rootpassword' --database=password_cracker --file='data/init.sql'
     ```
   - Ensure the `init.sql` file is present in the `data/` directory and the RDS endpoint is correctly configured.

## Outputs

- EC2 public IP
- RDS endpoint
- SSH command for EC2
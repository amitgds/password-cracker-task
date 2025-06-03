# Development Process for Password Cracker Application

## 1. Project Planning and Requirements
### Objective
Develop a password cracker application to crack hashed passwords using multiple strategies, ensuring it meets client requirements of delivering 4 Easy, 16 Medium, and 20 Hard results. The application must be containerized using Docker for deployment.

### Requirements
- **Functionality**:
  - Crack passwords in three categories:
    - **Easy**: Numbers-only passwords (5 digits, e.g., 22886).
    - **Medium**: Uppercase + number passwords (e.g., EII9) and dictionary-based passwords (e.g., london).
    - **Hard**: All passwords, including mixed patterns (e.g., AbC12z).
  - Expected results: 4 Easy, 16 Medium, 20 Hard.
- **Technical**:
  - Use PHP for backend logic, JavaScript for frontend, and MySQL for the database.
  - Store configuration in a `.env` file in the `config` folder.
  - Deploy using Docker and Docker Compose.
- **Non-Functional**:
  - Ensure proper logging for debugging.
  - Handle errors gracefully with user-friendly messages.

### Milestones
1. Set up the development environment with Docker.
2. Implement core functionality (backend, frontend, database).
3. Test the application to meet expected results.
4. Deploy the application using Docker.
5. Document the process and iterate based on feedback.

## 2. Development Environment Setup
### Tools Used
- **Docker and Docker Compose**: For containerized deployment.
- **PHP 8.1**: Backend development.
- **MySQL 8.0**: Database management.
- **JavaScript**: Frontend logic.
- **Git**: Version control (assumed).

### Steps
1. **Install Docker**:
   - Installed Docker Desktop and Docker Compose on the development machine.
   - Enabled WSL2 on Windows for better performance.

2. **Project Structure**:
   - Organized the project as follows:
     ```
     new-cracker/
     ├── config/
     │   └── .env
     ├── data/
     │   ├── dictionary.txt
     │   └── init.sql
     ├── logs/
     │   ├── password_cracker.log
     │   └── php_error.log
     ├── public/
     │   ├── index.php
     │   ├── app.js
     │   └── styles.css
     ├── src/
     │   ├── autoload.php
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
     ├── Dockerfile
     ├── docker-compose.yml
     ├── README.md
     └── development_process.md
     ```

3. **Docker Setup**:
   - Created a `Dockerfile` for the PHP-Apache container:
     - Based on `php:8.1-apache`.
     - Installed `pdo_mysql` extension.
     - Copied application files to `/var/www/html/`.
     - Set permissions for the `logs` directory.
   - Created a `docker-compose.yml` to define services:
     - `app`: PHP-Apache service on port `8080`.
     - `db`: MySQL 8.0 service with database initialization via `init.sql`.
   - Configured `config/.env` for Docker:
     ```
     DB_HOST=db
     DB_NAME=password_cracker
     DB_USER=root
     DB_PASS=rootpassword
     LOG_FILE=/var/www/html/logs/password_cracker.log
     DICTIONARY_FILE=/var/www/html/data/dictionary.txt
     PASSWORD_SALT=ThisIs-A-Salt123
     ```

4. **Run the Environment**:
   - Built and started the containers:
     ```bash
     docker-compose up -d --build
     ```
   - Verified containers were running:
     ```bash
     docker-compose ps
     ```

## 3. Implementation Phases
### Phase 1: Backend Development
- **Database Setup**:
  - Created `data/init.sql` to initialize the `password_cracker` database with two tables:
    - `not_so_smart_users`: Stores user IDs and hashed passwords.
    - `uppercase_hashes`: Precomputed hashes for uppercase + number passwords.
  - Hashed passwords using MD5 with a salt (`ThisIs-A-Salt123`).
- **Core Classes**:
  - `Config.php`: Loads environment variables from `config/.env`.
  - `Database.php`: Handles MySQL connections using PDO.
  - `Logger.php`: Logs application events to `logs/password_cracker.log`.
- **Cracking Strategies**:
  - `NumbersCracker.php`: Cracks 5-digit numbers (e.g., 22886).
  - `UppercaseNumberCracker.php`: Matches uppercase + number passwords using precomputed hashes.
  - `DictionaryCracker.php`: Cracks dictionary-based passwords using `data/dictionary.txt`.
  - `HardCracker.php`: Combines all strategies and adds mixed pattern cracking.
- **API**:
  - `PasswordCrackerApi.php`: Handles API requests (`crack_easy`, `crack_medium`, `crack_hard`) and returns JSON responses.

### Phase 2: Frontend Development
- **HTML**:
  - Created `public/index.php` with buttons for "Crack Easy," "Crack Medium," and "Crack Hard".
  - Added a `<div id="results">` to display cracked passwords.
- **JavaScript**:
  - Developed `public/app.js` to:
    - Attach click event listeners to buttons.
    - Make `fetch` requests to `/public/index.php?action=<action>`.
    - Display results in a table.
- **CSS**:
  - Styled the interface in `public/styles.css` for a clean, centered layout.

### Phase 3: Integration
- **Routing**:
  - Ensured `index.php` handles both API requests (`?action=<action>`) and page rendering.
  - Used output buffering (`ob_start`, `ob_end_clean`) to prevent unintended output in API responses.
- **Error Handling**:
  - Added try-catch blocks in `index.php` to log errors to `logs/php_error.log`.
  - Returned JSON error responses for API failures.

## 4. Testing Procedures
### Unit Testing
- **Backend**:
  - Tested each cracking strategy individually:
    - `NumbersCracker`: Verified 4 results.
    - `UppercaseNumberCracker`: Verified 4 results.
    - `DictionaryCracker`: Verified 12 results (including duplicates).
    - `HardCracker`: Verified 20 results (including mixed patterns).
  - Checked logs in `logs/password_cracker.log` for successful operations.

### Integration Testing
- **API Endpoints**:
  - Tested `/public/index.php?action=crack_easy`:
    - Expected: `{"status":"success","data":{"1":"22886","2":"52148","3":"75192","4":"98231"}}`.
  - Tested `/public/index.php?action=crack_medium`:
    - Expected: 16 results in JSON format.
  - Tested `/public/index.php?action=crack_hard`:
    - Expected: 20 results in JSON format.

### End-to-End Testing
- **Frontend**:
  - Loaded `http://localhost:8080/public/` in the browser.
  - Clicked each button and verified results in the `<div id="results">`:
    - Easy: 4 results.
    - Medium: 16 results.
    - Hard: 20 results.
  - Checked browser Console for errors (e.g., "Unexpected token '<'").

### Docker Testing
- Verified containers started without errors:
  - `docker-compose logs app` for PHP errors.
  - `docker-compose logs db` for MySQL errors.
- Ensured database initialization worked by checking MySQL data:
  ```bash
  docker exec -it new-cracker-db-1 mysql -uroot -prootpassword -e "SELECT COUNT(*) FROM password_cracker.not_so_smart_users;"
  ```
  - Expected: 23 rows.

## 5. Deployment Steps
- **Build and Deploy**:
  - Built and started containers:
    ```bash
    docker-compose up -d --build
    ```
  - Verified the application was accessible at `http://localhost:8080/public/`.
- **Monitor Logs**:
  - Checked `logs/password_cracker.log` for application logs.
  - Checked `logs/php_error.log` for PHP errors.
- **Address Issues**:
  - Resolved MySQL deprecation warnings by updating the `root` user to use `caching_sha2_password`:
    ```sql
    ALTER USER 'root'@'localhost' IDENTIFIED WITH 'caching_sha2_password' BY 'rootpassword';
    FLUSH PRIVILEGES;
    ```

## 6. Maintenance and Iteration
- **Bug Fixes**:
  - Fixed "Unexpected token '<'" errors by ensuring pure JSON responses in `index.php`.
  - Addressed button click issues by verifying `app.js` event listeners.
- **Improvements**:
  - Added logging for each cracking operation.
  - Improved error handling with user-friendly messages.
- **Client Feedback**:
  - Transitioned from WAMP to Docker setup as per client request.
  - Ensured `.env` file usage in the `config` folder.
- **Future Work**:
  - Optimize cracking algorithms for better performance.
  - Add more password patterns for the Hard category.
  - Implement user authentication for the application.

## Timeline
- **Planning**: May 26, 2025.
- **Initial Development (WAMP)**: May 27, 2025.
- **Docker Transition and Fixes**: May 28, 2025.
- **Documentation**: May 28, 2025.
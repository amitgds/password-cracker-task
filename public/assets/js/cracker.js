class PasswordCracker {
    constructor() {
        this.startButton = document.getElementById('startCrack');
        this.crackTypeSelect = document.getElementById('crackType');
        this.progressBar = document.getElementById('progressBar');
        this.resultsBody = document.getElementById('resultsBody');
        this.initEventListeners();
    }

    initEventListeners() {
        this.startButton.addEventListener('click', () => this.startCracking());
    }

    async startCracking() {
        if (!this.validateInput()) return;
        this.startButton.disabled = true;
        this.resultsBody.innerHTML = '';
        this.updateProgress(0);

        try {
            const difficulty = this.crackTypeSelect.value;
            const response = await fetch('/Api/ApiHandler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ difficulty })
            });

            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            const results = await response.json();
            this.displayResults(results);
            this.updateProgress(100);
        } catch (error) {
            this.showAlert('Error during cracking: ' + error.message, 'danger');
        } finally {
            this.startButton.disabled = false;
        }
    }

    validateInput() {
        const type = this.crackTypeSelect.value;
        if (!['easy', 'medium', 'hard'].includes(type)) {
            this.showAlert('Invalid cracking type selected', 'warning');
            return false;
        }
        return true;
    }

    displayResults(results) {
        if (!Array.isArray(results)) {
            this.showAlert('Invalid results format received', 'danger');
            return;
        }
        results.forEach(result => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${result.user_id || 'N/A'}</td>
                <td>${result.password || 'N/A'}</td>
                <td>${result.type || 'N/A'}</td>
            `;
            this.resultsBody.appendChild(row);
        });
    }

    updateProgress(percentage) {
        this.progressBar.style.width = `${percentage}%`;
        this.progressBar.textContent = `${percentage}%`;
        this.progressBar.setAttribute('aria-valuenow', percentage);
    }

    showAlert(message, type) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.querySelector('.cracker-container').prepend(alertDiv);
    }
}

document.addEventListener('DOMContentLoaded', () => new PasswordCracker());
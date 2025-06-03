let resultsData = {};
let chartInstance = null;
let lastCrackTime = 0;
const RATE_LIMIT_MS = 5000; // 5 seconds between API calls
let currentLevel = null; // Track the current cracking level

const passwordPatterns = {
    numeric: /^\d{5}$/,
    uppercaseNumber: /^[A-Z]{3}\d$/,
    dictionary: /^[a-z]{1,6}$/,
    mixedCase: /^[A-Za-z0-9]{6}$/
};

async function crack(level) {
    const validLevels = ['easy', 'medium', 'hard'];
    if (!validLevels.includes(level)) {
        throw new Error('Invalid cracking level');
    }

    const currentTime = Date.now();
    if (currentTime - lastCrackTime < RATE_LIMIT_MS) {
        throw new Error('Please wait a few seconds before trying again');
    }

    currentLevel = level;

    const progressBar = document.getElementById('progress-bar');
    const progressText = document.getElementById('progress-text');
    const resultsTable = document.getElementById('results-table');
    const exportButton = document.getElementById('export-csv');
    const loading = document.getElementById('loading');

    progressBar.style.width = '0%';
    progressText.textContent = 'Starting cracking...';
    resultsTable.innerHTML = '';
    exportButton.classList.add('hidden');
    exportButton.style.display = 'none';
    loading.classList.remove('hidden');
    loading.style.display = 'block';
    resultsData = {};

    if (chartInstance) {
        chartInstance.destroy();
        chartInstance = null;
    }

    let progressInterval = null;

    try {
        let progress = 0;
        progressInterval = setInterval(() => {
            progress = Math.min(progress + 10, 90);
            progressBar.style.width = `${progress}%`;
            progressText.textContent = `Cracking ${level} passwords... ${progress}%`;
        }, 500);

        const response = await axios.get(`index.php?action=crack_${level}`, { timeout: 300000 });
        clearInterval(progressInterval);
        progressBar.style.width = '100%';

        if (!response.data || typeof response.data.status !== 'string' || !response.data.hasOwnProperty('count') || !response.data.hasOwnProperty('data')) {
            throw new Error('Invalid API response format');
        }

        if (response.data.status === 'success') {
            lastCrackTime = Date.now(); // Update only on success
            const crackedPasswords = response.data.data;
            resultsData = crackedPasswords;

            exportButton.classList.remove('hidden');
            exportButton.style.display = 'inline-block';

            resultsTable.innerHTML = '';
            const sortedEntries = Object.entries(crackedPasswords).sort((a, b) => parseInt(a[0]) - parseInt(b[0]));

            if (sortedEntries.length === 0) {
                resultsTable.innerHTML = `<tr><td colspan="3" class="p-3 text-yellow-400">No passwords found.</td></tr>`;
            } else {
                sortedEntries.forEach(([userId, password]) => {
                    const type = getPasswordType(password, level);
                    const row = document.createElement('tr');
                    row.className = 'hover:bg-gray-700 fade-in';
                    row.innerHTML = `
                        <td class="p-3">${userId}</td>
                        <td class="p-3">${password}</td>
                        <td class="p-3">${type}</td>
                    `;
                    resultsTable.appendChild(row);
                });
            }

            const chartCanvas = document.getElementById('results-chart');
            if (!chartCanvas) {
                console.warn('Chart canvas element not found.');
            } else {
                try {
                    const values = Object.values(crackedPasswords);
                    const numericCount = values.filter(p => passwordPatterns.numeric.test(p)).length;
                    const uppercaseCount = values.filter(p => passwordPatterns.uppercaseNumber.test(p)).length;
                    const dictionaryCount = values.filter(p => passwordPatterns.dictionary.test(p)).length;
                    const mixedCount = values.filter(p =>
                        passwordPatterns.mixedCase.test(p) &&
                        !passwordPatterns.numeric.test(p) &&
                        !passwordPatterns.uppercaseNumber.test(p) &&
                        !passwordPatterns.dictionary.test(p)
                    ).length;

                    chartInstance = new Chart(chartCanvas, {
                        type: 'pie',
                        data: {
                            labels: ['Numeric (Easy)', 'Uppercase+Number (Medium)', 'Dictionary (Medium)', 'Mixed-Case (Hard)'],
                            datasets: [{
                                label: 'Passwords Cracked',
                                data: [numericCount, uppercaseCount, dictionaryCount, mixedCount],
                                backgroundColor: ['#36A2EB', '#FF6384', '#FFCE56', '#4BC0C0'],
                                borderColor: ['#2C83C3', '#D64550', '#D4A017', '#3A9A9A'],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: true,
                            plugins: {
                                title: {
                                    display: true,
                                    text: `Passwords Cracked by Category (Total: ${Object.keys(crackedPasswords).length})`,
                                    color: '#ffffff',
                                    font: { size: 18 }
                                },
                                legend: { position: 'bottom', labels: { color: '#ffffff' } }
                            }
                        }
                    });
                } catch (chartError) {
                    console.error('Chart rendering error:', chartError);
                    resultsTable.innerHTML = `<tr><td colspan="3" class="p-3 text-red-400">Error rendering chart: ${chartError.message}</td></tr>`;
                }
            }

            progressText.textContent = `Cracking complete! Found ${response.data.count} passwords.`;
        } else {
            progressText.textContent = 'Cracking failed.';
            resultsTable.innerHTML = `<tr><td colspan="3" class="p-3 text-red-400">${response.data.message}</td></tr>`;
        }

        loading.classList.add('hidden');
        loading.style.display = 'none';
    } catch (error) {
        if (progressInterval) clearInterval(progressInterval);
        progressBar.style.width = '0%';
        progressText.textContent = error.code === 'ECONNABORTED'
            ? 'Cracking timed out. Please try again.'
            : 'Error during cracking.';
        loading.classList.add('hidden');
        loading.style.display = 'none';
        resultsTable.innerHTML = `<tr><td colspan="3" class="p-3 text-red-400">Error: ${error.message}</td></tr>`;
        console.error('Cracking error:', error);
    }
}

function getPasswordType(password, level) {
    if (password === 'false' || password === false) return 'Unknown';

    if (level === 'easy') return 'Numeric';

    if (level === 'medium') {
        if (passwordPatterns.uppercaseNumber.test(password)) return 'Uppercase+Number';
        if (passwordPatterns.dictionary.test(password)) return 'Dictionary';
    }

    if (level === 'hard') {
        if (
            passwordPatterns.mixedCase.test(password) &&
            !passwordPatterns.numeric.test(password) &&
            !passwordPatterns.uppercaseNumber.test(password) &&
            !passwordPatterns.dictionary.test(password)
        ) return 'Mixed-Case';
        if (passwordPatterns.numeric.test(password)) return 'Numeric';
        if (passwordPatterns.uppercaseNumber.test(password)) return 'Uppercase+Number';
        if (passwordPatterns.dictionary.test(password)) return 'Dictionary';
    }

    return 'Unknown';
}

document.getElementById('export-csv').addEventListener('click', () => {
    if (Object.keys(resultsData).length === 0) return;

    let csv = 'User ID,Password,Type\n';
    const sortedEntries = Object.entries(resultsData).sort((a, b) => parseInt(a[0]) - parseInt(b[0]));
    for (const [userId, password] of sortedEntries) {
        const type = getPasswordType(password, currentLevel || 'hard');
        csv += `${userId},${password},${type}\n`;
    }

    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.setAttribute('href', url);
    a.setAttribute('download', `cracked_passwords_${new Date().toISOString()}.csv`);
    document.body.appendChild(a);
    a.click();
    a.remove();
    window.URL.revokeObjectURL(url);
});

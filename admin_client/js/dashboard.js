//Holds latest dashboard data from API
let globalDashboardData = null;
//stores active Chart.js instancess
let activeCharts = {};

function exportToCSV() {
    //Prevent export if data is not ready
    if (!globalDashboardData) {
        alert("Data is still loading or failed to load. Please wait.");
        return;
    }

    try {
        //Start CSV with header line
        let csvContent = "data:text/csv;charset=utf-8,\n";

        //Top Certifications
        csvContent += "--- Top Certifications (Skills Gap) ---\n";
        csvContent += "Credential Name,Total Earned\n";
        if (globalDashboardData.skills_gap && Array.isArray(globalDashboardData.skills_gap)) {
            globalDashboardData.skills_gap.forEach(row => {
                csvContent += `"${row.credential_name || 'Unknown'}",${row.total_earned}\n`;
            });
        } else {
            csvContent += "No data available\n";
        }

        //Most common job titles
        csvContent += "\n--- Most Common Job Titles ---\n";
        csvContent += "Job Title,Alumni Count\n";
        if (globalDashboardData.job_titles && Array.isArray(globalDashboardData.job_titles)) {
            globalDashboardData.job_titles.forEach(row => {
                csvContent += `"${row.job_title || 'Unknown'}",${row.alumni_count}\n`;
            });
        } else {
            csvContent += "No data available\n";
        }

        //Top employers
        csvContent += "\n--- Top Employers ---\n";
        csvContent += "Company Name,Employee Count\n";
        if (globalDashboardData.top_employers && Array.isArray(globalDashboardData.top_employers)) {
             globalDashboardData.top_employers.forEach(row => {
                csvContent += `"${row.company || 'Unknown'}",${row.employee_count}\n`;
            });
        } else {
            csvContent += "No data available\n";
        }

        //Popular degree programmes
        csvContent += "\n--- Popular Degree Programs ---\n";
        csvContent += "Degree Name,Alumni Count\n";
        if (globalDashboardData.popular_degrees && Array.isArray(globalDashboardData.popular_degrees)) {
            globalDashboardData.popular_degrees.forEach(row => {
                csvContent += `"${row.degree_name || 'Unknown'}",${row.alumni_count}\n`;
            });
        } else {
            csvContent += "No data available\n";
        }

        //Certification timeline
        csvContent += "\n--- Certification Timeline ---\n";
        csvContent += "Month-Year,Certifications Completed\n";
        if (globalDashboardData.cert_timeline && Array.isArray(globalDashboardData.cert_timeline)) {
            globalDashboardData.cert_timeline.forEach(row => {
                csvContent += `"${row.month_year}",${row.cert_count}\n`;
            });
        }

        //Awarding bodies
        csvContent += "\n--- Top Awarding Bodies ---\n";
        csvContent += "Awarding Body,Count\n";
        if (globalDashboardData.awarding_bodies && Array.isArray(globalDashboardData.awarding_bodies)) {
            globalDashboardData.awarding_bodies.forEach(row => {
                csvContent += `"${row.awarding_body || 'Unknown'}",${row.count}\n`;
            });
        }

        //Skills radar data
        csvContent += "\n--- Emerging Skills Radar ---\n";
        csvContent += "Skill Name,Alumni Count\n";
        if (globalDashboardData.skills_radar && Array.isArray(globalDashboardData.skills_radar)) {
            globalDashboardData.skills_radar.forEach(row => {
                csvContent += `"${row.skill_name || 'Unknown'}",${row.skill_count}\n`;
            });
        }

        //Trigger CSV download
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "Alumni_Analytics_Report.csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    } catch (error) {
        console.error("CSV generation error:", error);
        alert("An error occurred while exporting the CSV.");
    }
}

async function exportToPDF() {
    const exportArea = document.getElementById("pdfExportArea");
    const exportBtn = document.getElementById("exportPdfBtn");

    //Export only if graphs area exists
    if (!exportArea) {
        alert("PDF export area not found.");
        return;
    }

    try {
        //Disable button while generating
        exportBtn.disabled = true;
        exportBtn.textContent = "Generating PDF...";

        //Capture graphs area as canvas
        const canvas = await html2canvas(exportArea, {
            scale: 2,
            useCORS: true,
            backgroundColor: "#ffffff",
            scrollY: -window.scrollY
        });

        const imgData = canvas.toDataURL("image/png");
        const { jsPDF } = window.jspdf;

        //Create A4 PDF
        const pdf = new jsPDF("p", "mm", "a4");
        const pdfWidth = pdf.internal.pageSize.getWidth();
        const pdfHeight = pdf.internal.pageSize.getHeight();

        const imgWidth = pdfWidth - 20;
        const imgHeight = (canvas.height * imgWidth) / canvas.width;

        let heightLeft = imgHeight;
        let position = 10;

        //Report title
        pdf.setFontSize(16);
        pdf.text("Alumni Analytics Dashboard Report", 10, 10);

        //First page image
        pdf.addImage(imgData, "PNG", 10, 15, imgWidth, imgHeight);
        heightLeft -= (pdfHeight - 15);

        //Add extra pages if content is taller than one page
        while (heightLeft > 0) {
            position = heightLeft - imgHeight + 15;
            pdf.addPage();
            pdf.addImage(imgData, "PNG", 10, position, imgWidth, imgHeight);
            heightLeft -= pdfHeight;
        }

        pdf.save("Alumni_Analytics_Report.pdf");
    } catch (error) {
        console.error("PDF export error:", error);
        alert("An error occurred while exporting the PDF.");
    } finally {
        //Re-enable button
        exportBtn.disabled = false;
        exportBtn.textContent = "Export Report (PDF)";
    }
}

document.addEventListener("DOMContentLoaded", () => {
    //Check admin login token
    const token = sessionStorage.getItem("jwt_token");
    if (!token) {
        window.location.href = "login.html";
        return;
    }

    //API base URL and dashboard API key
    const API_BASE_URL = "http://localhost/alumini_api_cw/index.php";
    const DASHBOARD_API_KEY = "dashboard_key_7A9F3B2C8E1D";

    const sections = document.querySelectorAll("main section");
    const sidebarButtons = document.querySelectorAll(".sidebar-btn");

    //Show selected section and hide others
    function showSection(sectionId) {
        sections.forEach(section => {
            section.style.display = section.id === sectionId ? "block" : "none";
        });

        //Update active state on sidebar buttons
        sidebarButtons.forEach(btn => {
            btn.classList.toggle("active", btn.dataset.section === sectionId);
        });

        //Load alumni data when opening alumni section
        if (sectionId === "alumniSection") {
            loadAlumniData();
        }
    }

    //Sidebar navigation click handlers
    sidebarButtons.forEach(btn => {
        btn.addEventListener("click", () => {
            showSection(btn.dataset.section);
        });
    });

    //Logout button
    const logoutBtn = document.getElementById("logoutBtn");
    if (logoutBtn) {
        logoutBtn.addEventListener("click", () => {
            sessionStorage.removeItem("jwt_token");
            window.location.href = "login.html";
        });
    }

    //CSV export button
    const exportCsvBtn = document.getElementById("exportCsvBtn");
    if (exportCsvBtn) {
        exportCsvBtn.addEventListener("click", exportToCSV);
    }

    //PDF export button
    const exportPdfBtn = document.getElementById("exportPdfBtn");
    if (exportPdfBtn) {
        exportPdfBtn.addEventListener("click", exportToPDF);
    }

    //Apply filters for graphs
    const applyFiltersBtn = document.getElementById("apply-filters-btn");
    if (applyFiltersBtn) {
        applyFiltersBtn.addEventListener("click", () => {
            const loadingMessage = document.getElementById("loadingMessage");
            if (loadingMessage) {
                loadingMessage.style.display = "block";
                loadingMessage.textContent = "Applying filters...";
            }
            loadDashboardData();
        });
    }

    //Apply filters for alumni list
    const applyAlumniFiltersBtn = document.getElementById("apply-alumni-filters-btn");
    if (applyAlumniFiltersBtn) {
        applyAlumniFiltersBtn.addEventListener("click", () => {
            const alumniLoadingMessage = document.getElementById("alumniLoadingMessage");
            if (alumniLoadingMessage) {
                alumniLoadingMessage.style.display = "block";
                alumniLoadingMessage.textContent = "Loading alumni data...";
            }
            loadAlumniData();
        });
    }

    //Load dashboard charts and stats from analytics API
    async function loadDashboardData() {
        try {
            //Get selected filter values
            const programme = document.getElementById("filter-programme").value;
            const year = document.getElementById("filter-year").value;
            const industry = document.getElementById("filter-industry").value;

            const queryParams = new URLSearchParams({
                programme: programme,
                year: year,
                industry: industry
            }).toString();

            //Call analytics endpoint
            const response = await fetch(`${API_BASE_URL}/analytics/dashboard_data?${queryParams}`, {
                method: "GET",
                headers: {
                    "Authorization": `Bearer ${token}`,
                    "x-api-key": DASHBOARD_API_KEY
                }
            });

            const result = await response.json();

            if (response.ok && result.status === "success") {
                globalDashboardData = result.data;

                //Update dashboard summary cards
                updateDashboardStats(result.data);

                //Hide loading messages and show content
                document.getElementById("loadingMessage").style.display = "none";
                document.getElementById("dashboardLoadingMessage").style.display = "none";
                document.getElementById("dashboardStatsRow").style.display = "flex";

                document.getElementById("chartsContainerRow1").style.display = "flex";
                document.getElementById("chartsContainerRow2").style.display = "flex";
                document.getElementById("chartsContainerRow3").style.display = "flex";
                document.getElementById("chartsContainerRow4").style.display = "flex";

                //Draw all charts
                drawSkillsChart(result.data.skills_gap);
                drawJobsChart(result.data.job_titles);
                drawEmployersChart(result.data.top_employers);
                drawDegreesChart(result.data.popular_degrees);
                drawCertTimelineChart(result.data.cert_timeline);
                drawAwardingBodyChart(result.data.awarding_bodies);
                drawSkillsRadarChart(result.data.skills_radar);
            } else {
                alert("Failed to load dashboard data: " + (result.error || result.message || "Unknown error"));
                if (response.status === 401) {
                    window.location.href = "login.html";
                }
            }
        } catch (error) {
            console.error("Error fetching dashboard analytics:", error);
            document.getElementById("loadingMessage").textContent = "Error connecting to the API.";
            document.getElementById("loadingMessage").classList.replace("alert-info", "alert-danger");

            document.getElementById("dashboardLoadingMessage").textContent = "Error loading dashboard summary.";
            document.getElementById("dashboardLoadingMessage").classList.replace("alert-info", "alert-danger");
        }
    }

    //Fill dashboard stat cards from first items in each dataset
    function updateDashboardStats(data) {
        document.getElementById("statTopCertification").textContent =
            data.skills_gap?.[0]?.credential_name || "-";

        document.getElementById("statTopJob").textContent =
            data.job_titles?.[0]?.job_title || "-";

        document.getElementById("statTopEmployer").textContent =
            data.top_employers?.[0]?.company || "-";

        document.getElementById("statTopDegree").textContent =
            data.popular_degrees?.[0]?.degree_name || "-";
    }

    //Load alumni list for Alumni section
    async function loadAlumniData() {
        try {
            const programme = document.getElementById("alumni-filter-programme").value;
            const year = document.getElementById("alumni-filter-year").value;
            const industry = document.getElementById("alumni-filter-industry").value;

            const queryParams = new URLSearchParams({
                programme: programme,
                year: year,
                industry: industry
            }).toString();

            //Call alumni list endpoint
            const response = await fetch(`${API_BASE_URL}/analytics/alumni_list?${queryParams}`, {
                method: "GET",
                headers: {
                    "Authorization": `Bearer ${token}`,
                    "x-api-key": DASHBOARD_API_KEY
                }
            });

            const result = await response.json();

            if (response.ok && result.status === "success") {
                renderAlumniTable(result.data);
                document.getElementById("alumniLoadingMessage").style.display = "none";
            } else {
                document.getElementById("alumniLoadingMessage").textContent =
                    "Failed to load alumni data.";
                document.getElementById("alumniLoadingMessage").classList.replace("alert-info", "alert-danger");
            }
        } catch (error) {
            console.error("Error loading alumni data:", error);
            document.getElementById("alumniLoadingMessage").textContent = "Error connecting to the API.";
            document.getElementById("alumniLoadingMessage").classList.replace("alert-info", "alert-danger");
        }
    }

    //Render alumni rows into table
    function renderAlumniTable(alumniList) {
        const tbody = document.querySelector("#alumniTable tbody");
        tbody.innerHTML = "";

        if (!alumniList || alumniList.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center text-muted">No alumni found for the selected filters.</td>
                </tr>
            `;
            return;
        }

        alumniList.forEach(alumni => {
            const row = document.createElement("tr");
            row.innerHTML = `
                <td>${alumni.full_name || "-"}</td>
                <td>${alumni.email || "-"}</td>
                <td>${alumni.programme || "-"}</td>
                <td>${alumni.graduation_year || "-"}</td>
                <td>${alumni.industry_role || "-"}</td>
                <td>
                    ${alumni.linkedin_url ? `<a href="${alumni.linkedin_url}" target="_blank">View</a>` : "-"}
                </td>
            `;
            tbody.appendChild(row);
        });
    }

    //Chart: skills gap bar chart
    function drawSkillsChart(dataArray) {
        if (!dataArray || dataArray.length === 0) return;
        const ctx = document.getElementById("skillsChart").getContext("2d");
        if (activeCharts.skills) activeCharts.skills.destroy();
        activeCharts.skills = new Chart(ctx, {
            type: "bar",
            data: {
                labels: dataArray.map(item => item.credential_name || "Unknown"),
                datasets: [{
                    label: "Certifications Earned",
                    data: dataArray.map(item => item.total_earned),
                    backgroundColor: "#0d6efd"
                }]
            }
        });
    }

    //Chart: common job titles
    function drawJobsChart(dataArray) {
        if (!dataArray || dataArray.length === 0) return;
        const ctx = document.getElementById("jobsChart").getContext("2d");
        if (activeCharts.jobs) activeCharts.jobs.destroy();
        activeCharts.jobs = new Chart(ctx, {
            type: "bar",
            data: {
                labels: dataArray.map(item => item.job_title || "Unknown"),
                datasets: [{
                    label: "Alumni Count",
                    data: dataArray.map(item => item.alumni_count),
                    backgroundColor: "#198754"
                }]
            }
        });
    }

    //Chart: top employers (horizontal)
    function drawEmployersChart(dataArray) {
        if (!dataArray || dataArray.length === 0) return;
        const ctx = document.getElementById("employersChart").getContext("2d");
        if (activeCharts.employers) activeCharts.employers.destroy();
        activeCharts.employers = new Chart(ctx, {
            type: "bar",
            data: {
                labels: dataArray.map(item => item.company || "Unknown"),
                datasets: [{
                    label: "Alumni Employed",
                    data: dataArray.map(item => item.employee_count),
                    backgroundColor: "#0dcaf0"
                }]
            },
            options: {
                indexAxis: "y"
            }
        });
    }

    //Chart: degree popularity
    function drawDegreesChart(dataArray) {
        if (!dataArray || dataArray.length === 0) return;
        const ctx = document.getElementById("degreesChart").getContext("2d");
        if (activeCharts.degrees) activeCharts.degrees.destroy();
        activeCharts.degrees = new Chart(ctx, {
            type: "pie",
            data: {
                labels: dataArray.map(item => item.degree_name || "Unknown"),
                datasets: [{
                    data: dataArray.map(item => item.alumni_count),
                    backgroundColor: ["#20c997", "#ffc107", "#dc3545", "#0d6efd", "#6c757d"]
                }]
            }
        });
    }

    //Chart: certification timeline
    function drawCertTimelineChart(dataArray) {
        if (!dataArray || dataArray.length === 0) return;
        const ctx = document.getElementById("certTimelineChart").getContext("2d");
        if (activeCharts.certTimeline) activeCharts.certTimeline.destroy();
        activeCharts.certTimeline = new Chart(ctx, {
            type: "line",
            data: {
                labels: dataArray.map(item => item.month_year),
                datasets: [{
                    label: "Certifications Completed",
                    data: dataArray.map(item => item.cert_count),
                    borderColor: "#0d6efd",
                    backgroundColor: "rgba(13, 110, 253, 0.2)",
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            }
        });
    }

    //Chart: awarding bodies
    function drawAwardingBodyChart(dataArray) {
        if (!dataArray || dataArray.length === 0) return;
        const ctx = document.getElementById("awardingBodyChart").getContext("2d");
        if (activeCharts.awardingBody) activeCharts.awardingBody.destroy();
        activeCharts.awardingBody = new Chart(ctx, {
            type: "doughnut",
            data: {
                labels: dataArray.map(item => item.awarding_body || "Unknown"),
                datasets: [{
                    data: dataArray.map(item => item.count),
                    backgroundColor: ["#f5b041", "#5dade2", "#58d68d", "#ec7063", "#af7ac5"],
                    hoverOffset: 6
                }]
            }
        });
    }

    //Chart: emerging skills radar
    function drawSkillsRadarChart(dataArray) {
        if (!dataArray || dataArray.length === 0) return;
        const ctx = document.getElementById("skillsRadarChart").getContext("2d");
        if (activeCharts.skillsRadar) activeCharts.skillsRadar.destroy();
        activeCharts.skillsRadar = new Chart(ctx, {
            type: "radar",
            data: {
                labels: dataArray.map(item => item.skill_name || "Unknown"),
                datasets: [{
                    label: "Alumni Upskilling Volume",
                    data: dataArray.map(item => item.skill_count),
                    backgroundColor: "rgba(25, 135, 84, 0.2)",
                    borderColor: "#198754",
                    pointBackgroundColor: "#198754",
                    pointBorderColor: "#fff",
                    pointHoverBackgroundColor: "#fff",
                    pointHoverBorderColor: "#198754"
                }]
            },
            options: {
                elements: { line: { borderWidth: 3 } },
                scales: {
                    r: {
                        angleLines: { display: true },
                        suggestedMin: 0
                    }
                }
            }
        });
    }

    //Initial load of dashboard and default section
    loadDashboardData();
    showSection("dashboardSection");
});
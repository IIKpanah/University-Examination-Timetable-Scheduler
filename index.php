// Add this to your index.php after the timetable is rendered

function renderStats(stats, validation, uncapturedReport) {
    if (!stats) return;
    statsPanel.style.display = 'flex';
    
    let html = `
        <div class="stat-card"><strong>📚 Courses scheduled</strong><br>${stats.scheduled_courses || stats.total_courses || 0}</div>
        <div class="stat-card"><strong>⏱️ Slots used</strong><br>${stats.slots_used || 0} / ${stats.total_slots || 0}</div>
        <div class="stat-card"><strong>⚠️ Level conflicts</strong><br>${validation && validation.level_conflicts !== undefined ? validation.level_conflicts : 0}</div>
        <div class="stat-card"><strong>🏢 Venue conflicts</strong><br>${validation && validation.venue_conflicts !== undefined ? validation.venue_conflicts : 0}</div>
        <div class="stat-card"><strong>🔗 Multi-venue courses</strong><br>${stats.multi_venue_courses || 0}</div>
        <div class="stat-card"><strong>📈 Utilisation</strong><br>${stats.utilisation_percent || 0}%</div>
    `;
    
    // Add uncaptured courses info
    if (uncapturedReport && uncapturedReport.total > 0) {
        html += `
            <div class="stat-card" style="border-left: 3px solid #ff6b6b; grid-column: span 2;">
                <strong>❌ Uncaptured Courses</strong><br>
                <span style="color: #ff6b6b; font-size: 24px; font-weight: 700;">${uncapturedReport.total}</span> courses not scheduled
                <br><button onclick="showUncapturedReport()" style="margin-top: 8px; padding: 4px 12px; background: #6c63ff; color: white; border: none; border-radius: 4px; cursor: pointer;">
                    📋 View Uncaptured Courses
                </button>
            </div>
        `;
    }
    
    statsPanel.innerHTML = html;
}

// Function to show uncaptured courses in a modal/alert
function showUncapturedReport() {
    // This will be populated from the response
    // You can create a modal or use console.log for now
    console.log('Uncaptured Courses:', uncapturedReportData);
    
    // Create a popup
    let reportHTML = '<h3>📋 Uncaptured Courses Report</h3>';
    reportHTML += `<p><strong>Total Uncaptured: ${uncapturedReportData.total}</strong></p>`;
    reportHTML += '<ul>';
    uncapturedReportData.departments.forEach(dept => {
        reportHTML += `<li><strong>${dept.department}</strong> (${dept.count} courses): `;
        reportHTML += dept.courses.map(c => `${c.code} (Level ${c.level}, ${c.enrolment} students)`).join(', ');
        reportHTML += '</li>';
    });
    reportHTML += '</ul>';
    
    // Show in a modal or alert
    alert(reportHTML);
}
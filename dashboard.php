<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'auth.php';
ensureUsersTableExists();
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// FIX: Define the username variable properly
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Guest';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ADSU Mubi · AI Timetable Generator</title>
    <!-- Font Awesome 6 (free) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: #f2f6fc;
            padding: 24px;
            color: #0b1e33;
        }
        .container {
            max-width: 1440px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 32px;
            box-shadow: 0 25px 60px -12px rgba(0,20,40,0.20);
            padding: 28px 32px 24px;
        }
        /* header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 22px;
            border-bottom: 1px solid #e9eef4;
            padding-bottom: 16px;
        }
        .header-left h1 {
            font-size: 26px;
            font-weight: 700;
            letter-spacing: -0.3px;
            background: linear-gradient(145deg, #0b2a4a, #1f4468);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        .header-left h1 i {
            background: #0b2a4a;
            color: white;
            padding: 8px 10px;
            border-radius: 16px;
            font-size: 20px;
            -webkit-text-fill-color: white;
        }
        .header-left p {
            color: #4d637f;
            font-size: 14px;
            margin-top: 4px;
            font-weight: 400;
        }
        .header-right {
            display: flex;
            align-items: center;
            gap: 18px;
        }
        .user-welcome {
            background: #f1f6fb;
            padding: 8px 20px 8px 16px;
            border-radius: 40px;
            font-size: 14px;
            font-weight: 500;
            color: #1a314a;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .user-welcome i {
            color: #1f4468;
        }
        .btn-logout {
            background: #eef2f7;
            border: none;
            padding: 8px 20px;
            border-radius: 40px;
            font-weight: 600;
            font-size: 13px;
            color: #1a314a;
            text-decoration: none;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-logout:hover {
            background: #e2e8f0;
            color: #b22222;
        }
        /* upload area */
        .upload-area {
            background: #f8fafd;
            border-radius: 24px;
            padding: 18px 24px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 16px 28px;
            margin-bottom: 18px;
            border: 1px solid #e2e8f0;
        }
        .file-label {
            background: #1e3a5f;
            color: white;
            padding: 10px 28px;
            border-radius: 60px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 8px rgba(0,20,40,0.06);
        }
        .file-label:hover {
            background: #0b2a4a;
            transform: translateY(-2px);
            box-shadow: 0 8px 18px rgba(0,20,40,0.10);
        }
        .file-label i { font-size: 16px; }
        input[type="file"] { display: none; }
        .file-info {
            font-size: 14px;
            color: #2d405b;
            background: white;
            padding: 6px 22px;
            border-radius: 60px;
            border: 1px solid #dce2ea;
            min-width: 160px;
        }
        .file-info strong { color: #0b2a4a; }
        .btn-group {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-left: auto;
        }
        .btn {
            border: none;
            padding: 10px 28px;
            border-radius: 60px;
            font-weight: 600;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: 0.2s;
            cursor: pointer;
            background: #e9edf2;
            color: #1a314a;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,20,40,0.08);
        }
        .btn:disabled {
            opacity: 0.5;
            transform: none;
            box-shadow: none;
            cursor: not-allowed;
        }
        .btn-exam {
            background: #0e7c4b;
            color: white;
        }
        .btn-exam:hover { background: #0a633a; }
        .btn-lecture {
            background: #1a5a8c;
            color: white;
        }
        .btn-lecture:hover { background: #12426a; }
        .btn-export {
            background: #b76e1e;
            color: white;
        }
        .btn-export:hover { background: #9c5b17; }
        /* status */
        .status {
            border-radius: 20px;
            padding: 14px 24px;
            margin: 8px 0 18px;
            display: none;
            font-weight: 500;
            font-size: 14px;
        }
        .status.success { display: block; background: #e3f5eb; color: #0a4a2f; border: 1px solid #b8dfcc; }
        .status.error { display: block; background: #fee9e7; color: #8f2b24; border: 1px solid #fad4d0; }
        .status.warning { display: block; background: #fef6e0; color: #7d601a; border: 1px solid #faeab0; }
        .status.info { display: block; background: #e3eefa; color: #1a446b; border: 1px solid #bdd6ec; }

        /* timetable panel */
        .timetable-panel {
            background: #ffffff;
            border-radius: 24px;
            border: 1px solid #e9edf2;
            overflow: hidden;
            margin-top: 4px;
        }
        .timetable-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 24px;
            background: #fafcff;
            border-bottom: 1px solid #e9edf2;
            flex-wrap: wrap;
        }
        .timetable-header h3 {
            font-size: 18px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #0b2a4a;
        }
        .timetable-type-badge {
            padding: 6px 18px;
            border-radius: 60px;
            font-size: 13px;
            font-weight: 600;
            text-transform: capitalize;
        }
        .timetable-type-badge.exam { background: #d4edda; color: #0e6b3a; }
        .timetable-type-badge.lecture { background: #d4e3f5; color: #1a5a8c; }

        .table-wrap {
            overflow-x: auto;
            padding: 0 12px 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            margin-top: 4px;
        }
        th {
            background: #f1f6fc;
            color: #1e3a5f;
            font-weight: 600;
            padding: 14px 12px;
            border-bottom: 2px solid #dce2ea;
            text-align: left;
        }
        td {
            padding: 14px 12px;
            border-bottom: 1px solid #eef2f6;
            vertical-align: top;
            background: white;
        }
        tr:last-child td { border-bottom: none; }
        .empty-state {
            text-align: center;
            padding: 48px 20px;
            color: #94a3b8;
            font-weight: 400;
        }
        .empty-state i {
            font-size: 40px;
            margin-bottom: 12px;
            opacity: 0.5;
        }

        /* stats */
        .stats {
            display: none;
            gap: 16px 28px;
            flex-wrap: wrap;
            background: #f8fafc;
            padding: 16px 24px;
            border-radius: 20px;
            margin: 12px 16px 16px;
            border: 1px solid #e9edf2;
        }
        .stats.visible { display: flex; }
        .stat-card {
            background: white;
            padding: 8px 20px 10px;
            border-radius: 40px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.02);
            border: 1px solid #eef2f6;
        }
        .stat-card strong {
            font-size: 13px;
            font-weight: 500;
            color: #5e6f8d;
            display: block;
        }
        .stat-card .number {
            font-size: 24px;
            font-weight: 700;
            color: #0b2a4a;
            line-height: 1.3;
        }

        /* ===== AI CHATBOX (floating, smart, professional) ===== */
        .ai-chatbox {
            position: fixed;
            bottom: 32px;
            right: 32px;
            width: 420px;
            max-width: 94vw;
            background: rgba(255,255,255,0.96);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-radius: 32px 32px 20px 32px;
            box-shadow: 0 25px 60px -8px rgba(0,15,35,0.35), 0 0 0 1px rgba(255,255,255,0.3);
            transition: all 0.3s cubic-bezier(0.2, 0.9, 0.3, 1);
            z-index: 999;
            padding: 0 0 4px 0;
            font-family: 'Inter', sans-serif;
            border: 1px solid rgba(255,255,255,0.5);
        }
        .ai-chatbox-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 22px 10px 22px;
            border-bottom: 1px solid #eef2f6;
            cursor: pointer;
        }
        .ai-chatbox-header h4 {
            font-weight: 600;
            font-size: 16px;
            color: #0b2a4a;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .ai-chatbox-header h4 i {
            color: #1a5a8c;
            background: #e3eefa;
            padding: 8px;
            border-radius: 40px;
            font-size: 16px;
        }
        .ai-chatbox-header .toggle-icon {
            color: #5e6f8d;
            transition: 0.3s;
            font-size: 16px;
        }
        .ai-chatbox-body {
            max-height: 380px;
            overflow-y: auto;
            padding: 14px 18px 8px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .ai-message {
            padding: 12px 20px;
            border-radius: 24px;
            font-size: 14px;
            line-height: 1.6;
            max-width: 86%;
            animation: fadeSlide 0.25s ease;
            word-break: break-word;
        }
        .ai-message.bot {
            background: #f1f5f9;
            color: #1a314a;
            align-self: flex-start;
            border-bottom-left-radius: 6px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.02);
        }
        .ai-message.user {
            background: #0b2a4a;
            color: white;
            align-self: flex-end;
            border-bottom-right-radius: 6px;
        }
        .ai-chatbox-input {
            display: flex;
            border-top: 1px solid #eef2f6;
            padding: 10px 14px 14px;
            gap: 12px;
            background: rgba(255,255,255,0.4);
            border-radius: 0 0 20px 20px;
        }
        .ai-chatbox-input input {
            flex: 1;
            border: 1px solid #dce2ea;
            border-radius: 60px;
            padding: 12px 20px;
            font-size: 14px;
            outline: none;
            transition: 0.2s;
            background: white;
            font-family: 'Inter', sans-serif;
        }
        .ai-chatbox-input input:focus {
            border-color: #1a5a8c;
            box-shadow: 0 0 0 3px rgba(26,90,140,0.12);
        }
        .ai-chatbox-input button {
            background: #1a5a8c;
            border: none;
            color: white;
            border-radius: 60px;
            padding: 0 28px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: 0.2s;
            display: flex;
            align-items: center;
            gap: 10px;
            font-family: 'Inter', sans-serif;
        }
        .ai-chatbox-input button:hover {
            background: #0b2a4a;
            transform: scale(0.97);
        }
        /* minimized state */
        .ai-chatbox.minimized .ai-chatbox-body,
        .ai-chatbox.minimized .ai-chatbox-input {
            display: none;
        }
        .ai-chatbox.minimized {
            width: auto;
            border-radius: 60px;
            padding: 6px 16px 6px 20px;
            background: rgba(255,255,255,0.96);
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            bottom: 32px;
            right: 32px;
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255,255,255,0.8);
        }
        .ai-chatbox.minimized .ai-chatbox-header {
            border: none;
            padding: 6px 4px;
        }
        .ai-chatbox.minimized .ai-chatbox-header h4 {
            font-size: 14px;
        }
        @keyframes fadeSlide {
            0% { opacity: 0; transform: translateY(8px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        .footer {
            text-align: center;
            margin-top: 28px;
            font-size: 13px;
            color: #7e8fa8;
            border-top: 1px solid #eef2f6;
            padding-top: 20px;
        }
        @media (max-width: 768px) {
            .container { padding: 16px; }
            .upload-area { flex-direction: column; align-items: stretch; }
            .btn-group { margin-left: 0; justify-content: center; }
            .ai-chatbox { right: 12px; bottom: 16px; width: 94vw; }
            .ai-chatbox.minimized { right: 12px; bottom: 16px; }
        }
        /* scrollbar */
        .ai-chatbox-body::-webkit-scrollbar {
            width: 4px;
        }
        .ai-chatbox-body::-webkit-scrollbar-track {
            background: transparent;
        }
        .ai-chatbox-body::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 20px;
        }
    </style>
</head>
<body>
<div class="container">
    <!-- header -->
    <div class="header">
        <div class="header-left">
            <h1><i class="fas fa-calendar-check"></i> ADSU Mubi · Scheduler</h1>
            <p><i class="fas fa-map-pin" style="margin-right: 6px;"></i>Examination & Lecture Timetable Generator</p>
        </div>
        <div class="header-right">
            <span class="user-welcome"><i class="fas fa-user-circle"></i> <?= htmlspecialchars($username) ?></span>
            <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <p style="margin-bottom: 18px; color: #2d405b; font-weight: 400;">Upload <strong>Courses and Venue Capacity.docx</strong> — AI-assisted scheduling with venue & level constraints.</p>

    <!-- upload area -->
    <div class="upload-area">
        <label class="file-label">
            <i class="fas fa-folder-open"></i> Choose DOCX
            <input type="file" id="docxFile" accept=".docx">
        </label>
        <span class="file-info" id="fileInfo">No file selected</span>
        <div class="btn-group">
            <button id="generateExamBtn" class="btn btn-exam"><i class="fas fa-pen"></i> Exam</button>
            <button id="generateLectureBtn" class="btn btn-lecture"><i class="fas fa-book-open"></i> Lecture</button>
            <button id="exportExcelBtn" class="btn btn-export" disabled><i class="fas fa-file-excel"></i> Export</button>
        </div>
    </div>
    <div id="status" class="status"></div>

    <!-- timetable -->
    <div class="timetable-panel">
        <div class="timetable-header">
            <h3><i class="fas fa-table" style="color:#1a5a8c;"></i> <span id="timetableTitle">Generated Timetable</span></h3>
            <span id="timetableTypeBadge" class="timetable-type-badge exam">Examination</span>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th style="min-width:130px;">Day</th><th>7:00 – 10:00</th><th>10:30 – 13:30</th><th>14:00 – 17:00</th></tr></thead>
                <tbody id="timetableBody">
                    <tr><td colspan="4" class="empty-state"><i class="fas fa-hourglass-half"></i><br>No timetable generated yet. Upload a file and generate.</td></tr>
                </tbody>
            </table>
        </div>
        <div id="statsPanel" class="stats"></div>
    </div>

    <div class="footer">© ADSU Mubi 2026 · Boni Haruna Campus · Smart Scheduling Engine</div>
</div>

<!-- ===== AI CHATBOX (floating) ===== -->
<div class="ai-chatbox" id="aiChatbox">
    <div class="ai-chatbox-header" id="chatToggle">
        <h4><i class="fas fa-robot"></i> AI Assistant</h4>
        <span class="toggle-icon" id="chatToggleIcon"><i class="fas fa-minus"></i></span>
    </div>
    <div class="ai-chatbox-body" id="chatMessages">
        <div class="ai-message bot">👋 Hi! I'm your scheduling assistant. Upload a DOCX and click <strong>Exam</strong> or <strong>Lecture</strong>. Ask me anything!</div>
    </div>
    <div class="ai-chatbox-input">
        <input type="text" id="chatInput" placeholder="Ask about timetable automation...">
        <button id="chatSendBtn"><i class="fas fa-paper-plane"></i> Send</button>
    </div>
</div>

<script src="https://cdn.sheetjs.com/xlsx-0.20.2/package/dist/xlsx.full.min.js"></script>
<script>
    (function() {
        // DOM refs
        const fileInput = document.getElementById('docxFile');
        const generateExamBtn = document.getElementById('generateExamBtn');
        const generateLectureBtn = document.getElementById('generateLectureBtn');
        const exportBtn = document.getElementById('exportExcelBtn');
        const statusDiv = document.getElementById('status');
        const timetableBody = document.getElementById('timetableBody');
        const statsPanel = document.getElementById('statsPanel');
        const timetableTitle = document.getElementById('timetableTitle');
        const timetableTypeBadge = document.getElementById('timetableTypeBadge');
        const fileInfo = document.getElementById('fileInfo');

        // AI chat elements
        const chatMessages = document.getElementById('chatMessages');
        const chatInput = document.getElementById('chatInput');
        const chatSendBtn = document.getElementById('chatSendBtn');
        const chatToggle = document.getElementById('chatToggle');
        const chatbox = document.getElementById('aiChatbox');
        const toggleIcon = document.getElementById('chatToggleIcon');

        let currentTimetableData = null;
        let currentTimetableType = 'exam';
        let chatHistory = [];

        // ---- file handler ----
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                fileInfo.innerHTML = '<strong>' + this.files[0].name + '</strong> (' + (this.files[0].size / 1024).toFixed(2) + ' KB)';
            } else {
                fileInfo.textContent = 'No file selected';
            }
        });

        // ---- generate timetable ----
        async function generateTimetable(type) {
            const file = fileInput.files[0];
            if (!file) {
                showStatus('Please select a .docx file first.', 'warning');
                return;
            }
            if (file.type !== 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
                showStatus('Invalid file type. Please upload a .docx file.', 'error');
                return;
            }
            const typeLabel = type === 'exam' ? 'Examination' : 'Lecture';
            showStatus('Generating ' + typeLabel + ' timetable...', 'info');
            generateExamBtn.disabled = true;
            generateLectureBtn.disabled = true;
            exportBtn.disabled = true;

            const formData = new FormData();
            formData.append('docx_file', file);
            formData.append('timetable_type', type);

            try {
                const response = await fetch('schedule.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                if (!data.success) {
                    showStatus('Error: ' + data.message, 'error');
                    generateExamBtn.disabled = false;
                    generateLectureBtn.disabled = false;
                    return;
                }
                renderTimetable(data.timetable, type);
                renderStats(data.stats, data.validation);
                currentTimetableData = data.timetable;
                currentTimetableType = type;
                exportBtn.disabled = false;
                showStatus(typeLabel + ' timetable generated successfully!', 'success');
                addChatMessage('bot', `✅ ${typeLabel} timetable ready! ${data.stats ? data.stats.total_courses : ''} courses scheduled.`);
            } catch (err) {
                showStatus('Network error: ' + err.message, 'error');
            } finally {
                generateExamBtn.disabled = false;
                generateLectureBtn.disabled = false;
            }
        }

        generateExamBtn.addEventListener('click', () => generateTimetable('exam'));
        generateLectureBtn.addEventListener('click', () => generateTimetable('lecture'));

        // ---- render timetable ----
        function renderTimetable(timetableRows, type) {
            if (!timetableRows || timetableRows.length === 0) {
                timetableBody.innerHTML = '<tr><td colspan="4" class="empty-state"><i class="fas fa-inbox"></i><br>No data available.</td></tr>';
                return;
            }
            const typeLabel = type === 'exam' ? 'Examination' : 'Lecture';
            timetableTitle.textContent = typeLabel + ' Timetable';
            timetableTypeBadge.textContent = typeLabel;
            timetableTypeBadge.className = 'timetable-type-badge ' + type;

            let html = '';
            for (let row of timetableRows) {
                html += `<tr>
                            <td><strong>${escapeHtml(row.day)}</strong></td>
                            <td>${escapeHtml(row.slot1) || '—'}</td>
                            <td>${escapeHtml(row.slot2) || '—'}</td>
                            <td>${escapeHtml(row.slot3) || '—'}</td>
                         </tr>`;
            }
            timetableBody.innerHTML = html;
        }

        function renderStats(stats, validation) {
            if (!stats) return;
            statsPanel.classList.add('visible');
            statsPanel.innerHTML = `
                <div class="stat-card"><strong>📚 Courses</strong><div class="number">${stats.total_courses}</div></div>
                <div class="stat-card"><strong>⏱️ Slots used</strong><div class="number">${stats.slots_used} / ${stats.total_slots}</div></div>
                <div class="stat-card"><strong>⚠️ Level conflicts</strong><div class="number" style="color:${validation.level_conflicts > 0 ? '#b91c1c' : '#0e7c4b'}">${validation.level_conflicts}</div></div>
                <div class="stat-card"><strong>🏢 Venue conflicts</strong><div class="number" style="color:${validation.venue_conflicts > 0 ? '#b91c1c' : '#0e7c4b'}">${validation.venue_conflicts}</div></div>
                <div class="stat-card"><strong>🔗 Multi-venue</strong><div class="number">${stats.multi_venue_courses}</div></div>
                <div class="stat-card"><strong>📈 Utilisation</strong><div class="number">${stats.utilisation_percent}%</div></div>
            `;
        }

        function showStatus(msg, type) {
            statusDiv.style.display = 'block';
            statusDiv.innerHTML = msg;
            statusDiv.className = 'status ' + type;
            if (type !== 'error') {
                setTimeout(() => { statusDiv.style.display = 'none'; }, 6000);
            }
        }

        // ---- export ----
        exportBtn.addEventListener('click', () => {
            if (!currentTimetableData) return;
            const typeLabel = currentTimetableType === 'exam' ? 'Examination' : 'Lecture';
            const wsData = [
                ['ADSU Mubi ' + typeLabel + ' Timetable'],
                [''],
                ['Day', '7:00-10:00', '10:30-13:30', '14:00-17:00']
            ];
            for (let row of currentTimetableData) {
                wsData.push([row.day, row.slot1 || '', row.slot2 || '', row.slot3 || '']);
            }
            const ws = XLSX.utils.aoa_to_sheet(wsData);
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'Timetable');
            XLSX.writeFile(wb, currentTimetableType + '_timetable.xlsx');
            addChatMessage('bot', '📎 Exported timetable to Excel.');
        });

        // ---- AI chat ----
        function addChatMessage(role, text) {
            const div = document.createElement('div');
            div.className = 'ai-message ' + (role === 'user' ? 'user' : 'bot');
            div.textContent = text;
            chatMessages.appendChild(div);
            chatMessages.scrollTop = chatMessages.scrollHeight;
            chatHistory.push({ role, text });
        }

        function handleChat() {
            const msg = chatInput.value.trim();
            if (!msg) return;
            addChatMessage('user', msg);
            chatInput.value = '';

            const lower = msg.toLowerCase();
            let reply = '';
            if (lower.includes('hello') || lower.includes('hi')) {
                reply = 'Hello! Upload a .docx file and click Exam or Lecture to generate.';
            } else if (lower.includes('exam') || lower.includes('lecture')) {
                reply = 'Click the "Exam" or "Lecture" button after uploading your DOCX. The system schedules courses respecting venue capacities and levels.';
            } else if (lower.includes('venue') || lower.includes('capacity')) {
                reply = 'Venue capacities are parsed from the DOCX. The algorithm avoids overcapacity and level conflicts automatically.';
            } else if (lower.includes('conflict') || lower.includes('error')) {
                reply = 'Conflicts are shown in the stats panel. Level conflicts and venue conflicts are highlighted in red.';
            } else if (lower.includes('export') || lower.includes('excel')) {
                reply = 'After generating, click Export to download an Excel file of the timetable.';
            } else if (lower.includes('thank')) {
                reply = 'You\'re welcome! 😊 Let me know if you need more help.';
            } else {
                reply = 'I can assist with timetable generation, venue capacity, level constraints, and export. Try asking about "exam", "lecture", "venue", or "export".';
            }
            setTimeout(() => addChatMessage('bot', reply), 300);
        }

        chatSendBtn.addEventListener('click', handleChat);
        chatInput.addEventListener('keydown', (e) => { if (e.key === 'Enter') handleChat(); });

        // toggle chatbox
        let minimized = false;
        chatToggle.addEventListener('click', () => {
            minimized = !minimized;
            chatbox.classList.toggle('minimized', minimized);
            toggleIcon.innerHTML = minimized ? '<i class="fas fa-plus"></i>' : '<i class="fas fa-minus"></i>';
        });

        // utility
        function escapeHtml(str) {
            if (!str) return '';
            return str.replace(/[&<>]/g, function(m) {
                if (m === '&') return '&amp;';
                if (m === '<') return '&lt;';
                if (m === '>') return '&gt;';
                return m;
            });
        }

        // initial bot nudge
        setTimeout(() => {
            addChatMessage('bot', '💡 Tip: Upload your DOCX and click Exam or Lecture. I\'ll guide you through the process.');
        }, 700);
    })();
</script>
</body>
</html>
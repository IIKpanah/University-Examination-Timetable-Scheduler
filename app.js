    async function generateTimetable(endpoint, typeName) {
        const file = fileInput.files[0];
        if (!file) {
            showStatus('Please select a .docx file first.', 'error');
            return;
        }
        if (file.type !== 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
            showStatus('Invalid file type. Please upload a .docx file.', 'error');
            return;
        }

        showStatus('Processing... Please wait.', 'info');
        generateExamBtn.disabled = true;
        generateLectureBtn.disabled = true;
        exportBtn.disabled = true;

        const formData = new FormData();
        formData.append('docx_file', file);

        try {
            console.log('Sending request to:', endpoint);
            console.log('File:', file.name, file.size, file.type);
            
            const response = await fetch(endpoint, {
                method: 'POST',
                body: formData
            });
            
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            
            const data = await response.json();
            console.log('Response data:', data);
            
            if (!data.success) {
                showStatus('Error: ' + data.message, 'error');
                generateExamBtn.disabled = false;
                generateLectureBtn.disabled = false;
                return;
            }
            
            renderTimetable(data.timetable, typeName);
            renderStats(data.stats, data.validation);
            currentTimetableData = data.timetable;
            currentTimetableType = typeName;
            exportBtn.disabled = false;
            
            // Show current timetable type
            timetableType.style.display = 'block';
            currentType.textContent = typeName.charAt(0).toUpperCase() + typeName.slice(1);
            
            showStatus(`${typeName.charAt(0).toUpperCase() + typeName.slice(1)} timetable generated successfully!`, 'success');
            
        } catch (err) {
            console.error('Error details:', err);
            showStatus('Network error: ' + err.message, 'error');
        } finally {
            generateExamBtn.disabled = false;
            generateLectureBtn.disabled = false;
        }
    }
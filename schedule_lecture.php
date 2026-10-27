<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'auth.php';
ensureUsersTableExists();
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['docx_file'])) {
        throw new Exception('No file uploaded');
    }

    $file = $_FILES['docx_file'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Upload error: ' . $file['error']);
    }

    // Read DOCX file content using ZipArchive
    if (!class_exists('ZipArchive')) {
        throw new Exception('ZipArchive extension is not enabled. Please enable it in php.ini');
    }

    $zip = new ZipArchive();
    if ($zip->open($file['tmp_name']) !== true) {
        throw new Exception('Could not open DOCX file');
    }

    $xml = $zip->getFromName('word/document.xml');
    $zip->close();

    if (!$xml) {
        throw new Exception('Invalid DOCX format - no document.xml found');
    }

    // Parse courses from DOCX
    $courses = parseLectureCourses($xml);
    
    if (empty($courses)) {
        throw new Exception('No courses found in the document. Please ensure the DOCX contains course data in a table format.');
    }

    // Generate lecture timetable
    $timetable = generateLectureTimetable($courses);
    $stats = calculateLectureStats($timetable);
    $validation = validateLectureTimetable($timetable);

    echo json_encode([
        'success' => true,
        'timetable' => $timetable,
        'stats' => $stats,
        'validation' => $validation
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function parseLectureCourses($xml) {
    $courses = [];
    $dom = new DOMDocument();
    @$dom->loadXML($xml);
    
    // Find all tables in the document
    $tables = $dom->getElementsByTagName('w:tbl');
    
    if ($tables->length === 0) {
        return $courses;
    }
    
    foreach ($tables as $table) {
        $rows = $table->getElementsByTagName('w:tr');
        $headers = [];
        $rowIndex = 0;
        
        foreach ($rows as $row) {
            $cells = $row->getElementsByTagName('w:tc');
            $rowData = [];
            
            foreach ($cells as $cell) {
                $text = '';
                $paragraphs = $cell->getElementsByTagName('w:p');
                foreach ($paragraphs as $p) {
                    $runs = $p->getElementsByTagName('w:r');
                    foreach ($runs as $r) {
                        $t = $r->getElementsByTagName('w:t');
                        if ($t->length > 0) {
                            $text .= $t->item(0)->textContent;
                        }
                    }
                }
                $rowData[] = trim($text);
            }
            
            // Check if this is a header row (contains 'Course Code' or similar)
            if ($rowIndex === 0 && !empty($rowData)) {
                $headers = $rowData;
            } elseif ($rowIndex > 0 && !empty($rowData)) {
                // Try to identify columns based on header or position
                $course = [
                    'code' => isset($rowData[0]) ? $rowData[0] : '',
                    'title' => isset($rowData[1]) ? $rowData[1] : '',
                    'level' => extractLevel(isset($rowData[2]) ? $rowData[2] : ''),
                    'lecturer' => isset($rowData[3]) ? $rowData[3] : '',
                    'venue' => isset($rowData[4]) ? $rowData[4] : '',
                    'duration' => isset($rowData[5]) ? $rowData[5] : '2',
                    'students' => extractStudentCount(isset($rowData[6]) ? $rowData[6] : '')
                ];
                
                // If course code and title exist, add to list
                if ($course['code'] && $course['title']) {
                    $courses[] = $course;
                }
            }
            $rowIndex++;
        }
    }
    
    return $courses;
}

function extractLevel($text) {
    if (preg_match('/(\d{3})/i', $text, $matches)) {
        return (int)$matches[1];
    }
    // Try to extract level from common patterns like "100 Level", "Level 100"
    if (preg_match('/(?:level\s*)?(\d+)/i', $text, $matches)) {
        return (int)$matches[1] * 100;
    }
    return 100;
}

function extractStudentCount($text) {
    if (preg_match('/(\d+)/', $text, $matches)) {
        return (int)$matches[1];
    }
    return 30; // Default
}

function generateLectureTimetable($courses) {
    // Define weekly schedule - Monday to Saturday, 4 slots per day
    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    $slots = [
        '7:00 AM - 9:00 AM',
        '9:00 AM - 11:00 AM', 
        '11:00 AM - 1:00 PM',
        '2:00 PM - 5:00 PM'
    ];
    
    // Group courses by level
    $levelGroups = [];
    foreach ($courses as $course) {
        $level = $course['level'];
        if (!isset($levelGroups[$level])) {
            $levelGroups[$level] = [];
        }
        $levelGroups[$level][] = $course;
    }
    
    // Sort levels (lower levels first)
    ksort($levelGroups);
    
    $timetable = [];
    $dayIndex = 0;
    $slotIndex = 0;
    $maxSlots = count($days) * count($slots);
    $totalCourses = count($courses);
    
    // Create schedule by level
    foreach ($levelGroups as $level => $levelCourses) {
        foreach ($levelCourses as $course) {
            // Find next available slot
            $day = $days[$dayIndex % count($days)];
            $slot = $slots[$slotIndex % count($slots)];
            $slotNum = ($slotIndex % count($slots)) + 1;
            
            // Check for conflicts (same lecturer or venue at same time)
            $hasConflict = false;
            foreach ($timetable as $existing) {
                if ($existing['day'] === $day && $existing['slot_num'] === $slotNum) {
                    if ($existing['lecturer'] === $course['lecturer'] && $course['lecturer']) {
                        $hasConflict = true;
                        break;
                    }
                    if ($existing['venue'] === $course['venue'] && $course['venue']) {
                        $hasConflict = true;
                        break;
                    }
                }
            }
            
            if ($hasConflict) {
                // Try next slot
                $slotIndex++;
                $day = $days[$dayIndex % count($days)];
                $slot = $slots[$slotIndex % count($slots)];
                $slotNum = ($slotIndex % count($slots)) + 1;
            }
            
            $timetable[] = [
                'day' => $day,
                'slot' => $slot,
                'slot_num' => $slotNum,
                'course_code' => $course['code'],
                'course_title' => $course['title'],
                'level' => $course['level'],
                'lecturer' => $course['lecturer'],
                'venue' => $course['venue'],
                'duration' => $course['duration'],
                'students' => $course['students']
            ];
            
            $slotIndex++;
            if ($slotIndex >= $maxSlots) {
                // If we run out of slots, start over with new week
                $slotIndex = 0;
                $dayIndex = 0;
            }
        }
    }
    
    // Convert to table format
    $table = [];
    foreach ($days as $day) {
        $row = [
            'day' => $day,
            'slot1' => '',
            'slot2' => '',
            'slot3' => '',
            'slot4' => ''
        ];
        
        $daySlots = array_filter($timetable, function($item) use ($day) {
            return $item['day'] === $day;
        });
        
        foreach ($daySlots as $item) {
            $slotNum = $item['slot_num'];
            // Format with line breaks for better display
            $text = $item['course_code'] . ' - ' . $item['course_title'] . "\n" .
                    '(' . $item['level'] . ' Level)' . "\n" .
                    'Venue: ' . $item['venue'] . "\n" .
                    'Lecturer: ' . $item['lecturer'] . "\n" .
                    'Students: ' . $item['students'];
            
            if ($slotNum == 1) $row['slot1'] = $text;
            elseif ($slotNum == 2) $row['slot2'] = $text;
            elseif ($slotNum == 3) $row['slot3'] = $text;
            elseif ($slotNum == 4) $row['slot4'] = $text;
        }
        
        $table[] = $row;
    }
    
    return $table;
}

function calculateLectureStats($timetable) {
    $totalCourses = 0;
    $totalSlots = 6 * 4; // 6 days * 4 slots
    
    foreach ($timetable as $row) {
        if ($row['slot1']) $totalCourses++;
        if ($row['slot2']) $totalCourses++;
        if ($row['slot3']) $totalCourses++;
        if ($row['slot4']) $totalCourses++;
    }
    
    return [
        'total_courses' => $totalCourses,
        'slots_used' => $totalCourses,
        'total_slots' => $totalSlots,
        'utilisation_percent' => $totalSlots > 0 ? round(($totalCourses / $totalSlots) * 100, 1) : 0,
        'days' => 6,
        'slots_per_day' => 4
    ];
}

function validateLectureTimetable($timetable) {
    $lecturerConflicts = 0;
    $venueConflicts = 0;
    $levelConflicts = 0;
    
    $lecturerSchedule = [];
    $venueSchedule = [];
    $levelSchedule = [];
    
    foreach ($timetable as $row) {
        $day = $row['day'];
        
        // Check conflicts for each slot
        for ($i = 1; $i <= 4; $i++) {
            $slotKey = 'slot' . $i;
            if (empty($row[$slotKey])) continue;
            
            $content = $row[$slotKey];
            $parts = explode("\n", $content);
            
            $lecturer = '';
            $venue = '';
            $level = '';
            
            foreach ($parts as $part) {
                if (strpos($part, 'Venue: ') !== false) {
                    $venue = trim(str_replace('Venue: ', '', $part));
                }
                if (strpos($part, 'Lecturer: ') !== false) {
                    $lecturer = trim(str_replace('Lecturer: ', '', $part));
                }
                if (strpos($part, 'Level)') !== false) {
                    preg_match('/\((\d+)\s*Level\)/', $part, $matches);
                    if ($matches) $level = $matches[1];
                }
            }
            
            $key = $day . '-' . $i;
            
            // Check lecturer conflict
            if ($lecturer && isset($lecturerSchedule[$key])) {
                if ($lecturerSchedule[$key] === $lecturer) {
                    $lecturerConflicts++;
                }
            } else if ($lecturer) {
                $lecturerSchedule[$key] = $lecturer;
            }
            
            // Check venue conflict
            if ($venue && isset($venueSchedule[$key])) {
                if ($venueSchedule[$key] === $venue) {
                    $venueConflicts++;
                }
            } else if ($venue) {
                $venueSchedule[$key] = $venue;
            }
            
            // Check level conflict (multiple same level at same time)
            if ($level && isset($levelSchedule[$key])) {
                if ($levelSchedule[$key] === $level) {
                    $levelConflicts++;
                }
            } else if ($level) {
                $levelSchedule[$key] = $level;
            }
        }
    }
    
    return [
        'lecturer_conflicts' => $lecturerConflicts,
        'venue_conflicts' => $venueConflicts,
        'level_conflicts' => $levelConflicts,
        'total_conflicts' => $lecturerConflicts + $venueConflicts + $levelConflicts
    ];
}
?>
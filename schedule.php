<?php
// schedule.php - ADSU BONI CAMPUS TIMETABLE SCHEDULER
// WITH UNCAPTURED COURSES REPORT

// ============================================
// ERROR HANDLING
// ============================================
error_reporting(0);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

while (ob_get_level()) ob_end_clean();
ob_start();
header('Content-Type: application/json');

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("PHP Error: $errstr in $errfile on line $errline");
    return true;
});

// ============================================
// MAIN EXECUTION
// ============================================
try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['docx_file'])) {
        throw new Exception('Invalid request. Please upload a DOCX file.');
    }

    $uploadedFile = $_FILES['docx_file']['tmp_name'];
    if (!is_uploaded_file($uploadedFile)) {
        throw new Exception('File upload failed.');
    }

    list($courseDict, $venues) = extractCoursesAndVenuesFromDocx($uploadedFile);

    if (empty($courseDict)) {
        throw new Exception('No courses found. Please check the DOCX file format.');
    }
    if (empty($venues)) {
        throw new Exception('No venues found. Please check the DOCX file format.');
    }

    $venues = increaseVenueCapacity($venues);

    list($slotAssignments, $slots, $scheduledCourses, $unscheduledCourses) = scheduleCourses12Day($courseDict, $venues);
    $timetableRows = buildTimetableRows12Day($slotAssignments, $slots, $courseDict);
    $stats = computeStats12Day($slotAssignments, $slots, $courseDict, $venues, $scheduledCourses, $unscheduledCourses);
    $validation = validateTimetable12Day($slotAssignments, $slots);

    // Build response with uncaptured courses details
    $response = [
        'success' => true,
        'timetable' => $timetableRows,
        'stats' => [
            'total_courses' => count($courseDict),
            'scheduled_courses' => count($scheduledCourses),
            'unscheduled_courses' => count($unscheduledCourses),
            'slots_used' => $stats['slots_used'],
            'total_slots' => $stats['total_slots'],
            'multi_venue_courses' => $stats['multi_venue_courses'],
            'utilisation_percent' => $stats['utilisation_percent']
        ],
        'validation' => [
            'level_conflicts' => $validation['level_conflicts'],
            'venue_conflicts' => $validation['venue_conflicts']
        ],
        'scheduled_courses' => $scheduledCourses,
        'unscheduled_courses' => $unscheduledCourses,
        'total_courses' => count($courseDict),
        'scheduled_count' => count($scheduledCourses),
        'unscheduled_count' => count($unscheduledCourses),
        // Add detailed uncaptured courses report
        'uncaptured_report' => generateUncapturedReport($unscheduledCourses, $courseDict)
    ];

    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// ============================================
// GENERATE UNCAPTURED COURSES REPORT
// ============================================
function generateUncapturedReport($unscheduledCourses, $courseDict) {
    $report = [];
    
    // Group by department
    $departmentGroups = [];
    foreach ($unscheduledCourses as $code) {
        $dept = getDepartment($code);
        $level = getLevel($code);
        $enrol = isset($courseDict[$code]) ? $courseDict[$code] : 'N/A';
        
        if (!isset($departmentGroups[$dept])) {
            $departmentGroups[$dept] = [];
        }
        $departmentGroups[$dept][] = [
            'code' => $code,
            'level' => $level,
            'enrolment' => $enrol
        ];
    }
    
    // Sort departments
    ksort($departmentGroups);
    
    $totalUncaptured = 0;
    foreach ($departmentGroups as $dept => $courses) {
        $count = count($courses);
        $totalUncaptured += $count;
        $report[] = [
            'department' => $dept,
            'count' => $count,
            'courses' => $courses
        ];
    }
    
    return [
        'total' => $totalUncaptured,
        'departments' => $report
    ];
}

// ============================================
// INCREASE VENUE CAPACITY - Combine Halls
// ============================================
function increaseVenueCapacity($venues) {
    $combinedVenues = [];
    
    if (isset($venues['LT1']) && isset($venues['LT2'])) {
        $combinedVenues['LT1&LT2'] = $venues['LT1'] + $venues['LT2'];
    }
    if (isset($venues['LT3']) && isset($venues['LT4'])) {
        $combinedVenues['LT3&LT4'] = $venues['LT3'] + $venues['LT4'];
    }
    if (isset($venues['LT1']) && isset($venues['LT2']) && isset($venues['LT3']) && isset($venues['LT4'])) {
        $combinedVenues['LT1&LT2&LT3&LT4'] = $venues['LT1'] + $venues['LT2'] + $venues['LT3'] + $venues['LT4'];
    }
    
    if (isset($venues['TETF1']) && isset($venues['TETF2'])) {
        $combinedVenues['TETF1&TETF2'] = $venues['TETF1'] + $venues['TETF2'];
    }
    if (isset($venues['TETF3']) && isset($venues['TETF4'])) {
        $combinedVenues['TETF3&TETF4'] = $venues['TETF3'] + $venues['TETF4'];
    }
    if (isset($venues['TETF3']) && isset($venues['TETF4']) && isset($venues['TETF5'])) {
        $combinedVenues['TETF3&TETF4&TETF5'] = $venues['TETF3'] + $venues['TETF4'] + $venues['TETF5'];
    }
    
    if (isset($venues['A1']) && isset($venues['A2'])) {
        $combinedVenues['A1&A2'] = $venues['A1'] + $venues['A2'];
    }
    if (isset($venues['A3']) && isset($venues['A4'])) {
        $combinedVenues['A3&A4'] = $venues['A3'] + $venues['A4'];
    }
    if (isset($venues['A1']) && isset($venues['A2']) && isset($venues['A3']) && isset($venues['A4'])) {
        $combinedVenues['A1&A2&A3&A4'] = $venues['A1'] + $venues['A2'] + $venues['A3'] + $venues['A4'];
    }
    
    if (isset($venues['B1']) && isset($venues['B2'])) {
        $combinedVenues['B1&B2'] = $venues['B1'] + $venues['B2'];
    }
    
    $allVenues = array_merge($venues, $combinedVenues);
    arsort($allVenues);
    
    return $allVenues;
}

// ============================================
// FUNCTIONS
// ============================================

function getRawText($filePath) {
    if (!class_exists('ZipArchive')) {
        throw new Exception('ZipArchive extension is not enabled.');
    }
    
    $zip = new ZipArchive();
    if ($zip->open($filePath) !== true) {
        throw new Exception('Could not open DOCX file.');
    }
    
    $xmlContent = $zip->getFromName('word/document.xml');
    $zip->close();
    
    if (!$xmlContent) {
        throw new Exception('Could not read document content.');
    }
    
    $xmlContent = preg_replace('/xmlns[^=]*="[^"]*"/', '', $xmlContent);
    $xmlContent = preg_replace('/<\/?(?:w:p|w:br|w:cr|w:r|w:t)>/i', ' ', $xmlContent);
    
    $text = strip_tags($xmlContent);
    $text = preg_replace('/\s+/', ' ', $text);
    
    return trim($text);
}

function extractCoursesAndVenuesFromDocx($filePath) {
    $text = getRawText($filePath);
    if (empty($text)) {
        throw new Exception('Document is empty or could not be read.');
    }

    $courseDict = [];
    
    // Pattern 1: Exact course with enrolment
    $pattern1 = '/([A-Z]{2,5}(?:-[A-Z]+)?(?:[A-Z]{2,5})?)(\d{3})\s+(\d{1,4})/i';
    preg_match_all($pattern1, $text, $matches1, PREG_SET_ORDER);
    
    foreach ($matches1 as $m) {
        $prefix = trim($m[1]);
        $courseNum = $m[2];
        $enrol = intval($m[3]);
        $code = strtoupper($prefix . $courseNum);
        if ($enrol > 0 && $enrol <= 1000 && preg_match('/[A-Z]{2,}\d{3}/', $code)) {
            if (!isset($courseDict[$code])) {
                $courseDict[$code] = $enrol;
            }
        }
    }
    
    // Pattern 2: Course with prefix and enrolment
    $pattern2 = '/([A-Z]{2,5}(?:-[A-Z]+)?(?:[A-Z]{2,5})?)(\d{3})\s*\((\d{1,4})\)/i';
    preg_match_all($pattern2, $text, $matches2, PREG_SET_ORDER);
    
    foreach ($matches2 as $m) {
        $prefix = trim($m[1]);
        $courseNum = $m[2];
        $enrol = intval($m[3]);
        $code = strtoupper($prefix . $courseNum);
        if ($enrol > 0 && $enrol <= 1000 && preg_match('/[A-Z]{2,}\d{3}/', $code)) {
            if (!isset($courseDict[$code])) {
                $courseDict[$code] = $enrol;
            }
        }
    }
    
    // Pattern 3: Simple course code with enrolment
    $pattern3 = '/([A-Z]{2,5})\s+(\d{3})\s+(\d{1,4})/i';
    preg_match_all($pattern3, $text, $matches3, PREG_SET_ORDER);
    
    foreach ($matches3 as $m) {
        $prefix = trim($m[1]);
        $courseNum = $m[2];
        $enrol = intval($m[3]);
        $code = strtoupper($prefix . $courseNum);
        if ($enrol > 0 && $enrol <= 1000 && preg_match('/[A-Z]{2,}\d{3}/', $code)) {
            if (!isset($courseDict[$code])) {
                $courseDict[$code] = $enrol;
            }
        }
    }
    
    // Pattern 4: ADSU prefix
    $pattern4 = '/ADSU-([A-Z]{2,5})(\d{3})\s+(\d{1,4})/i';
    preg_match_all($pattern4, $text, $matches4, PREG_SET_ORDER);
    
    foreach ($matches4 as $m) {
        $prefix = 'ADSU-' . trim($m[1]);
        $courseNum = $m[2];
        $enrol = intval($m[3]);
        $code = strtoupper($prefix . $courseNum);
        if ($enrol > 0 && $enrol <= 1000 && preg_match('/[A-Z]{2,}\d{3}/', $code)) {
            if (!isset($courseDict[$code])) {
                $courseDict[$code] = $enrol;
            }
        }
    }
    
    // Pattern 5: ICH prefix
    $pattern5 = '/ICH([A-Z]{2,5})(\d{3})\s+(\d{1,4})/i';
    preg_match_all($pattern5, $text, $matches5, PREG_SET_ORDER);
    
    foreach ($matches5 as $m) {
        $prefix = 'ICH' . trim($m[1]);
        $courseNum = $m[2];
        $enrol = intval($m[3]);
        $code = strtoupper($prefix . $courseNum);
        if ($enrol > 0 && $enrol <= 1000 && preg_match('/[A-Z]{2,}\d{3}/', $code)) {
            if (!isset($courseDict[$code])) {
                $courseDict[$code] = $enrol;
            }
        }
    }
    
    $courseDict = array_filter($courseDict, function($enrol) {
        return $enrol >= 5 && $enrol <= 1000;
    });
    
    ksort($courseDict);
    
    // Extract venues
    $venues = [];
    $venuePattern = '/(A[1-4]|B[1-4]|TETF[1-5]|LT[1-4]|MPH|NLT|CLT|SLT)\s*\(?(\d{2,4})\)?/i';
    preg_match_all($venuePattern, $text, $venueMatches, PREG_SET_ORDER);
    
    foreach ($venueMatches as $vm) {
        $venue = strtoupper($vm[1]);
        $cap = intval($vm[2]);
        if ($cap >= 20 && $cap <= 500) {
            $venues[$venue] = $cap;
        }
    }
    
    if (empty($venues)) {
        $venues = [
            'LT1' => 160, 'LT2' => 160, 'LT3' => 250, 'LT4' => 250,
            'TETF1' => 70, 'TETF2' => 70, 'TETF3' => 160, 'TETF4' => 160, 'TETF5' => 150,
            'A1' => 40, 'A2' => 40, 'A3' => 40, 'A4' => 40,
            'B1' => 40, 'B2' => 40
        ];
    }
    
    arsort($venues);
    
    return [$courseDict, $venues];
}

function getLevel($code) {
    $code = str_replace(['TERED', 'ADSU-', 'ICH'], '', $code);
    
    if (preg_match('/(\d{3})/', $code, $match)) {
        $first = intval($match[1][0]);
        if ($first == 1) return 100;
        if ($first == 2) return 200;
        if ($first == 3) return 300;
        if ($first == 4) return 400;
    }
    return null;
}

function getDepartment($code) {
    $code = str_replace(['TERED', 'ADSU-', 'ICH'], '', $code);
    
    if (preg_match('/^([A-Z]+)/', $code, $match)) {
        $dept = $match[1];
        $deptMap = [
            'BCH' => 'Biochemistry', 'BIO' => 'Botany', 'BOT' => 'Botany',
            'CHM' => 'Chemistry', 'COS' => 'Computer Science', 'CSC' => 'Computer Science',
            'ENG' => 'English', 'FAC' => 'English', 'LIT' => 'English',
            'GEY' => 'Geology', 'HIS' => 'History', 'MCB' => 'Microbiology',
            'MTH' => 'Mathematics', 'STA' => 'Mathematics', 'PHY' => 'Physics',
            'ZOO' => 'Zoology', 'DTS' => 'Computer Science', 'IFT' => 'Computer Science',
            'GET' => 'Chemistry'
        ];
        return isset($deptMap[$dept]) ? $deptMap[$dept] : $dept;
    }
    return 'Unknown';
}

// ============================================
// 12-DAY SCHEDULING ALGORITHM
// ============================================
function scheduleCourses12Day($courseDict, $venues) {
    $courses = [];
    foreach ($courseDict as $code => $enrol) {
        $level = getLevel($code);
        if ($level === null) continue;
        $courses[] = [
            'code' => $code,
            'enrol' => $enrol,
            'level' => $level,
            'department' => getDepartment($code)
        ];
    }
    
    usort($courses, function($a, $b) { 
        return $b['enrol'] - $a['enrol']; 
    });
    
    $slots = [];
    $dayNames = ['Day 1', 'Day 2', 'Day 3', 'Day 4', 'Day 5', 'Day 6', 
                  'Day 7', 'Day 8', 'Day 9', 'Day 10', 'Day 11', 'Day 12'];
    $periods = ['7:00-10:00', '10:30-13:30', '14:00-17:00'];
    
    foreach ($dayNames as $day) {
        foreach ($periods as $p) {
            $slots[] = ['day' => $day, 'period' => $p];
        }
    }
    
    $n = count($slots);
    $slotAssignments = array_fill(0, $n, []);
    $slotVenuesUsed = array_fill(0, $n, []);
    
    $allVenues = array_keys($venues);
    $venueCapacities = $venues;
    
    $unscheduled = [];
    $scheduled = [];
    $courseIndex = 0;
    $maxAttempts = $n * 5;
    
    foreach ($courses as $course) {
        $assigned = false;
        
        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $idx = ($courseIndex + $attempt) % $n;
            
            $availableVenues = [];
            foreach ($allVenues as $venue) {
                if (!in_array($venue, $slotVenuesUsed[$idx])) {
                    $availableVenues[] = $venue;
                }
            }
            
            if (empty($availableVenues)) continue;
            
            $selectedVenues = selectOptimalVenues($course['enrol'], $availableVenues, $venueCapacities);
            
            if ($selectedVenues !== null) {
                $slotAssignments[$idx][] = [
                    'code' => $course['code'],
                    'level' => $course['level'],
                    'enrol' => $course['enrol'],
                    'department' => $course['department'],
                    'venues' => $selectedVenues
                ];
                
                foreach ($selectedVenues as $ven) {
                    $slotVenuesUsed[$idx][] = $ven;
                }
                
                $assigned = true;
                $scheduled[] = $course['code'];
                break;
            }
        }
        
        if (!$assigned) {
            $unscheduled[] = $course['code'];
        }
        $courseIndex++;
    }
    
    // Second Pass
    $remainingCourses = array_filter($courses, function($c) use ($scheduled) {
        return !in_array($c['code'], $scheduled);
    });
    
    foreach ($remainingCourses as $course) {
        $assigned = false;
        
        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $idx = ($courseIndex + $attempt) % $n;
            
            $availableVenues = [];
            foreach ($allVenues as $venue) {
                if (!in_array($venue, $slotVenuesUsed[$idx])) {
                    $availableVenues[] = $venue;
                }
            }
            
            if (empty($availableVenues)) continue;
            
            $selectedVenues = selectFlexibleVenues($course['enrol'], $availableVenues, $venueCapacities);
            
            if ($selectedVenues !== null) {
                $slotAssignments[$idx][] = [
                    'code' => $course['code'],
                    'level' => $course['level'],
                    'enrol' => $course['enrol'],
                    'department' => $course['department'],
                    'venues' => $selectedVenues
                ];
                
                foreach ($selectedVenues as $ven) {
                    $slotVenuesUsed[$idx][] = $ven;
                }
                
                $assigned = true;
                $scheduled[] = $course['code'];
                break;
            }
        }
        
        if (!$assigned) {
            $unscheduled[] = $course['code'];
        }
        $courseIndex++;
    }
    
    return [$slotAssignments, $slots, $scheduled, $unscheduled];
}

function selectOptimalVenues($needed, $availableVenues, $venueCapacities) {
    usort($availableVenues, function($a, $b) use ($venueCapacities) {
        return $venueCapacities[$b] - $venueCapacities[$a];
    });
    
    $selected = [];
    $remaining = $needed;
    $totalCapacity = 0;
    
    foreach ($availableVenues as $venue) {
        $totalCapacity += $venueCapacities[$venue];
    }
    
    if ($totalCapacity < $needed) {
        return $availableVenues;
    }
    
    foreach ($availableVenues as $venue) {
        if ($remaining <= 0) break;
        $selected[] = $venue;
        $remaining -= $venueCapacities[$venue];
    }
    
    if ($remaining > 0) {
        foreach ($availableVenues as $venue) {
            if (!in_array($venue, $selected)) {
                $selected[] = $venue;
                $remaining -= $venueCapacities[$venue];
                if ($remaining <= 0) break;
            }
        }
    }
    
    if ($remaining > 0) return null;
    return $selected;
}

function selectFlexibleVenues($needed, $availableVenues, $venueCapacities) {
    usort($availableVenues, function($a, $b) use ($venueCapacities) {
        return $venueCapacities[$b] - $venueCapacities[$a];
    });
    
    $selected = [];
    $remaining = $needed;
    $totalCapacity = 0;
    
    foreach ($availableVenues as $venue) {
        $totalCapacity += $venueCapacities[$venue];
    }
    
    if ($totalCapacity < $needed) {
        return $availableVenues;
    }
    
    foreach ($availableVenues as $venue) {
        if ($remaining <= 0) break;
        $selected[] = $venue;
        $remaining -= $venueCapacities[$venue];
    }
    
    if ($remaining > 0) {
        foreach ($availableVenues as $venue) {
            if (!in_array($venue, $selected)) {
                $selected[] = $venue;
                $remaining -= $venueCapacities[$venue];
                if ($remaining <= 0) break;
            }
        }
    }
    
    if ($remaining > 0) return null;
    return $selected;
}

function buildTimetableRows12Day($slotAssignments, $slots, $courseDict) {
    $rows = [];
    
    foreach ($slots as $idx => $slot) {
        $day = $slot['day'];
        $period = $slot['period'];
        
        $rowIndex = -1;
        foreach ($rows as $i => $r) {
            if ($r['day'] === $day) {
                $rowIndex = $i;
                break;
            }
        }
        
        if ($rowIndex === -1) {
            $rows[] = [
                'day' => $day,
                'slot1' => '',
                'slot2' => '',
                'slot3' => ''
            ];
            $rowIndex = count($rows) - 1;
        }
        
        $courseEntries = [];
        foreach ($slotAssignments[$idx] as $ass) {
            $courseCode = $ass['code'];
            $enrolment = $ass['enrol'];
            $venueStr = count($ass['venues']) > 1 ? implode('&', $ass['venues']) : $ass['venues'][0];
            
            $courseEntries[] = $courseCode . " (" . $enrolment . ") -- " . $venueStr;
        }
        
        $cellText = implode('; ', $courseEntries);
        
        if ($period == '7:00-10:00') {
            $rows[$rowIndex]['slot1'] = $cellText;
        } elseif ($period == '10:30-13:30') {
            $rows[$rowIndex]['slot2'] = $cellText;
        } elseif ($period == '14:00-17:00') {
            $rows[$rowIndex]['slot3'] = $cellText;
        }
    }
    
    return $rows;
}

function computeStats12Day($slotAssignments, $slots, $courseDict, $venues, $scheduled, $unscheduled) {
    $slotsUsed = 0;
    $totalCoursesInSlots = 0;
    
    foreach ($slotAssignments as $ass) {
        if (count($ass) > 0) {
            $slotsUsed++;
            $totalCoursesInSlots += count($ass);
        }
    }
    
    $multiVenue = 0;
    foreach ($slotAssignments as $ass) {
        foreach ($ass as $a) {
            if (count($a['venues']) > 1) $multiVenue++;
        }
    }
    
    $totalSeatHours = count($slots) * array_sum($venues);
    $utilised = array_sum($courseDict);
    $utilPercent = $totalSeatHours > 0 ? round(($utilised / $totalSeatHours) * 100, 2) : 0;
    
    return [
        'slots_used' => $slotsUsed,
        'total_slots' => count($slots),
        'multi_venue_courses' => $multiVenue,
        'utilisation_percent' => $utilPercent,
        'avg_courses_per_slot' => $slotsUsed > 0 ? round($totalCoursesInSlots / $slotsUsed, 2) : 0
    ];
}

function validateTimetable12Day($slotAssignments, $slots) {
    $levelConflicts = 0;
    $venueConflicts = 0;
    
    foreach ($slotAssignments as $assignments) {
        $levelsInSlot = [];
        $venuesInSlot = [];
        
        foreach ($assignments as $ass) {
            $lev = $ass['level'];
            if (in_array($lev, $levelsInSlot)) {
                $levelConflicts++;
            } else {
                $levelsInSlot[] = $lev;
            }
            
            foreach ($ass['venues'] as $ven) {
                if (in_array($ven, $venuesInSlot)) {
                    $venueConflicts++;
                } else {
                    $venuesInSlot[] = $ven;
                }
            }
        }
    }
    
    return [
        'level_conflicts' => $levelConflicts,
        'venue_conflicts' => $venueConflicts
    ];
}
?>
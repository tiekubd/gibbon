<?php
/*
Attendance Dashboard for Gibbon LMS
Developed by Tieku Bortei-Doku
*/

use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Services\Format;

// Ensure user has access to the module
if (!isActionAccessible($guid, $connection2, '/modules/Visual Builder/attendance_dashboard.php')) {
    if (!isset($guid) || !isset($connection2) || !userHasModuleAccess($guid, $connection2, '/modules/Visual Builder/attendance_dashboard.php')) {
        echo __('You do not have access to this action.');
        return;
    }
}

// Create the form for date selection and filters
$form = Form::create('attendanceDashboard', $session->get('absoluteURL') . '/index.php', 'get');
$form->setTitle(__('Attendance Dashboard'));
$form->addHiddenValue('q', '/modules/Visual Builder/attendance_dashboard.php');

$row = $form->addRow();
$row->addLabel('startDate', __('Start Date'));
$row->addDate('startDate')->setValue(isset($_GET['startDate']) ? $_GET['startDate'] : date('Y-m-d', strtotime('-1 month')))->required();

$row = $form->addRow();
$row->addLabel('endDate', __('End Date'));
$row->addDate('endDate')->setValue(isset($_GET['endDate']) ? $_GET['endDate'] : date('Y-m-d'))->required();

$row = $form->addRow();
$row->addLabel('groupBy', __('Group By'));
$row->addSelect('groupBy')
    ->fromArray([
        'All Students' => __('All Students'),
        'Class' => __('Class'),
        'Home Room' => __('Home Room')
    ])
    ->required();

$row = $form->addRow();
$row->addLabel('sortBy', __('Sort By'));
$row->addSelect('sortBy')
    ->fromArray([
        'Surname' => __('Surname'),
        'First Name' => __('First Name'),
        'Home Room' => __('Home Room')
    ])
    ->required();

$row = $form->addRow();
$row->addSubmit(__('Submit'))->addClass('pull-right');

echo $form->getOutput();

// Handle form submission
if (isset($_GET['startDate']) && isset($_GET['endDate'])) {
    $startDate = $_GET['startDate'] ?? '';
    $endDate = $_GET['endDate'] ?? '';
    $groupBy = $_GET['groupBy'] ?? 'All Students';
    $sortBy = $_GET['sortBy'] ?? 'Surname';

    // Fetch data for summary table
    $summaryData = fetchSummaryAttendanceData($connection2, $startDate, $endDate);

    // Debug: Output the raw summary data
    

    // Generate summary table as plain HTML
    echo '<h2>' . __('Summary Data: ' . date('j M Y', strtotime($startDate)) . ' - ' . date('j M Y', strtotime($endDate))) . '</h2>';
    echo '<table border="1" style="border-collapse: collapse; width: 100%; text-align: center;">';
    echo '<thead>';
    echo '<tr>';
    echo '<th style="text-align: center;">' . __('Home Room') . '</th>';
    echo '<th style="text-align: center;">' . __('Sum of Present') . '</th>';
    echo '<th style="text-align: center;">' . __('Sum of Present - Late') . '</th>';
    echo '<th style="text-align: center;">' . __('Sum of Present - Offsite') . '</th>';
    echo '<th style="text-align: center;">' . __('Sum of Absent') . '</th>';
    echo '<th style="text-align: center;">' . __('Sum of Left') . '</th>';
    echo '<th style="text-align: center;">' . __('Sum of Left - Early') . '</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    foreach ($summaryData as $row) {
        if (!empty($row['homeRoom'])) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['homeRoom']) . '</td>';
            echo '<td>' . htmlspecialchars($row['sumPresent'] ?? 0) . '</td>';
            echo '<td>' . htmlspecialchars($row['sumPresentLate'] ?? 0) . '</td>';
            echo '<td>' . htmlspecialchars($row['sumPresentOffsite'] ?? 0) . '</td>';
            echo '<td>' . htmlspecialchars($row['sumAbsent'] ?? 0) . '</td>';
            echo '<td>' . htmlspecialchars($row['sumLeft'] ?? 0) . '</td>';
            echo '<td>' . htmlspecialchars($row['sumLeftEarly'] ?? 0) . '</td>';
            echo '</tr>';
        }
    }
    echo '</tbody>';
    echo '</table>';

    // Fetch data for detailed table
    $data = fetchAttendanceData($connection2, $startDate, $endDate, $groupBy, $sortBy);

    // Debug: Output the raw detailed data
    

    // Generate detailed table as plain HTML
    echo '<h2>' . __('Detailed Data: ' . date('j M Y', strtotime($startDate)) . ' - ' . date('j M Y', strtotime($endDate))) . '</h2>';
    echo '<table border="1" style="border-collapse: collapse; width: 100%; text-align: center; table-layout: fixed;">';
    echo '<thead>';
    echo '<tr>';
    echo '<th rowspan="2" style="text-align: center; width: 10%;">' . __('Home<br>Room') . '</th>';
    echo '<th rowspan="2" style="text-align: center; width: 20%;">' . __('Name') . '</th>';
    echo '<th colspan="3" style="text-align: center; width: 23%;">' . __('IN') . '</th>';
    echo '<th colspan="3" style="text-align: center; width: 23%;">' . __('OUT') . '</th>';
    echo '<th rowspan="2" style="writing-mode: vertical-rl; transform: rotate(180deg); text-align: center; width: 10%;">' . __('Total') . '</th>';
    echo '</tr>';
    echo '<tr>';
    echo '<th style="writing-mode: vertical-rl; transform: rotate(180deg); text-align: center; width: 4.75%;">' . __('Present') . '</th>';
    echo '<th style="writing-mode: vertical-rl; transform: rotate(180deg); text-align: center; width: 4.75%;">' . __('Present - Late') . '</th>';
    echo '<th style="writing-mode: vertical-rl; transform: rotate(180deg); text-align: center; width: 4.75%;">' . __('Present - Offsite') . '</th>';
    echo '<th style="writing-mode: vertical-rl; transform: rotate(180deg); text-align: center; width: 4.75%;">' . __('Absent') . '</th>';
    echo '<th style="writing-mode: vertical-rl; transform: rotate(180deg); text-align: center; width: 4.75%;">' . __('Left') . '</th>';
    echo '<th style="writing-mode: vertical-rl; transform: rotate(180deg); text-align: center; width: 4.75%;">' . __('Left - Early') . '</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    foreach ($data as $row) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['homeRoom'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($row['name'] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($row['inCount'] ?? 0) . '</td>';
        echo '<td>' . htmlspecialchars($row['lateCount'] ?? 0) . '</td>';
        echo '<td>' . htmlspecialchars($row['offsiteCount'] ?? 0) . '</td>';
        echo '<td>' . htmlspecialchars($row['absentCount'] ?? 0) . '</td>';
        echo '<td>' . htmlspecialchars($row['leftCount'] ?? 0) . '</td>';
        echo '<td>' . htmlspecialchars($row['earlyCount'] ?? 0) . '</td>';
        echo '<td>' . htmlspecialchars($row['totalCount'] ?? 0) . '</td>';
        echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';
}

/**
 * Fetches summary attendance data for each home room.
 */
function fetchSummaryAttendanceData($connection, $startDate, $endDate) {
    $query = $connection->prepare(
        "SELECT 
            gibbonFormGroup.nameShort AS homeRoom,
            SUM(CASE WHEN gibbonAttendanceCode.name = 'Present' THEN 1 ELSE 0 END) AS sumPresent,
            SUM(CASE WHEN gibbonAttendanceCode.name = 'Present - Late' THEN 1 ELSE 0 END) AS sumPresentLate,
            SUM(CASE WHEN gibbonAttendanceCode.name = 'Present - Offsite' THEN 1 ELSE 0 END) AS sumPresentOffsite,
            SUM(CASE WHEN gibbonAttendanceCode.name = 'Absent' THEN 1 ELSE 0 END) AS sumAbsent,
            SUM(CASE WHEN gibbonAttendanceCode.name = 'Left' THEN 1 ELSE 0 END) AS sumLeft,
            SUM(CASE WHEN gibbonAttendanceCode.name = 'Left - Early' THEN 1 ELSE 0 END) AS sumLeftEarly
        FROM 
            (SELECT DISTINCT gibbonPersonID, date, MAX(timestampTaken) AS latestTimestamp
             FROM gibbonAttendanceLogPerson
             WHERE date BETWEEN :startDate AND :endDate
             GROUP BY gibbonPersonID, date) AS latestAttendance
        INNER JOIN gibbonAttendanceLogPerson ON latestAttendance.gibbonPersonID = gibbonAttendanceLogPerson.gibbonPersonID
            AND latestAttendance.latestTimestamp = gibbonAttendanceLogPerson.timestampTaken
        LEFT JOIN gibbonPerson ON gibbonAttendanceLogPerson.gibbonPersonID = gibbonPerson.gibbonPersonID
        LEFT JOIN gibbonStudentEnrolment ON gibbonPerson.gibbonPersonID = gibbonStudentEnrolment.gibbonPersonID
        LEFT JOIN gibbonFormGroup ON gibbonStudentEnrolment.gibbonFormGroupID = gibbonFormGroup.gibbonFormGroupID
        LEFT JOIN gibbonAttendanceCode ON gibbonAttendanceLogPerson.gibbonAttendanceCodeID = gibbonAttendanceCode.gibbonAttendanceCodeID
        WHERE 
            gibbonStudentEnrolment.gibbonSchoolYearID = (
                SELECT gibbonSchoolYearID FROM gibbonSchoolYear WHERE status = 'Current'
            )
        GROUP BY 
            gibbonFormGroup.nameShort"
    );

    $query->execute([
        'startDate' => $startDate,
        'endDate' => $endDate,
    ]);

    return $query->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Fetches attendance data for detailed table.
 */
function fetchAttendanceData($connection, $startDate, $endDate, $groupBy, $sortBy) {
    // Map valid columns for grouping and sorting
    $validGroupBy = [
        'All Students' => 'gibbonPerson.gibbonPersonID',
        'Class' => 'gibbonCourseClass.name',
        'Home Room' => 'gibbonFormGroup.nameShort'
    ];

    $validSortBy = [
        'Surname' => 'gibbonPerson.surname, gibbonPerson.preferredName',
        'First Name' => 'gibbonPerson.preferredName',
        'Home Room' => 'gibbonFormGroup.name'
    ];

    $groupByColumn = $validGroupBy[$groupBy] ?? 'gibbonPerson.gibbonPersonID';
    $sortByColumn = $validSortBy[$sortBy] ?? 'gibbonPerson.surname, gibbonPerson.preferredName';

    $query = $connection->prepare(
        "SELECT 
            latestAttendance.date AS date,
            gibbonFormGroup.nameShort AS homeRoom,
            CONCAT(gibbonPerson.surname, ', ', gibbonPerson.preferredName) AS name,
            COUNT(CASE WHEN gibbonAttendanceCode.name = 'Present' THEN 1 END) AS inCount,
            COUNT(CASE WHEN gibbonAttendanceCode.name = 'Present - Late' THEN 1 END) AS lateCount,
            COUNT(CASE WHEN gibbonAttendanceCode.name = 'Present - Offsite' THEN 1 END) AS offsiteCount,
            COUNT(CASE WHEN gibbonAttendanceCode.name = 'Absent' THEN 1 END) AS absentCount,
            COUNT(CASE WHEN gibbonAttendanceCode.name = 'Left' THEN 1 END) AS leftCount,
            COUNT(CASE WHEN gibbonAttendanceCode.name = 'Left - Early' THEN 1 END) AS earlyCount,
            (
                COUNT(CASE WHEN gibbonAttendanceCode.name = 'Present' THEN 1 END) +
                COUNT(CASE WHEN gibbonAttendanceCode.name = 'Present - Late' THEN 1 END) +
                COUNT(CASE WHEN gibbonAttendanceCode.name = 'Present - Offsite' THEN 1 END) +
                COUNT(CASE WHEN gibbonAttendanceCode.name = 'Absent' THEN 1 END) +
                COUNT(CASE WHEN gibbonAttendanceCode.name = 'Left' THEN 1 END) +
                COUNT(CASE WHEN gibbonAttendanceCode.name = 'Left - Early' THEN 1 END)
            ) AS totalCount
        FROM 
            (SELECT * FROM gibbonAttendanceLogPerson AS innerLog
            WHERE innerLog.timestampTaken = (SELECT MAX(timestampTaken) 
                                            FROM gibbonAttendanceLogPerson 
                                            WHERE gibbonPersonID = innerLog.gibbonPersonID 
                                            AND date = innerLog.date)) AS latestAttendance
        LEFT JOIN gibbonPerson ON latestAttendance.gibbonPersonID = gibbonPerson.gibbonPersonID
        LEFT JOIN gibbonStudentEnrolment ON gibbonPerson.gibbonPersonID = gibbonStudentEnrolment.gibbonPersonID
        LEFT JOIN gibbonFormGroup ON gibbonStudentEnrolment.gibbonFormGroupID = gibbonFormGroup.gibbonFormGroupID
        LEFT JOIN gibbonAttendanceCode ON latestAttendance.gibbonAttendanceCodeID = gibbonAttendanceCode.gibbonAttendanceCodeID
        LEFT JOIN gibbonRole ON gibbonPerson.gibbonRoleIDPrimary = gibbonRole.gibbonRoleID
        WHERE 
            latestAttendance.date BETWEEN :startDate AND :endDate
            AND gibbonRole.category = 'Student'
            AND gibbonStudentEnrolment.gibbonSchoolYearID = (
                SELECT gibbonSchoolYearID FROM gibbonSchoolYear WHERE status = 'Current'
            )
        GROUP BY 
            gibbonPerson.gibbonPersonID
        ORDER BY 
            $sortByColumn"
    );

    $query->execute([
        'startDate' => $startDate,
        'endDate' => $endDate,
    ]);

    return $query->fetchAll(PDO::FETCH_ASSOC);
}
?>

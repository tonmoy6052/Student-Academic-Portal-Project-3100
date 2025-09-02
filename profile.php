<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}
include 'db.php';

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// ===== Grade to GPA mapping =====
$grade_to_gpa = [
    "A+" => 4.0,
    "A"  => 3.75,
    "A-" => 3.5,
    "B+" => 3.25,
    "B"  => 3.0,
    "C"  => 2.5,
    "D"  => 2.0,
    "F"  => 0.0
];

// ===== Handle form submissions =====
if (isset($_POST['add_course'])) {
    var_dump($_POST); // Debug POST data
    $course_name = $_POST['course_name_ct'] ?? '';
    $ct1 = $_POST['ct1'] ?? 0;
    $ct2 = $_POST['ct2'] ?? 0;
    $ct3 = $_POST['ct3'] ?? 0;
    $ct4 = $_POST['ct4'] ?? 0;
    $final_exam = $_POST['final_exam'] ?? 0;
    $attendance = $_POST['attendance'] ?? 0;
    $assignment = $_POST['assignment'] ?? 0;
    $credit = $_POST['credit'] ?? 0;

    $cts = [$ct1, $ct2, $ct3, $ct4];
    rsort($cts); // best 3 CTs
    $best_three_ct_avg = round(($cts[0] + $cts[1] + $cts[2]) / 3, 2);
    $total_marks = $best_three_ct_avg + $final_exam + $attendance + $assignment;
    if ($total_marks >= 80) $grade = "A+";
    elseif ($total_marks >= 75) $grade = "A";
    elseif ($total_marks >= 70) $grade = "A-";
    elseif ($total_marks >= 65) $grade = "B+";
    elseif ($total_marks >= 60) $grade = "B";
    elseif ($total_marks >= 50) $grade = "C";
    elseif ($total_marks >= 40) $grade = "D";
    else $grade = "F";

    $stmt = $conn->prepare("INSERT INTO courses (user_id, course_name, ct1, ct2, ct3, ct4, final_exam, attendance, assignment, credit, total_marks, grade) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isddddddddds", $user_id, $course_name, $ct1, $ct2, $ct3, $ct4, $final_exam, $attendance, $assignment, $credit, $total_marks, $grade);
    $stmt->execute();
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

if (isset($_POST['add_sessional_course'])) {
    var_dump($_POST); // Debug POST data
    $course_name = $_POST['course_name_sessional'] ?? '';
    $credit = $_POST['credit_sessional'] ?? 0;
    $grade = $_POST['grade'] ?? 'F';

    $stmt = $conn->prepare("INSERT INTO extra_courses (user_id, course_name, credit, grade) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isds", $user_id, $course_name, $credit, $grade);
    $stmt->execute();
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

if (isset($_POST['edit_course'])) {
    $course_id = $_POST['course_id'] ?? 0;
    $ct1 = $_POST['ct1'] ?? 0;
    $ct2 = $_POST['ct2'] ?? 0;
    $ct3 = $_POST['ct3'] ?? 0;
    $ct4 = $_POST['ct4'] ?? 0;
    $final_exam = $_POST['final_exam'] ?? 0;
    $attendance = $_POST['attendance'] ?? 0;
    $assignment = $_POST['assignment'] ?? 0;
    $credit = $_POST['credit'] ?? 0;
    $cts = [$ct1, $ct2, $ct3, $ct4];
    rsort($cts); // best 3 CTs
    $best_three_ct_avg = round(($cts[0] + $cts[1] + $cts[2]) / 3, 2);
    $total_marks = $best_three_ct_avg + $final_exam + $attendance + $assignment;
    $total_marks = floatval($total_marks);
    if ($total_marks >= 80) $grade = "A+";
    elseif ($total_marks >= 75) $grade = "A";
    elseif ($total_marks >= 70) $grade = "A-";
    elseif ($total_marks >= 65) $grade = "B+";
    elseif ($total_marks >= 60) $grade = "B";
    elseif ($total_marks >= 50) $grade = "C";
    elseif ($total_marks >= 40) $grade = "D";
    else $grade = "F";

    if($final_exam<15)$grade="F";

    $stmt = $conn->prepare("UPDATE courses SET ct1 = ?, ct2 = ?, ct3 = ?, ct4 = ?, final_exam = ?, attendance = ?, assignment = ?, credit = ?, total_marks = ?, grade = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("dddddddddsii", $ct1, $ct2, $ct3, $ct4, $final_exam, $attendance, $assignment, $credit, $total_marks, $grade, $course_id, $user_id);
    $stmt->execute();
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF'] . "?edit_course_id=0"); // Clear edit mode
    exit();
}

if (isset($_POST['edit_sessional_course'])) {
    $course_id = $_POST['course_id'] ?? 0;
    $credit = $_POST['credit_sessional'] ?? 0;
    $grade = $_POST['grade'] ?? 'F';

    $stmt = $conn->prepare("UPDATE extra_courses SET credit = ?, grade = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("dsii", $credit, $grade, $course_id, $user_id);
    $stmt->execute();
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF'] . "?edit_sessional_course_id=0"); // Clear edit mode
    exit();
}

if (isset($_POST['delete_course'])) {
    $course_id = $_POST['course_id'] ?? 0;
    $stmt = $conn->prepare("DELETE FROM courses WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $course_id, $user_id);
    $stmt->execute();
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

if (isset($_POST['delete_sessional_course'])) {
    $course_id = $_POST['course_id'] ?? 0;
    $stmt = $conn->prepare("DELETE FROM extra_courses WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $course_id, $user_id);
    $stmt->execute();
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// ===== Fetch data =====
$courses_data = [];
$stmt = $conn->prepare("SELECT id, user_id, course_name, ct1, ct2, ct3, ct4, final_exam, attendance, assignment, credit, total_marks, grade FROM courses WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $courses_data[] = $row;
}
$stmt->close();

$sessional_courses = [];
$stmt = $conn->prepare("SELECT id, user_id, course_name, credit, grade FROM extra_courses WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $sessional_courses[] = $row;
}
$stmt->close();

$edit_course_id = isset($_GET['edit_course_id']) ? intval($_GET['edit_course_id']) : 0;
$edit_sessional_course_id = isset($_GET['edit_sessional_course_id']) ? intval($_GET['edit_sessional_course_id']) : 0;

$total_quality_points = 0;
$total_credits = 0;

foreach ($courses_data as $c) {
    $cts = [
        isset($c['ct1']) ? $c['ct1'] : 0,
        isset($c['ct2']) ? $c['ct2'] : 0,
        isset($c['ct3']) ? $c['ct3'] : 0,
        isset($c['ct4']) ? $c['ct4'] : 0
    ];
    rsort($cts); // best 3 CTs
    $best_three_ct_avg = round(($cts[0] + $cts[1] + $cts[2]) / 3, 2);
    $grade = $c['grade'];

    $gpa = isset($grade_to_gpa[$grade]) ? $grade_to_gpa[$grade] : 0.0;
    $credit = isset($c['credit']) ? $c['credit'] : 0;
    $total_quality_points += $gpa * $credit;
    $total_credits += $credit;
}

foreach ($sessional_courses as $ec) {
    $gpa = isset($grade_to_gpa[$ec['grade']]) ? $grade_to_gpa[$ec['grade']] : 0.0;
    $credit = isset($ec['credit']) ? $ec['credit'] : 0;
    $total_quality_points += $gpa * $credit;
    $total_credits += $credit;
}

$cgpa = $total_credits > 0 ? round($total_quality_points / $total_credits, 2) : 0;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Profile - <?php echo htmlspecialchars($username); ?></title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 20px;
            background: linear-gradient(135deg, #e0f7fa, #f0e6f5); /* Gradient background */
            color: #333;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            background: #cecbd4;
            background: linear-gradient(90deg, rgba(206, 203, 212, 1) 0%, rgba(224, 220, 252, 1) 34%, rgba(252, 250, 189, 1) 100%);
        }
        .form-container {
            max-width: 1500px;
            width: 100%;
            padding:30px 30px 30px 60px;
        }
        h1 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 30px;
            font-size: 2.5em;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
        }
        h2 {
            color: #34495e;
            margin-top: 40px;
            font-size: 1.8em;
            border-bottom: 2px solid #3498db;
            padding-bottom: 5px;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            overflow: hidden;
            background-color: rgba(247, 238, 232, 1);
        }
        th, td {
            border: 1px solid #ddd;
            padding: 15px;
            text-align: center;
            transition: background 0.3s;
        }
        th {
            background: #3498db;
            color: white;
            font-weight: bold;
        }
        form {
            display: flex;
            flex-direction: column ;
            justify-content: center;
            align-items: center;
            margin-bottom: 30px;
            padding: 0px;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            background-color: rgba(242, 232, 226, 1);
        }
        .input-group {
            margin: 15px 0;
            display: flex;
            align-items: center;
        }
        .input-group label {
            display: inline-block;
            width: 150px;
            font-weight: bold;
            color: #2c3e50;
        }
        input, select {
            padding: 12px;
            margin: 5px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s, box-shadow 0.3s;
            width: 250px;
            background: #fff;
        }
        input:focus, select:focus {
            border-color: #e74c3c;
            outline: none;
            box-shadow: 0 0 8px rgba(231, 76, 60, 0.4);
        }
        button {
            padding: 12px 25px;
            background: #2ecc71;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s, transform 0.2s;
        }
        button:hover {
            background: #27ae60;
            transform: translateY(-2px);
        }
        .delete-btn {
            background: #e74c3c;
        }
        .delete-btn:hover {
            background: #c0392b;
        }
        .edit-btn {
            background: #3498db;
            margin-right: 10px;
            display: inline-block;
            text-decoration: none;
            color: white;
            text-align: center;
            padding: 12px 20px;
            border-radius: 8px;
            transition: background 0.3s, transform 0.2s;
        }
        .edit-btn:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }
        .logout-btn {
            width: 50px;
            background: #e74c3c;
            padding: 12px 20px;
            margin-bottom: 20px;
            display: inline-block;
            text-decoration: none;
            color: white;
            border-radius: 8px;
            transition: background 0.3s, transform 0.2s;
            text-align: center;
        }
        .logout-btn:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }
        .cgpa-box {
            padding: 20px 40px;
            background: linear-gradient(135deg, #ffeb3b, #ff9800);
            border: 3px solid #f57c00;
            border-radius: 15px;
            display: inline-block;
            font-size: 28px;
            font-weight: bold;
            color: #fff;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
            text-align: center;
            margin-top: 20px;
        }
        .Button1 {
            padding: 12px 120px;
        }
        .Button2 {
            padding: 12px 100px;
        }
        .header {
            display: flex;
            justify-content: flex-end;
            width: 100%;
            max-width: 1500px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h1>Welcome, <?php echo htmlspecialchars($username); ?></h1>

        <h2>Add New Course (with CTs + Final + Attendance + Assignment)</h2>
        <form method="POST">
            <div class="input-group">
                <label>Course Name:</label>
                <input type="text" name="course_name_ct" required>
            </div>
            <div class="input-group">
                <label>CT1:</label>
                <input type="number" step="0.01" name="ct1" required>
            </div>
            <div class="input-group">
                <label>CT2:</label>
                <input type="number" step="0.01" name="ct2" required>
            </div>
            <div class="input-group">
                <label>CT3:</label>
                <input type="number" step="0.01" name="ct3" required>
            </div>
            <div class="input-group">
                <label>CT4:</label>
                <input type="number" step="0.01" name="ct4" required>
            </div>
            <div class="input-group">
                <label>Final Exam:</label>
                <input type="number" step="0.01" name="final_exam" required>
            </div>
            <div class="input-group">
                <label>Attendance:</label>
                <input type="number" step="0.01" name="attendance" required>
            </div>
            <div class="input-group">
                <label>Assignment:</label>
                <input type="number" step="0.01" name="assignment" required>
            </div>
            <div class="input-group">
                <label>Credit:</label>
                <input type="number" step="0.01" name="credit" required>
            </div>
            <button type="submit" name="add_course" class="Button1">Add Course</button>
        </form>

        <h2>Add sessional Course (Grade + Credit only)</h2>
        <form method="POST">
            <div class="input-group">
                <label>Course Name:</label>
                <input type="text" name="course_name_sessional" required>
            </div>
            <div class="input-group">
                <label>Grade:</label>
                <select name="grade" required>
                    <option value="A+">A+</option>
                    <option value="A">A</option>
                    <option value="A-">A-</option>
                    <option value="B+">B+</option>
                    <option value="B">B</option>
                    <option value="C">C</option>
                    <option value="D">D</option>
                    <option value="F">F</option>
                </select>
            </div>
            <div class="input-group">
                <label>Credit:</label>
                <input type="number" step="0.01" name="credit_sessional" required>
            </div>
            <button type="submit" name="add_sessional_course" class="Button2">Add sessional Course</button>
        </form>

        <h2>Your Courses (with CTs)</h2>
        <table>
            <tr>
                <th>Course</th><th>CT1</th><th>CT2</th><th>CT3</th><th>CT4</th>
                <th>Attendance</th><th>Assignment</th><th>Final</th><th>Credit</th><th>Total Marks</th><th>Grade</th><th>Actions</th>
            </tr>
            <?php foreach ($courses_data as $cc): ?>
            <tr>
                <td><?php echo htmlspecialchars($cc['course_name'] ?? 'N/A'); ?></td>
                <td><?php echo isset($cc['ct1']) ? $cc['ct1'] : 'N/A'; ?></td>
                <td><?php echo isset($cc['ct2']) ? $cc['ct2'] : 'N/A'; ?></td>
                <td><?php echo isset($cc['ct3']) ? $cc['ct3'] : 'N/A'; ?></td>
                <td><?php echo isset($cc['ct4']) ? $cc['ct4'] : 'N/A'; ?></td>
                <td><?php echo isset($cc['attendance']) ? $cc['attendance'] : 'N/A'; ?></td>
                <td><?php echo isset($cc['assignment']) ? $cc['assignment'] : 'N/A'; ?></td>
                <td><?php echo isset($cc['final_exam']) ? $cc['final_exam'] : 'N/A'; ?></td>
                <td><?php echo isset($cc['credit']) ? $cc['credit'] : 'N/A'; ?></td>
                <td><?php echo isset($cc['total_marks']) ? $cc['total_marks'] : 'N/A'; ?></td>
                <td><?php echo isset($cc['grade']) ? $cc['grade'] : 'N/A'; ?></td>
                <td>
                    <a href="?edit_course_id=<?php echo $cc['id']; ?>" class="edit-btn">Edit</a>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="course_id" value="<?php echo $cc['id']; ?>">
                        <button type="submit" name="delete_course" class="delete-btn">Delete</button>
                    </form>
                </td>
            </tr>
            <?php if ($edit_course_id == $cc['id']): ?>
                <?php
                // Debug to check the correct course is being edited
                var_dump("Editing course ID: " . $cc['id']);
                ?>
            <tr>
                <td colspan="12">
                    <form method="POST">
                        <h3>Edit Course: <?php echo htmlspecialchars($cc['course_name'] ?? 'N/A'); ?></h3>
                        <input type="hidden" name="course_id" value="<?php echo $cc['id']; ?>">
                        <div class="input-group">
                            <label>CT1:</label>
                            <input type="number" step="0.01" name="ct1" value="<?php echo isset($cc['ct1']) ? $cc['ct1'] : 0; ?>" required>
                        </div>
                        <div class="input-group">
                            <label>CT2:</label>
                            <input type="number" step="0.01" name="ct2" value="<?php echo isset($cc['ct2']) ? $cc['ct2'] : 0; ?>" required>
                        </div>
                        <div class="input-group">
                            <label>CT3:</label>
                            <input type="number" step="0.01" name="ct3" value="<?php echo isset($cc['ct3']) ? $cc['ct3'] : 0; ?>" required>
                        </div>
                        <div class="input-group">
                            <label>CT4:</label>
                            <input type="number" step="0.01" name="ct4" value="<?php echo isset($cc['ct4']) ? $cc['ct4'] : 0; ?>" required>
                        </div>
                        <div class="input-group">
                            <label>Final Exam:</label>
                            <input type="number" step="0.01" name="final_exam" value="<?php echo isset($cc['final_exam']) ? $cc['final_exam'] : 0; ?>" required>
                        </div>
                        <div class="input-group">
                            <label>Attendance:</label>
                            <input type="number" step="0.01" name="attendance" value="<?php echo isset($cc['attendance']) ? $cc['attendance'] : 0; ?>" required>
                        </div>
                        <div class="input-group">
                            <label>Assignment:</label>
                            <input type="number" step="0.01" name="assignment" value="<?php echo isset($cc['assignment']) ? $cc['assignment'] : 0; ?>" required>
                        </div>
                        <div class="input-group">
                            <label>Credit:</label>
                            <input type="number" step="0.5" name="credit" value="<?php echo isset($cc['credit']) ? $cc['credit'] : 0; ?>" required>
                        </div>
                        <button type="submit" name="edit_course">Update Course</button>
                    </form>
                </td>
            </tr>
            <?php endif; ?>
            <?php endforeach; ?>
        </table>

        <!-- Table of sessional Courses -->
        <h2>Your sessional Courses</h2>
        <table>
            <tr>
                <th>Course</th><th>Grade</th><th>Credit</th><th>Action</th>
            </tr>
            <?php foreach ($sessional_courses as $ec): ?>
            <tr>
                <td><?php echo htmlspecialchars($ec['course_name'] ?? 'N/A'); ?></td>
                <td><?php echo isset($ec['grade']) ? $ec['grade'] : 'N/A'; ?></td>
                <td><?php echo isset($ec['credit']) ? $ec['credit'] : 'N/A'; ?></td>
                <td>
                    <a href="?edit_sessional_course_id=<?php echo $ec['id']; ?>" class="edit-btn">Edit</a>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="course_id" value="<?php echo $ec['id']; ?>">
                        <button type="submit" name="delete_sessional_course" class="delete-btn">Delete</button>
                    </form>
                </td>
            </tr>
            <?php if ($edit_sessional_course_id == $ec['id']): ?>
                <?php
                // Debug to check the correct sessional course is being edited
                var_dump("Editing sessional course ID: " . $ec['id']);
                ?>
            <tr>
                <td colspan="4">
                    <form method="POST">
                        <h3>Edit sessional Course: <?php echo htmlspecialchars($ec['course_name'] ?? 'N/A'); ?></h3>
                        <input type="hidden" name="course_id" value="<?php echo $ec['id']; ?>">
                        <div class="input-group">
                            <label>Grade:</label>
                            <select name="grade" required>
                                <option value="A+" <?php if (isset($ec['grade']) && $ec['grade'] == 'A+') echo 'selected'; ?>>A+</option>
                                <option value="A" <?php if (isset($ec['grade']) && $ec['grade'] == 'A') echo 'selected'; ?>>A</option>
                                <option value="A-" <?php if (isset($ec['grade']) && $ec['grade'] == 'A-') echo 'selected'; ?>>A-</option>
                                <option value="B+" <?php if (isset($ec['grade']) && $ec['grade'] == 'B+') echo 'selected'; ?>>B+</option>
                                <option value="B" <?php if (isset($ec['grade']) && $ec['grade'] == 'B') echo 'selected'; ?>>B</option>
                                <option value="C" <?php if (isset($ec['grade']) && $ec['grade'] == 'C') echo 'selected'; ?>>C</option>
                                <option value="D" <?php if (isset($ec['grade']) && $ec['grade'] == 'D') echo 'selected'; ?>>D</option>
                                <option value="F" <?php if (isset($ec['grade']) && $ec['grade'] == 'F') echo 'selected'; ?>>F</option>
                            </select>
                        </div>
                        <div class="input-group">
                            <label>Credit:</label>
                            <input type="number" step="0.5" name="credit_sessional" value="<?php echo isset($ec['credit']) ? $ec['credit'] : 0; ?>" required>
                        </div>
                        <button type="submit" name="edit_sessional_course">Update sessional Course</button>
                    </form>
                </td>
            </tr>
            <?php endif; ?>
            <?php endforeach; ?>
        </table>

        <h2 style="padding: 0px 0px 30px 500px;">Your CGPA: <span class="cgpa-box"><?php echo $cgpa; ?></span></h2>
    </div>
    <div >
        <a href="index.html" class="logout-btn">Logout</a>
    </div>
</body>
</html>
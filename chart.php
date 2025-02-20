<?php
$host = 'localhost';
$user = 'root';
$password = '1234';
$database = 'car_report';

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// รับค่าฟิลเตอร์จาก GET parameters
$min_age = isset($_GET['min_age']) ? (int)$_GET['min_age'] : 0;
$max_age = isset($_GET['max_age']) ? (int)$_GET['max_age'] : 100;
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$selected_gender = isset($_GET['gender']) ? $_GET['gender'] : "ทั้งหมด";
$selected_symptom = isset($_GET['symptom']) ? $_GET['symptom'] : "ทั้งหมด";
$selected_hospital = isset($_GET['hospital']) ? $_GET['hospital'] : "ทั้งหมด";


// สร้าง WHERE Clause ตามฟิลเตอร์ที่เลือก
$where_clause = "WHERE report_patient_age BETWEEN $min_age AND $max_age";
if ($selected_date) {
    $where_clause .= " AND DATE(report_date) = '$selected_date'";
}
if ($selected_gender !== "ทั้งหมด") {
    $where_clause .= " AND report_patient_gender = '$selected_gender'";
}
if ($selected_symptom !== "ทั้งหมด") {
    if ($selected_symptom === "อื่นๆ") {
        $where_clause .= " AND report_reason NOT LIKE '%อุบัติเหตุ%' AND report_reason NOT LIKE '%อาการป่วย%'";
    } else {
        $where_clause .= " AND report_reason LIKE '%$selected_symptom%'";
    }
}
if ($selected_hospital !== "ทั้งหมด") {
    $where_clause .= " AND hospital_waypoint = '$selected_hospital'";
}


// Query ดึงข้อมูล
$sql = "SELECT 
    report_reason,
    SUM(CASE WHEN report_patient_gender = 'ชาย' THEN 1 ELSE 0 END) as male_count,
    SUM(CASE WHEN report_patient_gender = 'หญิง' THEN 1 ELSE 0 END) as female_count
    FROM emergency_case 
    $where_clause
    GROUP BY report_reason";

$result = $conn->query($sql);

$labels = [];
$maleData = [];
$femaleData = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $labels[] = $row['report_reason'];
        $maleData[] = $row['male_count'];
        $femaleData[] = $row['female_count'];
    }
}

$hospital_query = "SELECT DISTINCT hospital_waypoint FROM emergency_case";
$hospital_result = $conn->query($hospital_query);

$hospital_options = [];
if ($hospital_result->num_rows > 0) {
    while ($row = $hospital_result->fetch_assoc()) {
        $hospital_options[] = $row['hospital_waypoint'];
    }
}


$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="path/to/font-awesome/css/font-awesome.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Itim&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/style.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/index.js"></script>
    <title>ดูรายงานเคส</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        canvas {
            width: 80% !important;
            height: 60% !important;
            max-width: 800px;
            max-height: 600px;
            margin: auto;
            display: block;
        }

        .filter-container {
            text-align: center;
            margin: 20px 0;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }

        .age-input {
            width: 60px;
            padding: 8px;
            font-size: 16px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }

        button {
            padding: 8px 15px;
            font-size: 16px;
            border-radius: 4px;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
        }

        button:hover {
            background-color: #45a049;
        }
    </style>
</head>

<body>
    <header class="header">
        <div class="logo-section">
            <img src="img/logo.jpg" alt="" class="logo">
            <h1 href="ceo_home_page.html" style="font-family: Itim;">CEO - HOME</h1>
        </div>
        <nav class="nav" style="margin-left: 20%;">
            <a href="approve_page.html" class="nav-item">อนุมัติคำสั่งซื้อ/เช่า</a>
            <a href="approve_clam_page.html" class="nav-item">อนุมัติเคลม</a>
            <a href="summary_page.html" class="nav-item">สรุปยอดขาย</a>
            <a href="case_report_page.html" class="nav-item active">ดูสรุปรายงานเคส</a>
            <a href="history_fixed_page.html" class="nav-item">ประวัติการส่งซ่อมรถและอุปกรณ์การแพทย์</a>
            <a href="static_car_page.html" class="nav-item">สถิติการใช้งานรถ</a>
        </nav>
    </header>

    <div id="chart-labels" style="display: none;"><?php echo json_encode($labels); ?></div>
    <div id="chart-maleData" style="display: none;"><?php echo json_encode($maleData); ?></div>
    <div id="chart-femaleData" style="display: none;"><?php echo json_encode($femaleData); ?></div>

    <h1 style="text-align: center;">ดูสรุปรายงานเคสฉุกเฉิน</h1>
    <main class="main-content">
        <div class="search-section">
            <div class="filter-icon">
                <i class="fa-solid fa-filter"></i> <!-- ไอคอน Filter -->
            </div>

            <div class="filter-sidebar" id="filterSidebar">
                <div class="sidebar-header">
                    <h2>ตัวกรอง</h2>
                    <button class="close-sidebar">&times;</button>
                </div>
                <div class="sidebar-content">
                    <label for="calendarSelect">เลือกวันที่:</label>
                    <input class="calendar-selected" id="calendarSelect" type="text" placeholder="เลือกวันที่" value="2025-01-22">


                    <label for="filter-gender">เพศ:</label>
                    <select id="filter-gender-list" class="filter-select">
                        <option value="ทั้งหมด" <?php if ($selected_gender == "ทั้งหมด") echo "selected"; ?>>ทั้งหมด</option>
                        <option value="ชาย" <?php if ($selected_gender == "ชาย") echo "selected"; ?>>ชาย</option>
                        <option value="หญิง" <?php if ($selected_gender == "หญิง") echo "selected"; ?>>หญิง</option>
                    </select>

                    <!-- แก้เป็น radio -->
                    <label>ช่วงอายุ:</label>
                    <input type="number" id="minAge" class="age-input" value="<?php echo $min_age; ?>" min="0" max="100">
                    <span>ถึง</span>
                    <input type="number" id="maxAge" class="age-input" value="<?php echo $max_age; ?>" min="0" max="100">
                    <span>ปี</span>
                    <br><br>

                    <label for="filter-symtom">สาเหตุ/อาการป่วย:</label>
                    <select id="filter-symtom-list" class="filter-select">
                        <option value="ทั้งหมด" <?php if ($selected_symptom == "ทั้งหมด") echo "selected"; ?>>ทั้งหมด</option>
                        <option value="อุบัติเหตุ" <?php if ($selected_symptom == "อุบัติเหตุ") echo "selected"; ?>>อุบัติเหตุ</option>
                        <option value="อาการป่วย" <?php if ($selected_symptom == "อาการป่วย") echo "selected"; ?>>อาการป่วย</option>
                        <option value="อื่นๆ" <?php if ($selected_symptom == "อื่นๆ") echo "selected"; ?>>อื่นๆ</option>
                    </select>

                    <label for="filter-hospital">โรงพยาบาล:</label>
                    <select id="filter-hospital-list" class="filter-select">
                        <option value="ทั้งหมด" <?php if ($selected_hospital == "ทั้งหมด") echo "selected"; ?>>ทั้งหมด</option>
                        <?php foreach ($hospital_options as $hospital) : ?>
                            <option value="<?php echo $hospital; ?>" <?php if ($selected_hospital == $hospital) echo "selected"; ?>>
                                <?php echo $hospital; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>


                </div>
            </div>
        </div>

    </main>

    <canvas id="case"></canvas>

    <script>
        const labels = <?php echo json_encode($labels); ?>;
        const maleData = <?php echo json_encode($maleData); ?>;
        const femaleData = <?php echo json_encode($femaleData); ?>;

        const mychart = new Chart(document.getElementById("case"), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'ชาย',
                    data: maleData,
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }, {
                    label: 'หญิง',
                    data: femaleData,
                    backgroundColor: 'rgba(255, 99, 132, 0.5)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    x: {
                        stacked: true,
                        title: {
                            display: true,
                            text: 'สาเหตุการแจ้งเหตุ'
                        }
                    },
                    y: {
                        stacked: true,
                        title: {
                            display: true,
                            text: 'จำนวนผู้ป่วย'
                        }
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'สถิติผู้ป่วยฉุกเฉินแยกตามสาเหตุและเพศ',
                        font: {
                            size: 18
                        }
                    },
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: {
                            boxWidth: 20,
                            padding: 15
                        }
                    }
                }
            }
        });
        const originalLabels = [...labels]; // Store original labels
        const originalMaleData = [...maleData]; // Store original male data
        const originalFemaleData = [...femaleData]; // Store original female data

        document.getElementById('filter-symtom-list').addEventListener('change', function() {
            const symptom = this.value;

            if (symptom === 'ทั้งหมด') {
                // Show all data
                mychart.data.labels = originalLabels;
                mychart.data.datasets[0].data = originalMaleData;
                mychart.data.datasets[1].data = originalFemaleData;
            } else if (symptom === 'อื่นๆ') {
                // Filter out labels that don't contain 'อุบัติเหตุ' or 'อาการป่วย'
                const filteredIndices = originalLabels.reduce((acc, label, index) => {
                    if (!label.includes('อุบัติเหตุ') && !label.includes('อาการป่วย')) {
                        acc.push(index);
                    }
                    return acc;
                }, []);

                const filteredLabels = filteredIndices.map(i => originalLabels[i]);
                const filteredMaleData = filteredIndices.map(i => originalMaleData[i]);
                const filteredFemaleData = filteredIndices.map(i => originalFemaleData[i]);

                mychart.data.labels = filteredLabels;
                mychart.data.datasets[0].data = filteredMaleData;
                mychart.data.datasets[1].data = filteredFemaleData;
            } else {
                // Filter data based on selected symptom (อุบัติเหตุ or อาการป่วย)
                const filteredIndices = originalLabels.reduce((acc, label, index) => {
                    if (label.includes(symptom)) {
                        acc.push(index);
                    }
                    return acc;
                }, []);

                const filteredLabels = filteredIndices.map(i => originalLabels[i]);
                const filteredMaleData = filteredIndices.map(i => originalMaleData[i]);
                const filteredFemaleData = filteredIndices.map(i => originalFemaleData[i]);

                mychart.data.labels = filteredLabels;
                mychart.data.datasets[0].data = filteredMaleData;
                mychart.data.datasets[1].data = filteredFemaleData;
            }

            mychart.update();
        });

        document.getElementById('filter-gender-list').addEventListener('change', function() {
            const gender = this.value;

            if (gender === 'ชาย') {
                mychart.data.datasets[0].hidden = false;
                mychart.data.datasets[1].hidden = true;
            } else if (gender === 'หญิง') {
                mychart.data.datasets[0].hidden = true;
                mychart.data.datasets[1].hidden = false;
            } else {
                mychart.data.datasets[0].hidden = false;
                mychart.data.datasets[1].hidden = false;
            }
            mychart.update();
        });


        function updateAgeRange() {
            const minAge = document.getElementById('minAge').value;
            const maxAge = document.getElementById('maxAge').value;

            if (parseInt(minAge) > parseInt(maxAge)) {
                alert('กรุณาระบุช่วงอายุให้ถูกต้อง');
                return;
            }

            window.location.href = `chart.php?min_age=${minAge}&max_age=${maxAge}`;
        }

        // สคริปต์สำหรับเปิด-ปิด Sidebar
        document.addEventListener("DOMContentLoaded", () => {
            const filterIcon = document.querySelector(".filter-icon");
            const sidebar = document.getElementById("filterSidebar");
            const closeSidebar = document.querySelector(".close-sidebar");

            // เปิด Sidebar
            filterIcon.addEventListener("click", () => {
                sidebar.classList.add("open");
            });

            // ปิด Sidebar
            closeSidebar.addEventListener("click", () => {
                sidebar.classList.remove("open");
            });

            // ปิด Sidebar เมื่อคลิกนอก Sidebar
            document.addEventListener("click", (e) => {
                if (!sidebar.contains(e.target) && !filterIcon.contains(e.target)) {
                    sidebar.classList.remove("open");
                }
            });

        });

        flatpickr("#calendarSelect", {
            dateFormat: "Y-m-d",
            defaultDate: "<?php echo $selected_date; ?>",
            onChange: updateFilters
        });


        function updateFilters() {
            const date = document.getElementById("calendarSelect").value;
            const gender = document.getElementById("filter-gender-list").value;
            const minAge = document.getElementById("minAge").value;
            const maxAge = document.getElementById("maxAge").value;
            const symptom = document.getElementById("filter-symtom-list").value;
            const hospital = document.getElementById("filter-hospital-list").value;

            // สร้าง URL Query
            const params = new URLSearchParams({
                date,
                gender,
                min_age: minAge,
                max_age: maxAge,
                symptom,
                hospital,
            });

            // อัปเดต URL โดยไม่โหลดหน้าใหม่
            const newUrl = window.location.pathname + "?" + params.toString();
            window.history.replaceState({}, "", newUrl);

            // โหลดข้อมูลใหม่ผ่าน AJAX
            fetch(newUrl)
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');

                    // อัปเดตข้อมูลกราฟใหม่
                    const newLabels = JSON.parse(doc.getElementById('chart-labels').textContent);
                    const newMaleData = JSON.parse(doc.getElementById('chart-maleData').textContent);
                    const newFemaleData = JSON.parse(doc.getElementById('chart-femaleData').textContent);

                    // อัปเดตกราฟ Chart.js
                    mychart.data.labels = newLabels;
                    mychart.data.datasets[0].data = newMaleData;
                    mychart.data.datasets[1].data = newFemaleData;
                    mychart.update();
                })
                .catch(error => console.error('Error fetching updated data:', error));
        }



        document.getElementById("calendarSelect").flatpickr({
            dateFormat: "Y-m-d",
            onChange: updateFilters
        });
        document.getElementById("filter-gender-list").addEventListener("change", updateFilters); // ใช้ ID ถูกต้อง
        document.getElementById("filter-symtom-list").addEventListener("change", updateFilters); // ใช้ ID ถูกต้อง
        document.getElementById("minAge").addEventListener("input", updateFilters);
        document.getElementById("maxAge").addEventListener("input", updateFilters);
        document.getElementById("filter-hospital-list").addEventListener("change", updateFilters);

    </script>
</body>

</html>
<?php
$host = 'localhost';
$user = 'root';
$password = '1234';
$database = 'car_report';

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get age range from GET parameters
$min_age = isset($_GET['min_age']) ? (int)$_GET['min_age'] : 0;
$max_age = isset($_GET['max_age']) ? (int)$_GET['max_age'] : 100;

// Create where clause for age range
$where_clause = "WHERE report_patient_age BETWEEN $min_age AND $max_age";

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
                        <option value="" selected hidden>กรุณาเลือกเพศ</option>
                        <option value="ทั้งหมด" selected>ทั้งหมด</option>
                        <option value="ชาย">ชาย</option>
                        <option value="หญิง">หญิง</option>
                    </select>

                    <!-- แก้เป็น radio -->
                    <label>ช่วงอายุ:</label>
                    <input type="number" id="minAge" class="age-input" value="<?php echo $min_age; ?>" min="0" max="100">
                    <span>ถึง</span>
                    <input type="number" id="maxAge" class="age-input" value="<?php echo $max_age; ?>" min="0" max="100">
                    <span>ปี</span>
                    <button onclick="updateAgeRange()">กรอง</button>
                    <br><br>

                    <label for="filter-symtom">สาเหตุ/อาการป่วย:</label>
                    <select id="filter-symtom-list" class="filter-select">
                        <option value="" selected hidden>กรุณาเลือก</option>
                        <option value="ทั้งหมด" selected>ทั้งหมด</option>
                        <option value="อุบัติเหตุ">อุบัติเหตุ</option>
                        <option value="อาการป่วย">อาการป่วย</option>
                        <option value="อื่นๆ">อื่นๆ</option>
                    </select>

                    <label for="filter-hospital">โรงพยาบาล:</label>
                    <select id="filter-hospital-list" class="filter-select">
                        <option value="" selected hidden>กรุณาเลือกโรงพยาบาล</option>
                        <option value="0" selected>ทั้งหมด</option>
                        <option value="1">โรงพยาบาลศูนย์มะเร็งกรุงเทพฯ</option>
                        <option value="2">โรงพยาบาลมนารมย์</option>
                        <option value="3">โรงพยาบาลปิยะเวท</option>
                        <option value="4">โรงพยาบาลเสรีรักษ์</option>
                        <option value="5">โรงพยาบาลจงจินต์มูลนิธิ</option>
                        <option value="6">โรงพยาบาลวิชัยเวช อินเตอร์เนชั่นแนล แยกไฟฉาย</option>
                        <option value="7">โรงพยาบาลกมล ศัลยกรรมตกแต่งและแปลงเพศ</option>
                        <option value="8">โรงพยาบาลจักษุ รัตนิน</option>
                        <option value="9">โรงพยาบาลสถาบันโรคไตภูมิราชนครินทร์</option>
                        <option value="10">โรงพยาบาลเบทเทอร์บีอิ้ง</option>
                        <option value="11">โรงพยาบาลยันฮี</option>
                        <option value="12">โรงพยาบาลผู้สูงอายุกล้วยน้ำไท 2</option>
                        <option value="13">โรงพยาบาลคามิลเลียน</option>
                        <option value="14">โรงพยาบาลบางปะกอก 9 อินเตอร์เนชั่นแนล</option>
                        <option value="15">โรงพยาบาลเกษมราษฎร์ รามคำแหง</option>
                        <option value="16">โรงพยาบาลบางขุนเทียน 1</option>
                        <option value="17">โรงพยาบาลธนบุรี บำรุงเมือง</option>
                        <option value="18">โรงพยาบาลวิมุต</option>
                        <option value="19">โรงพยาบาลตำรวจ</option>
                        <option value="20">โรงพยาบาลเลิดสิน</option>
                        <option value="21">โรงพยาบาลนพรัตนราชธานี</option>
                        <option value="22">โรงพยาบาลราชวิถี</option>
                        <option value="23">โรงพยาบาลสงฆ์</option>
                        <option value="24">โรงพยาบาลพระมงกุฎเกล้า</option>
                        <option value="25">โรงพยาบาลภูมิพลอดุลยเดช</option>
                        <option value="26">โรงพยาบาลทหารผ่านศึก</option>
                        <option value="27">โรงพยาบาลศิริราช</option>
                        <option value="28">โรงพยาบาลสมเด็จพระปิ่นเกล้า</option>
                        <option value="29">โรงพยาบาลจุฬาลงกรณ์</option>
                        <option value="30">โรงพยาบาลรามาธิบดี</option>
                        <option value="31">คณะแพทยศาสตร์วชิรพยาบาล มหาวิทยาลัยนวมินทราธิราช</option>
                        <option value="32">โรงพยาบาลลาดกระบังกรุงเทพมหานคร</option>
                        <option value="33">โรงพยาบาลคลองสามวา</option>
                        <option value="34">โรงพยาบาลบางนากรุงเทพมหานคร</option>
                        <option value="35">โรงพยาบาลเวชศาสตร์เขตร้อน</option>
                        <option value="36">โรงพยาบาลนวุติสมเด็จย่า</option>
                        <option value="37">โรงพยาบาลประสานมิตร</option>
                        <option value="38">โรงพยาบาลทหารเรือกรุงเทพ</option>
                        <option value="39">โรงพยาบาลมูลนิธิมิราเคิล ออฟไลฟ์</option>
                        <option value="40">ทัณฑสถานโรงพยาบาลราชทัณฑ์</option>
                        <option value="41">โรงพยาบาลจุฬาภรณ์</option>
                        <option value="42">สถาบันสุขภาพเด็กแห่งชาติมหาราชินี</option>
                        <option value="43">โรงพยาบาลศิริราช ปิยมหาราชการุณย์</option>
                        <option value="44">โรงพยาบาลพระจอมเกล้าเจ้าคุณทหาร</option>
                        <option value="45">โรงพยาบาลการไฟฟ้านครหลวง</option>
                        <option value="46">โรงพยาบาลสวนเบญจกิติเฉลิมพระเกียรติ 84 พรรษา</option>
                        <option value="47">โรงพยาบาลเจริญกรุงประชารักษ์</option>
                        <option value="48">โรงพยาบาลกลาง</option>
                        <option value="49">โรงพยาบาลตากสิน</option>
                        <option value="50">โรงพยาบาลสิรินธร</option>
                        <option value="51">โรงพยาบาลราชพิพัฒน์</option>
                        <option value="52">โรงพยาบาลหลวงพ่อทวีศักดิ์ ชุตินฺธโร อุทิศ</option>
                        <option value="53">โรงพยาบาลเวชการุณย์รัศมิ์</option>
                        <option value="54">โรงพยาบาลนคราภิบาล</option>
                        <option value="55">โรงพยาบาลผู้สูงอายุบางขุนเทียน</option>
                        <option value="56">โรงพยาบาลรัตนประชารักษ์</option>
                        <option value="57">โรงพยาบาลบางนากรุงเทพมหานคร</option>
                        <option value="58">โรงพยาบาลกรุงเทพ</option>
                        <option value="59">โรงพยาบาล กรุงเทพคริสเตียน</option>
                        <option value="60">โรงพยาบาลกล้วยน้ำไท</option>
                        <option value="61">โรงพยาบาลเกษมราษฎร์ บางแค</option>
                        <option value="62">โรงพยาบาลเกษมราษฎร์ ประชาชื่น</option>
                        <option value="63">โรงพยาบาลวิชัยเวช อินเตอร์เนชั่นแนล หนองแขม</option>
                        <option value="64">โรงพยาบาลเจ้าพระยา</option>
                        <option value="65">โรงพยาบาลซีจีเอช</option>
                        <option value="66">โรงพยาบาลซีจีเอช สายไหม</option>
                        <option value="67">โรงพยาบาลเซนต์หลุยส์</option>
                        <option value="68">โรงพยาบาลเทพธารินทร์</option>
                        <option value="69">โรงพยาบาลไทยนครินทร์</option>
                        <option value="70">โรงพยาบาลธนบุรี 1</option>
                        <option value="71">โรงพยาบาลธนบุรี 2</option>
                        <option value="72">โรงพยาบาลนครธน</option>
                        <option value="73">โรงพยาบาลนวมินทร์ 9</option>
                        <option value="74">โรงพยาบาลนวมินทร์</option>
                        <option value="75">โรงพยาบาลบางนา 1</option>
                        <option value="76">โรงพยาบาลบางปะกอก 1</option>
                        <option value="77">โรงพยาบาลบางปะกอก 8</option>
                        <option value="78">โรงพยาบาลบางไผ่</option>
                        <option value="79">โรงพยาบาลบางโพ</option>
                        <option value="80">โรงพยาบาลบางมด</option>
                        <option value="81">โรงพยาบาลบํารุงราษฎร์</option>
                        <option value="82">โรงพยาบาลบี.แคร์ เมดิคอลเซ็นเตอร์</option>
                        <option value="83">โรงพยาบาลบีเอ็นเอช</option>
                        <option value="85">โรงพยาบาลประชาพัฒน์</option>
                        <option value="86">โรงพยาบาลเปาโล เกษตร</option>
                        <option value="87">โรงพยาบาลเปาโล เมโมเรียล</option>
                        <option value="88">โรงพยาบาลเปาโล เมโมเรียล โชคชัย4</option>
                        <option value="89">โรงพยาบาลพญาไท 1</option>
                        <option value="90">โรงพยาบาลพญาไท 2</option>
                        <option value="91">โรงพยาบาลพญาไท 3</option>
                        <option value="92">โรงพยาบาลพญาไท นวมินทร์</option>
                        <option value="93">โรงพยาบาลพระรามเก้า</option>
                        <option value="94">โรงพยาบาลพีเอ็มจี</option>
                        <option value="95">โรงพยาบาลเพชรเวช</option>
                        <option value="96">โรงพยาบาลแพทย์ปัญญา</option>
                        <option value="97">โรงพยาบาลมงกุฎวัฒนะ</option>
                        <option value="98">โรงพยาบาลมเหสักข์</option>
                        <option value="99">โรงพยาบาลมิชชั่น</option>
                        <option value="100">โรงพยาบาลมิตรประชา</option>
                        <option value="101">โรงพยาบาลเมดพาร์ค</option>
                        <option value="102">โรงพยาบาลรามคำแหง</option>
                        <option value="103">โรงพยาบาลราษฏร์บูรณะ</option>
                        <option value="104">โรงพยาบาลลาดพร้าว</option>
                        <option value="105">โรงพยาบาลวิชัยยุทธ</option>
                        <option value="106">โรงพยาบาลวิชัยยุทธ</option>
                        <option value="107">โรงพยาบาลวิชัยเวช แยกไฟฉาย</option>
                        <option value="108">โรงพยาบาลวิภาราม</option>
                        <option value="109">โรงพยาบาลวิภาวดี</option>
                        <option value="110">โรงพยาบาลเวชธานี</option>
                        <option value="111">โรงพยาบาลศิครินทร์</option>
                        <option value="112">โรงพยาบาลศิริราชปิยมหาราชการุณย์</option>
                        <option value="113">โรงพยาบาลสมิติเวช ไชน่าทาวน์</option>
                        <option value="114">โรงพยาบาลสมิติเวช ธนบุรี (กรุงธน)</option>
                        <option value="115">โรงพยาบาลสมิติเวช ศรีนครินทร์</option>
                        <option value="116">โรงพยาบาลสมิติเวช สุขุมวิท</option>
                        <option value="117">โรงพยาบาลสหวิทยาการมะลิ</option>
                        <option value="118">โรงพยาบาลสินแพทย์</option>
                        <option value="119">โรงพยาบาลสินแพทย์ ศรีนครินทร์</option>
                        <option value="120">โรงพยาบาลสุขุมวิท</option>
                        <option value="121">โรงพยาบาลหัวเฉียว</option>
                        <option value="122">โรงพยาบาลอินทรารัตน์</option>
                        <option value="123">Kamol Cosmetic Hospital</option>
                        <option value="124">โรงพยาบาลกล้วยน้ำไท 1</option>
                        <option value="125">โรงพยาบาลจุฬารัตน์ 7</option>
                        <option value="126">โรงพยาบาลสุขสวัสดิ์</option>
                        <option value="127">โรงพยาบาลพรมงคลเทพมุนี</option>
                    </select>



                    <label for="filter-zone">เขตที่รับแจ้งเหตุ:</label>
                    <select id="filter-zone-list" class="filter-select">
                        <option value="" selected hidden>กรุณาเลือกเขต</option>
                        <option value="ทั้งหมด" selected>ทั้งหมด</option>
                        <option value="พระนคร">พระนคร</option>
                        <option value="ดุสิต">ดุสิต</option>
                        <option value="หนองจอก">หนองจอก</option>
                        <option value="บางรัก">บางรัก</option>
                        <option value="บางเขน">บางเขน</option>
                        <option value="บางกะปิ">บางกะปิ</option>
                        <option value="ปทุมวัน">ปทุมวัน</option>
                        <option value="ป้อมปราบศัตรูพ่าย">ป้อมปราบศัตรูพ่าย</option>
                        <option value="พระโขนง">พระโขนง</option>
                        <option value="มีนบุรี">มีนบุรี</option>
                        <option value="ลาดกระบัง">ลาดกระบัง</option>
                        <option value="ยานนาวา">ยานนาวา</option>
                        <option value="สัมพันธวงศ์">สัมพันธวงศ์</option>
                        <option value="พญาไท">พญาไท</option>
                        <option value="ธนบุรี">ธนบุรี</option>
                        <option value="บางกอกใหญ่">บางกอกใหญ่</option>
                        <option value="ห้วยขวาง">ห้วยขวาง</option>
                        <option value="คลองสาน">คลองสาน</option>
                        <option value="ตลิ่งชัน">ตลิ่งชัน</option>
                        <option value="บางกอกน้อย">บางกอกน้อย</option>
                        <option value="บางขุนเทียน">บางขุนเทียน</option>
                        <option value="ภาษีเจริญ">ภาษีเจริญ</option>
                        <option value="หนองแขม">หนองแขม</option>
                        <option value="ราษฎร์บูรณะ">ราษฎร์บูรณะ</option>
                        <option value="บางพลัด">บางพลัด</option>
                        <option value="ดินแดง">ดินแดง</option>
                        <option value="บึงกุ่ม">บึงกุ่ม</option>
                        <option value="สาทร">สาทร</option>
                        <option value="บางซื่อ">บางซื่อ</option>
                        <option value="จตุจักร">จตุจักร</option>
                        <option value="บางคอแหลม">บางคอแหลม</option>
                        <option value="ประเวศ">ประเวศ</option>
                        <option value="คลองเตย">คลองเตย</option>
                        <option value="สวนหลวง">สวนหลวง</option>
                        <option value="จอมทอง">จอมทอง</option>
                        <option value="ดอนเมือง">ดอนเมือง</option>
                        <option value="ราชเทวี">ราชเทวี</option>
                        <option value="ลาดพร้าว">ลาดพร้าว</option>
                        <option value="วัฒนา">วัฒนา</option>
                        <option value="บางแค">บางแค</option>
                        <option value="หลักสี่">หลักสี่</option>
                        <option value="สายไหม">สายไหม</option>
                        <option value="คันนายาว">คันนายาว</option>
                        <option value="สะพานสูง">สะพานสูง</option>
                        <option value="วังทองหลาง">วังทองหลาง</option>
                        <option value="คลองสามวา">คลองสามวา</option>
                        <option value="บางนา">บางนา</option>
                        <option value="ทวีวัฒนา">ทวีวัฒนา</option>
                        <option value="บางบอน">บางบอน</option>
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
        // // สคริปต์สำหรับแสดงค่าของ Slider
        // const slider = document.getElementById("priceRange");
        // const value = document.getElementById("value");

        // const updateValue = () => {
        //     value.textContent = slider.value;
        // }
        // slider.oninput = updateValue;
        // updateValue();
        // ตั้งค่าปฏิทิน Flatpickr
        flatpickr("#calendarSelect", {
            dateFormat: "Y-m-d", // รูปแบบวันที่เป็น YYYY-MM-DD
            onChange: function(selectedDates, dateStr, instance) {
                // เมื่อผู้ใช้เลือกวันที่, เรียกใช้งานฟังก์ชัน updateChart
                updateChart(dateStr);
            }
        });
    </script>
</body>

</html>
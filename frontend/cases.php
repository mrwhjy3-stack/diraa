<?php

$apiBase = "http://127.0.0.1:8000";


function apiRequest($endpoint, $method = "GET")
{
    global $apiBase;

    $url = $apiBase . $endpoint;

    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_CUSTOMREQUEST => $method
    ]);

    $response = curl_exec($curl);

    if (curl_errno($curl)) {
        curl_close($curl);
        return null;
    }

    $statusCode = curl_getinfo(
        $curl,
        CURLINFO_HTTP_CODE
    );

    curl_close($curl);

    if ($statusCode < 200 || $statusCode >= 300) {
        return null;
    }

    return json_decode($response, true);
}


$message = "";
$messageType = "";


$prefilledAccount =
    trim($_GET["account"] ?? "");


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action =
        $_POST["action"] ?? "";


    if ($action === "create") {

        $accountId =
            trim($_POST["account_id"] ?? "");

        $title =
            trim($_POST["title"] ?? "");

        $description =
            trim($_POST["description"] ?? "");

        $priority =
            trim($_POST["priority"] ?? "متوسط");


        $allowedPriorities = [
            "منخفض",
            "متوسط",
            "مرتفع",
            "حرج"
        ];


        if (
            $accountId !== "" &&
            $title !== "" &&
            in_array(
                $priority,
                $allowedPriorities,
                true
            )
        ) {

            $endpoint =
                "/api/cases" .
                "?account_id=" .
                urlencode($accountId) .
                "&title=" .
                urlencode($title) .
                "&description=" .
                urlencode($description) .
                "&priority=" .
                urlencode($priority);


            $result = apiRequest(
                $endpoint,
                "POST"
            );


            if ($result) {

                $message =
                    "تم فتح التحقيق بنجاح.";

                $messageType = "success";

            } else {

                $message =
                    "تعذر فتح التحقيق. تأكد أن الحساب موجود.";

                $messageType = "error";
            }

        } else {

            $message =
                "تأكد من إدخال الحساب والعنوان والأولوية بشكل صحيح.";

            $messageType = "error";
        }
    }


    if ($action === "update") {

        $caseId =
            (int) ($_POST["case_id"] ?? 0);

        $status =
            trim($_POST["status"] ?? "");


        $allowedStatuses = [
            "مفتوح",
            "قيد التحقيق",
            "مغلق"
        ];


        if (
            $caseId > 0 &&
            in_array(
                $status,
                $allowedStatuses,
                true
            )
        ) {

            $result = apiRequest(
                "/api/cases/" .
                $caseId .
                "?status=" .
                urlencode($status),
                "PUT"
            );


            if ($result) {

                $message =
                    "تم تحديث حالة التحقيق.";

                $messageType = "success";

            } else {

                $message =
                    "تعذر تحديث التحقيق.";

                $messageType = "error";
            }

        } else {

            $message =
                "بيانات التحقيق غير صحيحة.";

            $messageType = "error";
        }
    }
}


$casesData =
    apiRequest("/api/cases");

$dashboardData =
    apiRequest("/api/dashboard");


$cases =
    $casesData["cases"] ?? [];


$totalCases =
    $dashboardData["database"]["total_cases"]
    ?? count($cases);


$openCases =
    $dashboardData["database"]["open_cases"]
    ?? 0;


$inProgressCases = 0;
$closedCases = 0;
$criticalCases = 0;


foreach ($cases as $case) {

    $status =
        $case["status"] ?? "";

    $priority =
        $case["priority"] ?? "";


    if ($status === "قيد التحقيق") {
        $inProgressCases++;
    }


    if ($status === "مغلق") {
        $closedCases++;
    }


    if ($priority === "حرج") {
        $criticalCases++;
    }
}


$statusFilter =
    $_GET["status"] ?? "all";


$filteredCases = array_filter(
    $cases,
    function ($case) use ($statusFilter) {

        if ($statusFilter === "all") {
            return true;
        }

        return
            ($case["status"] ?? "")
            === $statusFilter;
    }
);


$filteredCases =
    array_values($filteredCases);

?>

<!DOCTYPE html>

<html lang="ar" dir="rtl">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        دِرع | التحقيقات
    </title>

    <link
        rel="stylesheet"
        href="style.css"
    >

</head>


<body>


<div class="app">


    <aside class="sidebar">


        <div class="logo">

            <h2>
                دِرع
            </h2>

            <span>
                كشف الاحتيال المالي
            </span>

        </div>


        <nav class="nav-menu">


            <a
                href="index.php"
                class="nav-link"
            >
                لوحة التحكم
            </a>


            <a
                href="analysis.php"
                class="nav-link"
            >
                تحليل الاحتيال
            </a>


            <a
                href="network.php"
                class="nav-link"
            >
                شبكة الحسابات
            </a>


            <a
                href="alerts.php"
                class="nav-link"
            >
                التنبيهات
            </a>


            <a
                href="account.php"
                class="nav-link"
            >
                تفاصيل الحساب
            </a>


            <a
                href="cases.php"
                class="nav-link active"
            >
                التحقيقات
            </a>


            <a
                href="prediction.php"
                class="nav-link"
            >
                التنبؤ بالحركة القادمة
            </a>


            <a
                href="simulation.php"
                class="nav-link"
            >
                المحاكاة
            </a>


        </nav>


    </aside>



    <main class="main-content">


        <header class="topbar">


            <div>

                <h1>
                    التحقيقات
                </h1>

                <p>
                    إدارة حالات الاحتيال ومتابعة الحسابات المشبوهة
                </p>

            </div>


            <div class="system-status">

                <span class="status-dot"></span>

                <?php if ($casesData): ?>

                    نظام التحقيقات يعمل

                <?php else: ?>

                    تعذر الاتصال بالنظام

                <?php endif; ?>

            </div>


        </header>



        <?php if ($message !== ""): ?>


            <section
                class="message-box <?= htmlspecialchars($messageType) ?>"
            >

                <?= htmlspecialchars($message) ?>

            </section>


        <?php endif; ?>



        <section class="stats-grid">


            <div class="stat-card">

                <p>
                    إجمالي التحقيقات
                </p>

                <h2>
                    <?= number_format($totalCases) ?>
                </h2>

            </div>


            <div class="stat-card">

                <p>
                    تحقيقات مفتوحة
                </p>

                <h2>
                    <?= number_format($openCases) ?>
                </h2>

            </div>


            <div class="stat-card">

                <p>
                    قيد التحقيق
                </p>

                <h2>
                    <?= number_format($inProgressCases) ?>
                </h2>

            </div>


            <div class="stat-card">

                <p>
                    أولوية حرجة
                </p>

                <h2>
                    <?= number_format($criticalCases) ?>
                </h2>

            </div>


        </section>



        <section class="panel">


            <div class="panel-header">


                <div>

                    <h2>
                        فتح تحقيق جديد
                    </h2>

                    <p>
                        أنشئ Case لحساب يحتاج إلى مراجعة
                    </p>

                </div>


            </div>



            <form
                method="POST"
                action="cases.php"
                class="case-form"
            >


                <input
                    type="hidden"
                    name="action"
                    value="create"
                >


                <div class="form-group">

                    <label>
                        رقم الحساب
                    </label>

                    <input
                        type="text"
                        name="account_id"
                        placeholder="مثال: ACC-1001"
                        value="<?= htmlspecialchars($prefilledAccount) ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        عنوان التحقيق
                    </label>

                    <input
                        type="text"
                        name="title"
                        placeholder="مثال: تحويلات مالية غير طبيعية"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        الأولوية
                    </label>

                    <select name="priority">

                        <option value="منخفض">
                            منخفض
                        </option>

                        <option
                            value="متوسط"
                            selected
                        >
                            متوسط
                        </option>

                        <option value="مرتفع">
                            مرتفع
                        </option>

                        <option value="حرج">
                            حرج
                        </option>

                    </select>

                </div>


                <div class="form-group">

                    <label>
                        وصف التحقيق
                    </label>

                    <textarea
                        name="description"
                        rows="5"
                        placeholder="اكتب ملاحظات المحلل أو سبب فتح التحقيق..."
                    ></textarea>

                </div>


                <button
                    type="submit"
                    class="primary-button"
                >
                    فتح التحقيق
                </button>


            </form>


        </section>



        <section class="panel">


            <div class="panel-header">


                <div>

                    <h2>
                        تصفية التحقيقات
                    </h2>

                    <p>
                        اعرض التحقيقات حسب حالتها
                    </p>

                </div>


            </div>


            <form
                method="GET"
                action="cases.php"
                class="analysis-filters"
            >


                <select name="status">


                    <option
                        value="all"
                        <?= $statusFilter === "all" ? "selected" : "" ?>
                    >
                        جميع التحقيقات
                    </option>


                    <option
                        value="مفتوح"
                        <?= $statusFilter === "مفتوح" ? "selected" : "" ?>
                    >
                        مفتوح
                    </option>


                    <option
                        value="قيد التحقيق"
                        <?= $statusFilter === "قيد التحقيق" ? "selected" : "" ?>
                    >
                        قيد التحقيق
                    </option>


                    <option
                        value="مغلق"
                        <?= $statusFilter === "مغلق" ? "selected" : "" ?>
                    >
                        مغلق
                    </option>


                </select>


                <button type="submit">
                    تطبيق الفلتر
                </button>


                <a
                    href="cases.php"
                    class="secondary-button"
                >
                    عرض الكل
                </a>


            </form>


        </section>



        <section class="panel">


            <div class="panel-header">


                <div>

                    <h2>
                        قائمة التحقيقات
                    </h2>

                    <p>
                        الحالات المفتوحة والمسجلة في النظام
                    </p>

                </div>


                <span>

                    <?= number_format(
                        count($filteredCases)
                    ) ?>

                    تحقيق

                </span>


            </div>



            <div class="table-container">


                <table>


                    <thead>

                        <tr>

                            <th>
                                رقم التحقيق
                            </th>

                            <th>
                                الحساب
                            </th>

                            <th>
                                العنوان
                            </th>

                            <th>
                                الوصف
                            </th>

                            <th>
                                الأولوية
                            </th>

                            <th>
                                الحالة
                            </th>

                            <th>
                                التاريخ
                            </th>

                            <th>
                                الإجراء
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php if (empty($filteredCases)): ?>


                        <tr>

                            <td colspan="8">

                                لا توجد تحقيقات حتى الآن.

                            </td>

                        </tr>


                    <?php else: ?>


                        <?php foreach ($filteredCases as $case): ?>


                            <tr>


                                <td>

                                    #<?= htmlspecialchars(
                                        $case["id"]
                                    ) ?>

                                </td>


                                <td>

                                    <a
                                        href="account.php?account=<?= urlencode(
                                            $case["account_id"]
                                        ) ?>"
                                    >

                                        <?= htmlspecialchars(
                                            $case["account_id"]
                                        ) ?>

                                    </a>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $case["title"]
                                    ) ?>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $case["description"] ?? ""
                                    ) ?>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $case["priority"]
                                    ) ?>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $case["status"]
                                    ) ?>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $case["created_at"]
                                    ) ?>

                                </td>


                                <td>


                                    <form
                                        method="POST"
                                        action="cases.php"
                                        class="inline-form"
                                    >


                                        <input
                                            type="hidden"
                                            name="action"
                                            value="update"
                                        >


                                        <input
                                            type="hidden"
                                            name="case_id"
                                            value="<?= htmlspecialchars(
                                                $case["id"]
                                            ) ?>"
                                        >


                                        <select name="status">


                                            <option
                                                value="مفتوح"
                                                <?= $case["status"] === "مفتوح" ? "selected" : "" ?>
                                            >
                                                مفتوح
                                            </option>


                                            <option
                                                value="قيد التحقيق"
                                                <?= $case["status"] === "قيد التحقيق" ? "selected" : "" ?>
                                            >
                                                قيد التحقيق
                                            </option>


                                            <option
                                                value="مغلق"
                                                <?= $case["status"] === "مغلق" ? "selected" : "" ?>
                                            >
                                                مغلق
                                            </option>


                                        </select>


                                        <button type="submit">
                                            تحديث
                                        </button>


                                    </form>


                                </td>


                            </tr>


                        <?php endforeach; ?>


                    <?php endif; ?>


                    </tbody>


                </table>


            </div>


        </section>



        <section class="panel">


            <div class="panel-header">

                <div>

                    <h2>
                        حالة التحقيقات
                    </h2>

                    <p>
                        ملخص سير التحقيقات الحالية
                    </p>

                </div>

            </div>


            <div class="network-summary">


                <div class="summary-item">

                    <span>
                        مفتوح
                    </span>

                    <strong>
                        <?= number_format($openCases) ?>
                    </strong>

                </div>


                <div class="summary-item">

                    <span>
                        قيد التحقيق
                    </span>

                    <strong>
                        <?= number_format($inProgressCases) ?>
                    </strong>

                </div>


                <div class="summary-item">

                    <span>
                        مغلق
                    </span>

                    <strong>
                        <?= number_format($closedCases) ?>
                    </strong>

                </div>


            </div>


        </section>


    </main>


</div>


</body>

</html>
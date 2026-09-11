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


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST["action"] ?? "";


    if ($action === "generate") {

        $result = apiRequest(
            "/api/alerts/generate",
            "POST"
        );

        if ($result) {

            $created = $result["created"] ?? 0;

            $message =
                "تم إنشاء " .
                $created .
                " تنبيه بنجاح.";

            $messageType = "success";

        } else {

            $message =
                "تعذر إنشاء التنبيهات.";

            $messageType = "error";
        }
    }


    if ($action === "update") {

        $alertId =
            (int) ($_POST["alert_id"] ?? 0);

        $status =
            trim($_POST["status"] ?? "");


        $allowedStatuses = [
            "جديد",
            "قيد المراجعة",
            "مغلق"
        ];


        if (
            $alertId > 0 &&
            in_array(
                $status,
                $allowedStatuses,
                true
            )
        ) {

            $result = apiRequest(
                "/api/alerts/" .
                $alertId .
                "?status=" .
                urlencode($status),
                "PUT"
            );


            if ($result) {

                $message =
                    "تم تحديث حالة التنبيه.";

                $messageType = "success";

            } else {

                $message =
                    "تعذر تحديث التنبيه.";

                $messageType = "error";
            }

        } else {

            $message =
                "بيانات التنبيه غير صحيحة.";

            $messageType = "error";
        }
    }
}


$alertsData = apiRequest("/api/alerts");
$dashboardData = apiRequest("/api/dashboard");


$alerts =
    $alertsData["alerts"] ?? [];


$totalAlerts =
    $dashboardData["database"]["total_alerts"]
    ?? count($alerts);


$newAlerts =
    $dashboardData["database"]["new_alerts"]
    ?? 0;


$reviewAlerts = 0;
$closedAlerts = 0;
$criticalAlerts = 0;


foreach ($alerts as $alert) {

    $status =
        $alert["status"] ?? "";

    $riskScore =
        $alert["risk_score"] ?? 0;


    if ($status === "قيد المراجعة") {
        $reviewAlerts++;
    }


    if ($status === "مغلق") {
        $closedAlerts++;
    }


    if ($riskScore >= 85) {
        $criticalAlerts++;
    }
}


$statusFilter =
    $_GET["status"] ?? "all";


$filteredAlerts = array_filter(
    $alerts,
    function ($alert) use ($statusFilter) {

        if ($statusFilter === "all") {
            return true;
        }

        return
            ($alert["status"] ?? "")
            === $statusFilter;
    }
);


$filteredAlerts =
    array_values($filteredAlerts);

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
        دِرع | التنبيهات
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
                class="nav-link active"
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
                class="nav-link"
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
                    مركز التنبيهات
                </h1>

                <p>
                    متابعة الأنشطة والحسابات عالية الخطورة
                </p>

            </div>


            <div class="system-status">

                <span class="status-dot"></span>

                <?php if ($alertsData): ?>

                    نظام التنبيهات يعمل

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
                    إجمالي التنبيهات
                </p>

                <h2>
                    <?= number_format($totalAlerts) ?>
                </h2>

            </div>


            <div class="stat-card">

                <p>
                    تنبيهات جديدة
                </p>

                <h2>
                    <?= number_format($newAlerts) ?>
                </h2>

            </div>


            <div class="stat-card">

                <p>
                    قيد المراجعة
                </p>

                <h2>
                    <?= number_format($reviewAlerts) ?>
                </h2>

            </div>


            <div class="stat-card">

                <p>
                    تنبيهات حرجة
                </p>

                <h2>
                    <?= number_format($criticalAlerts) ?>
                </h2>

            </div>


        </section>



        <section class="panel">


            <div class="panel-header">


                <div>

                    <h2>
                        إدارة التنبيهات
                    </h2>

                    <p>
                        إنشاء تنبيهات للحسابات عالية الخطورة
                    </p>

                </div>


                <form
                    method="POST"
                    action="alerts.php"
                >

                    <input
                        type="hidden"
                        name="action"
                        value="generate"
                    >

                    <button
                        type="submit"
                        class="primary-button"
                    >
                        توليد التنبيهات
                    </button>

                </form>


            </div>


            <form
                method="GET"
                action="alerts.php"
                class="analysis-filters"
            >


                <select name="status">


                    <option
                        value="all"
                        <?= $statusFilter === "all" ? "selected" : "" ?>
                    >
                        جميع التنبيهات
                    </option>


                    <option
                        value="جديد"
                        <?= $statusFilter === "جديد" ? "selected" : "" ?>
                    >
                        جديد
                    </option>


                    <option
                        value="قيد المراجعة"
                        <?= $statusFilter === "قيد المراجعة" ? "selected" : "" ?>
                    >
                        قيد المراجعة
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
                    href="alerts.php"
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
                        قائمة التنبيهات
                    </h2>

                    <p>
                        الحسابات التي تحتاج إلى مراجعة
                    </p>

                </div>


                <span>

                    <?= number_format(
                        count($filteredAlerts)
                    ) ?>

                    تنبيه

                </span>


            </div>



            <div class="table-container">


                <table>


                    <thead>

                        <tr>

                            <th>
                                رقم التنبيه
                            </th>

                            <th>
                                الحساب
                            </th>

                            <th>
                                Risk Score
                            </th>

                            <th>
                                المستوى
                            </th>

                            <th>
                                سبب التنبيه
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


                    <?php if (empty($filteredAlerts)): ?>


                        <tr>

                            <td colspan="8">

                                لا توجد تنبيهات حتى الآن.

                            </td>

                        </tr>


                    <?php else: ?>


                        <?php foreach ($filteredAlerts as $alert): ?>


                            <tr>


                                <td>

                                    #<?= htmlspecialchars(
                                        $alert["id"]
                                    ) ?>

                                </td>


                                <td>

                                    <a
                                        href="account.php?account=<?= urlencode(
                                            $alert["account_id"]
                                        ) ?>"
                                    >

                                        <?= htmlspecialchars(
                                            $alert["account_id"]
                                        ) ?>

                                    </a>

                                </td>


                                <td>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $alert["risk_score"]
                                        ) ?>%

                                    </strong>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $alert["risk_level"]
                                    ) ?>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $alert["message"]
                                    ) ?>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $alert["status"]
                                    ) ?>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $alert["created_at"]
                                    ) ?>

                                </td>


                                <td>


                                    <form
                                        method="POST"
                                        action="alerts.php"
                                        class="inline-form"
                                    >


                                        <input
                                            type="hidden"
                                            name="action"
                                            value="update"
                                        >


                                        <input
                                            type="hidden"
                                            name="alert_id"
                                            value="<?= htmlspecialchars(
                                                $alert["id"]
                                            ) ?>"
                                        >


                                        <select name="status">


                                            <option
                                                value="جديد"
                                                <?= $alert["status"] === "جديد" ? "selected" : "" ?>
                                            >
                                                جديد
                                            </option>


                                            <option
                                                value="قيد المراجعة"
                                                <?= $alert["status"] === "قيد المراجعة" ? "selected" : "" ?>
                                            >
                                                قيد المراجعة
                                            </option>


                                            <option
                                                value="مغلق"
                                                <?= $alert["status"] === "مغلق" ? "selected" : "" ?>
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
                        حالة معالجة التنبيهات
                    </h2>

                    <p>
                        ملخص سير العمل الحالي
                    </p>

                </div>

            </div>


            <div class="network-summary">


                <div class="summary-item">

                    <span>
                        جديد
                    </span>

                    <strong>
                        <?= number_format($newAlerts) ?>
                    </strong>

                </div>


                <div class="summary-item">

                    <span>
                        قيد المراجعة
                    </span>

                    <strong>
                        <?= number_format($reviewAlerts) ?>
                    </strong>

                </div>


                <div class="summary-item">

                    <span>
                        مغلق
                    </span>

                    <strong>
                        <?= number_format($closedAlerts) ?>
                    </strong>

                </div>


            </div>


        </section>


    </main>


</div>


</body>

</html>
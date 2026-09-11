<?php

$apiBase = "https://diraa.onrender.com";


function apiGet($endpoint)
{
    global $apiBase;

    $url = $apiBase . $endpoint;

    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5
    ]);

    $response = curl_exec($curl);

    if (curl_errno($curl)) {
        curl_close($curl);
        return null;
    }

    $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    curl_close($curl);

    if ($statusCode !== 200) {
        return null;
    }

    return json_decode($response, true);
}


$dashboard = apiGet("/api/dashboard");
$highRisk = apiGet("/api/high-risk?minimum_score=65");
$alertsData = apiGet("/api/alerts");


$totalAccounts = 0;
$totalConnections = 0;
$totalTransactions = 0;
$totalAmount = 0;

$suspiciousAccounts = 0;
$highestRisk = 0;
$newAlerts = 0;


if ($dashboard) {

    $totalAccounts =
        $dashboard["network"]["total_accounts"] ?? 0;

    $totalConnections =
        $dashboard["network"]["total_connections"] ?? 0;

    $totalTransactions =
        $dashboard["network"]["total_transactions"] ?? 0;

    $totalAmount =
        $dashboard["network"]["total_amount"] ?? 0;

    $suspiciousAccounts =
        $dashboard["fraud"]["total_suspicious"] ?? 0;

    $highestRisk =
        $dashboard["fraud"]["highest_risk"] ?? 0;

    $newAlerts =
        $dashboard["database"]["new_alerts"] ?? 0;
}


$highRiskAccounts =
    $highRisk["accounts"] ?? [];


$alerts =
    $alertsData["alerts"] ?? [];

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
        دِرع | لوحة التحكم
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
                class="nav-link active"
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
                    لوحة التحكم
                </h1>

                <p>
                    مراقبة وتحليل شبكات الاحتيال المالي
                </p>

            </div>


            <div class="system-status">

                <span class="status-dot"></span>

                <?php if ($dashboard): ?>

                    النظام يعمل

                <?php else: ?>

                    تعذر الاتصال بالنظام

                <?php endif; ?>

            </div>


        </header>



        <section class="search-section">


            <form
                action="account.php"
                method="GET"
            >

                <input
                    type="text"
                    name="account"
                    placeholder="ابحث عن حساب مثل ACC-1001"
                    required
                >


                <button type="submit">

                    بحث عن الحساب

                </button>

            </form>


        </section>



        <section class="stats-grid">


            <div class="stat-card">

                <p>
                    إجمالي الحسابات
                </p>

                <h2>
                    <?= number_format($totalAccounts) ?>
                </h2>

            </div>



            <div class="stat-card">

                <p>
                    الحسابات المشبوهة
                </p>

                <h2>
                    <?= number_format($suspiciousAccounts) ?>
                </h2>

            </div>



            <div class="stat-card">

                <p>
                    أعلى درجة خطورة
                </p>

                <h2>
                    <?= htmlspecialchars($highestRisk) ?>%
                </h2>

            </div>



            <div class="stat-card">

                <p>
                    التنبيهات الجديدة
                </p>

                <h2>
                    <?= number_format($newAlerts) ?>
                </h2>

            </div>


        </section>



        <section class="dashboard-grid">


            <div class="panel">


                <div class="panel-header">

                    <div>

                        <h2>
                            أعلى الحسابات خطورة
                        </h2>

                        <p>
                            الحسابات التي تحتاج إلى مراجعة
                        </p>

                    </div>


                    <a href="analysis.php">
                        عرض الكل
                    </a>

                </div>



                <div class="table-container">


                    <table>


                        <thead>

                            <tr>

                                <th>
                                    الحساب
                                </th>

                                <th>
                                    درجة الخطورة
                                </th>

                                <th>
                                    المستوى
                                </th>

                                <th>
                                    التفاصيل
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php if (empty($highRiskAccounts)): ?>


                            <tr>

                                <td colspan="4">

                                    لا توجد بيانات لعرضها.

                                </td>

                            </tr>


                        <?php else: ?>


                            <?php foreach (
                                array_slice($highRiskAccounts, 0, 5)
                                as $account
                            ): ?>


                                <tr>


                                    <td>

                                        <?= htmlspecialchars(
                                            $account["account"]
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= htmlspecialchars(
                                            $account["risk_score"]
                                        ) ?>%

                                    </td>


                                    <td>

                                        <?= htmlspecialchars(
                                            $account["risk_level"]
                                        ) ?>

                                    </td>


                                    <td>

                                        <a
                                            href="account.php?account=<?= urlencode(
                                                $account["account"]
                                            ) ?>"
                                        >

                                            فتح

                                        </a>

                                    </td>


                                </tr>


                            <?php endforeach; ?>


                        <?php endif; ?>


                        </tbody>


                    </table>


                </div>


            </div>



            <div class="panel">


                <div class="panel-header">

                    <div>

                        <h2>
                            ملخص الشبكة المالية
                        </h2>

                        <p>
                            إحصائيات حركة الأموال
                        </p>

                    </div>

                </div>



                <div class="network-summary">


                    <div class="summary-item">

                        <span>
                            الروابط بين الحسابات
                        </span>

                        <strong>

                            <?= number_format(
                                $totalConnections
                            ) ?>

                        </strong>

                    </div>



                    <div class="summary-item">

                        <span>
                            عدد التحويلات
                        </span>

                        <strong>

                            <?= number_format(
                                $totalTransactions
                            ) ?>

                        </strong>

                    </div>



                    <div class="summary-item">

                        <span>
                            إجمالي قيمة التحويلات
                        </span>

                        <strong>

                            <?= number_format(
                                $totalAmount,
                                2
                            ) ?>

                            ريال

                        </strong>

                    </div>


                </div>



                <a
                    href="network.php"
                    class="primary-button"
                >

                    فتح شبكة الحسابات

                </a>


            </div>


        </section>



        <section class="panel">


            <div class="panel-header">


                <div>

                    <h2>
                        آخر التنبيهات
                    </h2>

                    <p>
                        آخر الأنشطة التي اكتشفها النظام
                    </p>

                </div>


                <a href="alerts.php">

                    عرض التنبيهات

                </a>


            </div>



            <div class="alerts-list">


                <?php if (empty($alerts)): ?>


                    <div class="empty-state">

                        لا توجد تنبيهات حتى الآن.

                    </div>


                <?php else: ?>


                    <?php foreach (
                        array_slice($alerts, 0, 5)
                        as $alert
                    ): ?>


                        <div class="alert-item">


                            <div>

                                <strong>

                                    <?= htmlspecialchars(
                                        $alert["account_id"]
                                    ) ?>

                                </strong>


                                <p>

                                    <?= htmlspecialchars(
                                        $alert["message"]
                                    ) ?>

                                </p>

                            </div>


                            <span>

                                <?= htmlspecialchars(
                                    $alert["risk_score"]
                                ) ?>%

                            </span>


                        </div>


                    <?php endforeach; ?>


                <?php endif; ?>


            </div>


        </section>


    </main>


</div>


</body>

</html>
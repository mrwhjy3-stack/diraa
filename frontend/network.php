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


$networkData = apiGet("/api/network");
$dashboardData = apiGet("/api/dashboard");


$nodes = $networkData["nodes"] ?? [];
$edges = $networkData["edges"] ?? [];


$totalAccounts =
    $dashboardData["network"]["total_accounts"] ?? count($nodes);

$totalConnections =
    $dashboardData["network"]["total_connections"] ?? count($edges);

$totalTransactions =
    $dashboardData["network"]["total_transactions"] ?? 0;

$totalAmount =
    $dashboardData["network"]["total_amount"] ?? 0;


$highRiskNodes = 0;

foreach ($nodes as $node) {

    if (($node["risk_score"] ?? 0) >= 65) {
        $highRiskNodes++;
    }
}


$displayNodes = array_slice($nodes, 0, 50);
$displayEdges = array_slice($edges, 0, 100);

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
        دِرع | شبكة الحسابات
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
                class="nav-link active"
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
                    شبكة الحسابات
                </h1>

                <p>
                    عرض الحسابات والعلاقات والتحويلات المالية بينها
                </p>

            </div>


            <div class="system-status">

                <span class="status-dot"></span>

                <?php if ($networkData): ?>

                    بيانات الشبكة متصلة

                <?php else: ?>

                    تعذر تحميل الشبكة

                <?php endif; ?>

            </div>


        </header>



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
                    العلاقات بين الحسابات
                </p>

                <h2>
                    <?= number_format($totalConnections) ?>
                </h2>

            </div>


            <div class="stat-card">

                <p>
                    الحسابات عالية الخطورة
                </p>

                <h2>
                    <?= number_format($highRiskNodes) ?>
                </h2>

            </div>


            <div class="stat-card">

                <p>
                    عدد التحويلات
                </p>

                <h2>
                    <?= number_format($totalTransactions) ?>
                </h2>

            </div>


        </section>



        <section class="panel">


            <div class="panel-header">


                <div>

                    <h2>
                        استكشاف شبكة حساب
                    </h2>

                    <p>
                        أدخل رقم الحساب لعرض شبكته المباشرة
                    </p>

                </div>


            </div>



            <form
                action="account.php"
                method="GET"
                class="analysis-filters"
            >


                <input
                    type="text"
                    name="account"
                    placeholder="مثال: ACC-1001"
                    required
                >


                <button type="submit">

                    فتح الحساب

                </button>


            </form>


        </section>



        <section class="panel">


            <div class="panel-header">


                <div>

                    <h2>
                        العرض المرئي للشبكة
                    </h2>

                    <p>
                        الحسابات كـ Nodes والتحويلات كـ Edges
                    </p>

                </div>


            </div>



            <div
                id="networkGraph"
                class="network-graph"
            >

                <?php if (!$networkData): ?>


                    <div class="empty-state">

                        تعذر تحميل بيانات الشبكة من FastAPI.

                    </div>


                <?php else: ?>


                    <div class="empty-state">

                        سيتم رسم الشبكة التفاعلية هنا عند إضافة JavaScript.

                    </div>


                <?php endif; ?>


            </div>


        </section>



        <section class="dashboard-grid">


            <div class="panel">


                <div class="panel-header">

                    <div>

                        <h2>
                            الحسابات في الشبكة
                        </h2>

                        <p>
                            أول 50 حساب للعرض
                        </p>

                    </div>

                </div>



                <div class="table-container">


                    <table>


                        <thead>

                            <tr>

                                <th>
                                    الحساب
                                </th>

                                <th>
                                    Risk Score
                                </th>

                                <th>
                                    مستوى الخطورة
                                </th>

                                <th>
                                    الإجراء
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php if (empty($displayNodes)): ?>


                            <tr>

                                <td colspan="4">

                                    لا توجد حسابات.

                                </td>

                            </tr>


                        <?php else: ?>


                            <?php foreach ($displayNodes as $node): ?>


                                <tr>


                                    <td>

                                        <?= htmlspecialchars(
                                            $node["id"]
                                        ) ?>

                                    </td>


                                    <td>

                                        <?= htmlspecialchars(
                                            $node["risk_score"] ?? 0
                                        ) ?>%

                                    </td>


                                    <td>

                                        <?= htmlspecialchars(
                                            $node["risk_level"] ?? "طبيعي"
                                        ) ?>

                                    </td>


                                    <td>

                                        <a
                                            href="account.php?account=<?= urlencode(
                                                $node["id"]
                                            ) ?>"
                                        >

                                            التفاصيل

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
                            ملخص حركة الأموال
                        </h2>

                        <p>
                            إحصائيات الشبكة الكاملة
                        </p>

                    </div>

                </div>



                <div class="network-summary">


                    <div class="summary-item">

                        <span>
                            الحسابات
                        </span>

                        <strong>
                            <?= number_format($totalAccounts) ?>
                        </strong>

                    </div>


                    <div class="summary-item">

                        <span>
                            العلاقات
                        </span>

                        <strong>
                            <?= number_format($totalConnections) ?>
                        </strong>

                    </div>


                    <div class="summary-item">

                        <span>
                            التحويلات
                        </span>

                        <strong>
                            <?= number_format($totalTransactions) ?>
                        </strong>

                    </div>


                    <div class="summary-item">

                        <span>
                            إجمالي الأموال
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


            </div>


        </section>



        <section class="panel">


            <div class="panel-header">

                <div>

                    <h2>
                        آخر العلاقات في الشبكة
                    </h2>

                    <p>
                        عينة من التحويلات بين الحسابات
                    </p>

                </div>

            </div>



            <div class="table-container">


                <table>


                    <thead>

                        <tr>

                            <th>
                                من الحساب
                            </th>

                            <th>
                                إلى الحساب
                            </th>

                            <th>
                                إجمالي المبلغ
                            </th>

                            <th>
                                عدد التحويلات
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php if (empty($displayEdges)): ?>


                        <tr>

                            <td colspan="4">

                                لا توجد علاقات لعرضها.

                            </td>

                        </tr>


                    <?php else: ?>


                        <?php foreach ($displayEdges as $edge): ?>


                            <tr>


                                <td>

                                    <?= htmlspecialchars(
                                        $edge["source"]
                                    ) ?>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $edge["target"]
                                    ) ?>

                                </td>


                                <td>

                                    <?= number_format(
                                        $edge["amount"] ?? 0,
                                        2
                                    ) ?>

                                    ريال

                                </td>


                                <td>

                                    <?= number_format(
                                        $edge["count"] ?? 0
                                    ) ?>

                                </td>


                            </tr>


                        <?php endforeach; ?>


                    <?php endif; ?>


                    </tbody>


                </table>


            </div>


        </section>


    </main>


</div>


<script>

    const networkNodes =
        <?= json_encode(
            $nodes,
            JSON_UNESCAPED_UNICODE
        ) ?>;

    const networkEdges =
        <?= json_encode(
            $edges,
            JSON_UNESCAPED_UNICODE
        ) ?>;

</script>


</body>

</html>
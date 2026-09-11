<?php

$apiBase = "http://127.0.0.1:8000";


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


$analysisData = apiGet("/api/analyze");
$dashboardData = apiGet("/api/dashboard");


$accounts = $analysisData["accounts"] ?? [];


$search = $_GET["search"] ?? "";
$riskLevel = $_GET["risk"] ?? "all";


$filteredAccounts = array_filter(
    $accounts,
    function ($account) use ($search, $riskLevel) {

        $matchesSearch = true;
        $matchesRisk = true;

        if ($search !== "") {
            $matchesSearch = stripos(
                $account["account"],
                $search
            ) !== false;
        }

        if ($riskLevel !== "all") {
            $matchesRisk =
                $account["risk_level"] === $riskLevel;
        }

        return $matchesSearch && $matchesRisk;
    }
);


$filteredAccounts = array_values($filteredAccounts);


$totalSuspicious = count($accounts);

$criticalAccounts = 0;
$highAccounts = 0;
$mediumAccounts = 0;
$lowAccounts = 0;

$highestRisk = 0;


foreach ($accounts as $account) {

    $score = $account["risk_score"] ?? 0;
    $level = $account["risk_level"] ?? "";

    if ($score > $highestRisk) {
        $highestRisk = $score;
    }

    if ($level === "حرج") {
        $criticalAccounts++;
    }

    elseif ($level === "مرتفع") {
        $highAccounts++;
    }

    elseif ($level === "متوسط") {
        $mediumAccounts++;
    }

    elseif ($level === "منخفض") {
        $lowAccounts++;
    }
}

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
        دِرع | تحليل الاحتيال
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
                class="nav-link active"
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
                    تحليل الاحتيال
                </h1>

                <p>
                    تحليل الحسابات واكتشاف الأنماط المالية المشبوهة
                </p>

            </div>


            <div class="system-status">

                <span class="status-dot"></span>

                <?php if ($analysisData): ?>

                    محرك التحليل يعمل

                <?php else: ?>

                    تعذر الاتصال بمحرك التحليل

                <?php endif; ?>

            </div>


        </header>



        <section class="stats-grid">


            <div class="stat-card">

                <p>
                    إجمالي الحسابات المشبوهة
                </p>

                <h2>
                    <?= number_format($totalSuspicious) ?>
                </h2>

            </div>


            <div class="stat-card">

                <p>
                    خطورة حرجة
                </p>

                <h2>
                    <?= number_format($criticalAccounts) ?>
                </h2>

            </div>


            <div class="stat-card">

                <p>
                    خطورة مرتفعة
                </p>

                <h2>
                    <?= number_format($highAccounts) ?>
                </h2>

            </div>


            <div class="stat-card">

                <p>
                    أعلى Risk Score
                </p>

                <h2>
                    <?= htmlspecialchars($highestRisk) ?>%
                </h2>

            </div>


        </section>



        <section class="panel">


            <div class="panel-header">


                <div>

                    <h2>
                        فلترة نتائج التحليل
                    </h2>

                    <p>
                        ابحث عن حساب أو اعرض مستوى خطورة محدد
                    </p>

                </div>


            </div>



            <form
                method="GET"
                action="analysis.php"
                class="analysis-filters"
            >


                <input
                    type="text"
                    name="search"
                    placeholder="ابحث عن حساب..."
                    value="<?= htmlspecialchars($search) ?>"
                >


                <select name="risk">


                    <option
                        value="all"
                        <?= $riskLevel === "all" ? "selected" : "" ?>
                    >
                        جميع المستويات
                    </option>


                    <option
                        value="حرج"
                        <?= $riskLevel === "حرج" ? "selected" : "" ?>
                    >
                        حرج
                    </option>


                    <option
                        value="مرتفع"
                        <?= $riskLevel === "مرتفع" ? "selected" : "" ?>
                    >
                        مرتفع
                    </option>


                    <option
                        value="متوسط"
                        <?= $riskLevel === "متوسط" ? "selected" : "" ?>
                    >
                        متوسط
                    </option>


                    <option
                        value="منخفض"
                        <?= $riskLevel === "منخفض" ? "selected" : "" ?>
                    >
                        منخفض
                    </option>


                </select>


                <button type="submit">

                    تطبيق الفلتر

                </button>


                <a
                    href="analysis.php"
                    class="secondary-button"
                >
                    إعادة تعيين
                </a>


            </form>


        </section>



        <section class="panel">


            <div class="panel-header">


                <div>

                    <h2>
                        الحسابات المشبوهة
                    </h2>

                    <p>
                        مرتبة من أعلى درجة خطورة إلى الأقل
                    </p>

                </div>


                <span>

                    <?= number_format(
                        count($filteredAccounts)
                    ) ?>

                    نتيجة

                </span>


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
                                المستوى
                            </th>

                            <th>
                                الحسابات الواردة
                            </th>

                            <th>
                                الحسابات الصادرة
                            </th>

                            <th>
                                قيمة الأموال الواردة
                            </th>

                            <th>
                                الأسباب
                            </th>

                            <th>
                                الإجراء
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php if (empty($filteredAccounts)): ?>


                        <tr>

                            <td colspan="8">

                                لا توجد نتائج مطابقة.

                            </td>

                        </tr>


                    <?php else: ?>


                        <?php foreach ($filteredAccounts as $account): ?>


                            <tr>


                                <td>

                                    <?= htmlspecialchars(
                                        $account["account"]
                                    ) ?>

                                </td>


                                <td>

                                    <strong>

                                        <?= htmlspecialchars(
                                            $account["risk_score"]
                                        ) ?>%

                                    </strong>

                                </td>


                                <td>

                                    <?= htmlspecialchars(
                                        $account["risk_level"]
                                    ) ?>

                                </td>


                                <td>

                                    <?= number_format(
                                        $account["incoming_accounts"] ?? 0
                                    ) ?>

                                </td>


                                <td>

                                    <?= number_format(
                                        $account["outgoing_accounts"] ?? 0
                                    ) ?>

                                </td>


                                <td>

                                    <?= number_format(
                                        $account["incoming_amount"] ?? 0,
                                        2
                                    ) ?>

                                    ريال

                                </td>


                                <td>

                                    <?php

                                    $reasons =
                                        $account["reasons"] ?? [];

                                    if (empty($reasons)) {

                                        echo "لا توجد أسباب";

                                    } else {

                                        echo htmlspecialchars(
                                            implode(
                                                "، ",
                                                array_slice(
                                                    $reasons,
                                                    0,
                                                    2
                                                )
                                            )
                                        );

                                    }

                                    ?>

                                </td>


                                <td>

                                    <a
                                        href="account.php?account=<?= urlencode(
                                            $account["account"]
                                        ) ?>"
                                    >

                                        عرض التفاصيل

                                    </a>

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
                        توزيع مستويات الخطورة
                    </h2>

                    <p>
                        ملخص سريع للحسابات المشبوهة
                    </p>

                </div>


            </div>



            <div class="risk-summary">


                <div class="summary-item">

                    <span>
                        حرج
                    </span>

                    <strong>
                        <?= $criticalAccounts ?>
                    </strong>

                </div>


                <div class="summary-item">

                    <span>
                        مرتفع
                    </span>

                    <strong>
                        <?= $highAccounts ?>
                    </strong>

                </div>


                <div class="summary-item">

                    <span>
                        متوسط
                    </span>

                    <strong>
                        <?= $mediumAccounts ?>
                    </strong>

                </div>


                <div class="summary-item">

                    <span>
                        منخفض
                    </span>

                    <strong>
                        <?= $lowAccounts ?>
                    </strong>

                </div>


            </div>


        </section>


    </main>


</div>


</body>

</html>
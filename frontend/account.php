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

    $statusCode = curl_getinfo(
        $curl,
        CURLINFO_HTTP_CODE
    );

    curl_close($curl);

    if ($statusCode !== 200) {
        return null;
    }

    return json_decode($response, true);
}


$accountId = trim(
    $_GET["account"] ?? ""
);


$accountData = null;
$errorMessage = "";


if ($accountId !== "") {

    $accountData = apiGet(
        "/api/account/" . urlencode($accountId)
    );

    if (!$accountData) {
        $errorMessage =
            "الحساب غير موجود أو تعذر الاتصال بالنظام.";
    }
}


$risk = $accountData["risk"] ?? [];
$connections = $accountData["connections"] ?? [];
$prediction = $accountData["prediction"] ?? [];


$riskScore =
    $risk["risk_score"] ?? 0;

$riskLevel =
    $risk["risk_level"] ?? "طبيعي";

$reasons =
    $risk["reasons"] ?? [];

$incomingAccounts =
    $connections["incoming"] ?? [];

$outgoingAccounts =
    $connections["outgoing"] ?? [];

$predictedAccount =
    $prediction["prediction"] ?? null;

$predictionConfidence =
    $prediction["confidence"] ?? 0;

$predictionCandidates =
    $prediction["candidates"] ?? [];

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
        دِرع | تفاصيل الحساب
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
                class="nav-link active"
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
                    تفاصيل الحساب
                </h1>

                <p>
                    تحليل حساب مالي وعرض درجة الخطورة والعلاقات
                </p>

            </div>


            <div class="system-status">

                <span class="status-dot"></span>

                تحليل الحساب

            </div>


        </header>



        <section class="panel">


            <div class="panel-header">

                <div>

                    <h2>
                        البحث عن حساب
                    </h2>

                    <p>
                        أدخل رقم الحساب لعرض التحليل الكامل
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
                    value="<?= htmlspecialchars($accountId) ?>"
                    required
                >


                <button type="submit">
                    تحليل الحساب
                </button>


            </form>


        </section>



        <?php if ($errorMessage !== ""): ?>


            <section class="panel">

                <div class="empty-state">

                    <?= htmlspecialchars($errorMessage) ?>

                </div>

            </section>


        <?php endif; ?>



        <?php if ($accountData): ?>


            <section class="stats-grid">


                <div class="stat-card">

                    <p>
                        الحساب
                    </p>

                    <h2>
                        <?= htmlspecialchars($accountId) ?>
                    </h2>

                </div>


                <div class="stat-card">

                    <p>
                        Risk Score
                    </p>

                    <h2>
                        <?= htmlspecialchars($riskScore) ?>%
                    </h2>

                </div>


                <div class="stat-card">

                    <p>
                        مستوى الخطورة
                    </p>

                    <h2>
                        <?= htmlspecialchars($riskLevel) ?>
                    </h2>

                </div>


                <div class="stat-card">

                    <p>
                        إجمالي العلاقات
                    </p>

                    <h2>

                        <?= number_format(
                            $connections["total_connections"] ?? 0
                        ) ?>

                    </h2>

                </div>


            </section>



            <section class="dashboard-grid">


                <div class="panel">


                    <div class="panel-header">

                        <div>

                            <h2>
                                الحركة المالية
                            </h2>

                            <p>
                                ملخص الأموال الداخلة والخارجة
                            </p>

                        </div>

                    </div>


                    <div class="network-summary">


                        <div class="summary-item">

                            <span>
                                قيمة الأموال الواردة
                            </span>

                            <strong>

                                <?= number_format(
                                    $risk["incoming_amount"] ?? 0,
                                    2
                                ) ?>

                                ريال

                            </strong>

                        </div>


                        <div class="summary-item">

                            <span>
                                قيمة الأموال الصادرة
                            </span>

                            <strong>

                                <?= number_format(
                                    $risk["outgoing_amount"] ?? 0,
                                    2
                                ) ?>

                                ريال

                            </strong>

                        </div>


                        <div class="summary-item">

                            <span>
                                التحويلات الواردة
                            </span>

                            <strong>

                                <?= number_format(
                                    $risk["incoming_transactions"] ?? 0
                                ) ?>

                            </strong>

                        </div>


                        <div class="summary-item">

                            <span>
                                التحويلات الصادرة
                            </span>

                            <strong>

                                <?= number_format(
                                    $risk["outgoing_transactions"] ?? 0
                                ) ?>

                            </strong>

                        </div>


                        <div class="summary-item">

                            <span>
                                نسبة الأموال المحولة
                            </span>

                            <strong>

                                <?= htmlspecialchars(
                                    $risk["outgoing_ratio"] ?? 0
                                ) ?>%

                            </strong>

                        </div>


                    </div>


                </div>



                <div class="panel">


                    <div class="panel-header">

                        <div>

                            <h2>
                                أسباب الاشتباه
                            </h2>

                            <p>
                                لماذا أعطى النظام الحساب هذه الدرجة؟
                            </p>

                        </div>

                    </div>


                    <div class="reasons-list">


                        <?php if (empty($reasons)): ?>


                            <div class="empty-state">

                                لم يكتشف النظام أسباب اشتباه واضحة.

                            </div>


                        <?php else: ?>


                            <?php foreach ($reasons as $reason): ?>


                                <div class="reason-item">

                                    <?= htmlspecialchars($reason) ?>

                                </div>


                            <?php endforeach; ?>


                        <?php endif; ?>


                    </div>


                </div>


            </section>



            <section class="dashboard-grid">


                <div class="panel">


                    <div class="panel-header">

                        <div>

                            <h2>
                                الحسابات المرسلة
                            </h2>

                            <p>
                                الحسابات التي أرسلت أموالًا لهذا الحساب
                            </p>

                        </div>

                    </div>


                    <div class="accounts-list">


                        <?php if (empty($incomingAccounts)): ?>


                            <div class="empty-state">
                                لا توجد حسابات واردة.
                            </div>


                        <?php else: ?>


                            <?php foreach ($incomingAccounts as $account): ?>


                                <a
                                    class="account-link"
                                    href="account.php?account=<?= urlencode($account) ?>"
                                >

                                    <?= htmlspecialchars($account) ?>

                                </a>


                            <?php endforeach; ?>


                        <?php endif; ?>


                    </div>


                </div>



                <div class="panel">


                    <div class="panel-header">

                        <div>

                            <h2>
                                الحسابات المستقبلة
                            </h2>

                            <p>
                                الحسابات التي استقبلت أموالًا منه
                            </p>

                        </div>

                    </div>


                    <div class="accounts-list">


                        <?php if (empty($outgoingAccounts)): ?>


                            <div class="empty-state">
                                لا توجد حسابات صادرة.
                            </div>


                        <?php else: ?>


                            <?php foreach ($outgoingAccounts as $account): ?>


                                <a
                                    class="account-link"
                                    href="account.php?account=<?= urlencode($account) ?>"
                                >

                                    <?= htmlspecialchars($account) ?>

                                </a>


                            <?php endforeach; ?>


                        <?php endif; ?>


                    </div>


                </div>


            </section>



            <section class="panel">


                <div class="panel-header">

                    <div>

                        <h2>
                            التنبؤ بالحركة القادمة
                        </h2>

                        <p>
                            الوجهة الأكثر احتمالًا بناءً على نمط التحويلات
                        </p>

                    </div>


                    <a
                        href="prediction.php?account=<?= urlencode($accountId) ?>"
                    >
                        التحليل الكامل
                    </a>

                </div>



                <?php if ($predictedAccount): ?>


                    <div class="prediction-card">


                        <div>

                            <span>
                                الحساب الحالي
                            </span>

                            <strong>
                                <?= htmlspecialchars($accountId) ?>
                            </strong>

                        </div>


                        <div class="prediction-arrow">
                            ←
                        </div>


                        <div>

                            <span>
                                الحساب المتوقع
                            </span>

                            <strong>
                                <?= htmlspecialchars($predictedAccount) ?>
                            </strong>

                        </div>


                        <div>

                            <span>
                                نسبة الثقة
                            </span>

                            <strong>
                                <?= htmlspecialchars($predictionConfidence) ?>%
                            </strong>

                        </div>


                    </div>


                <?php else: ?>


                    <div class="empty-state">

                        لا توجد تحويلات صادرة كافية لإنشاء توقع.

                    </div>


                <?php endif; ?>


            </section>



            <?php if (!empty($predictionCandidates)): ?>


                <section class="panel">


                    <div class="panel-header">

                        <div>

                            <h2>
                                أقوى الوجهات المحتملة
                            </h2>

                            <p>
                                أفضل ثلاثة حسابات مرشحة
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
                                        قيمة التحويلات
                                    </th>

                                    <th>
                                        عدد التحويلات
                                    </th>

                                    <th>
                                        نسبة الثقة
                                    </th>

                                </tr>

                            </thead>


                            <tbody>


                                <?php foreach ($predictionCandidates as $candidate): ?>


                                    <tr>


                                        <td>

                                            <?= htmlspecialchars(
                                                $candidate["account"]
                                            ) ?>

                                        </td>


                                        <td>

                                            <?= number_format(
                                                $candidate["amount"] ?? 0,
                                                2
                                            ) ?>

                                            ريال

                                        </td>


                                        <td>

                                            <?= number_format(
                                                $candidate["count"] ?? 0
                                            ) ?>

                                        </td>


                                        <td>

                                            <?= htmlspecialchars(
                                                $candidate["confidence"] ?? 0
                                            ) ?>%

                                        </td>


                                    </tr>


                                <?php endforeach; ?>


                            </tbody>


                        </table>


                    </div>


                </section>


            <?php endif; ?>



            <section class="panel">


                <div class="panel-header">

                    <div>

                        <h2>
                            إجراءات التحقيق
                        </h2>

                        <p>
                            انتقل إلى الأدوات المرتبطة بهذا الحساب
                        </p>

                    </div>

                </div>


                <div class="action-buttons">


                    <a
                        href="network.php?account=<?= urlencode($accountId) ?>"
                        class="primary-button"
                    >
                        عرض الشبكة
                    </a>


                    <a
                        href="prediction.php?account=<?= urlencode($accountId) ?>"
                        class="secondary-button"
                    >
                        عرض التنبؤ
                    </a>


                    <a
                        href="cases.php?account=<?= urlencode($accountId) ?>"
                        class="secondary-button"
                    >
                        فتح تحقيق
                    </a>


                </div>


            </section>


        <?php elseif ($accountId === ""): ?>


            <section class="panel">

                <div class="empty-state">

                    ابحث عن حساب بالأعلى لعرض تفاصيله.

                </div>

            </section>


        <?php endif; ?>


    </main>


</div>


</body>

</html>
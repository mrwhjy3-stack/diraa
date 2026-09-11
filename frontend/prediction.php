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
        CURLOPT_TIMEOUT => 10
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


$accountId =
    trim($_GET["account"] ?? "");


$predictionData = null;
$errorMessage = "";


if ($accountId !== "") {

    $predictionData = apiGet(
        "/api/predict/" . urlencode($accountId)
    );

    if (!$predictionData) {
        $errorMessage =
            "الحساب غير موجود أو تعذر إنشاء التوقع.";
    }
}


$nextHop =
    $predictionData["next_hop"] ?? [];

$pathData =
    $predictionData["path"] ?? [];

$topPredictions =
    $predictionData["top_predictions"]["predictions"]
    ?? [];

$explanation =
    $predictionData["explanation"] ?? [];


$predictedAccount =
    $nextHop["prediction"] ?? null;

$confidence =
    $nextHop["confidence"] ?? 0;

$predictedPath =
    $pathData["predicted_path"] ?? [];

$pathSteps =
    $pathData["steps"] ?? [];

$explanationText =
    $explanation["explanation"]
    ?? "";

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
        دِرع | التنبؤ بالحركة القادمة
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
                class="nav-link"
            >
                التحقيقات
            </a>


            <a
                href="prediction.php"
                class="nav-link active"
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
                    التنبؤ بالحركة القادمة
                </h1>

                <p>
                    توقع الوجهة التالية للأموال بناءً على نمط التحويلات
                </p>

            </div>


            <div class="system-status">

                <span class="status-dot"></span>

                محرك التنبؤ

            </div>


        </header>



        <section class="panel">


            <div class="panel-header">

                <div>

                    <h2>
                        اختر حسابًا
                    </h2>

                    <p>
                        أدخل الحساب المراد تحليل حركته القادمة
                    </p>

                </div>

            </div>


            <form
                method="GET"
                action="prediction.php"
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
                    إنشاء التوقع
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



        <?php if ($predictionData): ?>


            <section class="stats-grid">


                <div class="stat-card">

                    <p>
                        الحساب الحالي
                    </p>

                    <h2>
                        <?= htmlspecialchars($accountId) ?>
                    </h2>

                </div>


                <div class="stat-card">

                    <p>
                        الحساب المتوقع
                    </p>

                    <h2>

                        <?php if ($predictedAccount): ?>

                            <?= htmlspecialchars($predictedAccount) ?>

                        <?php else: ?>

                            لا يوجد

                        <?php endif; ?>

                    </h2>

                </div>


                <div class="stat-card">

                    <p>
                        نسبة الثقة
                    </p>

                    <h2>
                        <?= htmlspecialchars($confidence) ?>%
                    </h2>

                </div>


                <div class="stat-card">

                    <p>
                        عدد خطوات المسار
                    </p>

                    <h2>
                        <?= number_format(count($pathSteps)) ?>
                    </h2>

                </div>


            </section>



            <section class="panel">


                <div class="panel-header">

                    <div>

                        <h2>
                            التوقع الرئيسي
                        </h2>

                        <p>
                            الوجهة الأقوى المتوقعة للأموال
                        </p>

                    </div>

                </div>


                <?php if ($predictedAccount): ?>


                    <div class="prediction-card">


                        <div>

                            <span>
                                من
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
                                إلى
                            </span>

                            <strong>
                                <?= htmlspecialchars($predictedAccount) ?>
                            </strong>

                        </div>


                        <div>

                            <span>
                                الثقة
                            </span>

                            <strong>
                                <?= htmlspecialchars($confidence) ?>%
                            </strong>

                        </div>


                    </div>


                <?php else: ?>


                    <div class="empty-state">

                        لا توجد تحويلات صادرة كافية لإنشاء توقع.

                    </div>


                <?php endif; ?>


            </section>



            <section class="panel">


                <div class="panel-header">

                    <div>

                        <h2>
                            تفسير التوقع
                        </h2>

                        <p>
                            لماذا اختار النظام هذه الوجهة؟
                        </p>

                    </div>

                </div>


                <div class="explanation-box">

                    <?php if ($explanationText !== ""): ?>

                        <?= htmlspecialchars($explanationText) ?>

                    <?php else: ?>

                        لا يوجد تفسير متاح.

                    <?php endif; ?>

                </div>


            </section>



            <section class="panel">


                <div class="panel-header">

                    <div>

                        <h2>
                            أفضل 3 وجهات متوقعة
                        </h2>

                        <p>
                            ترتيب الحسابات المرشحة حسب قوة التوقع
                        </p>

                    </div>

                </div>


                <div class="table-container">


                    <table>


                        <thead>

                            <tr>

                                <th>
                                    الترتيب
                                </th>

                                <th>
                                    الحساب
                                </th>

                                <th>
                                    قيمة التحويلات السابقة
                                </th>

                                <th>
                                    عدد التحويلات
                                </th>

                                <th>
                                    نسبة الثقة
                                </th>

                                <th>
                                    التفاصيل
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php if (empty($topPredictions)): ?>


                            <tr>

                                <td colspan="6">

                                    لا توجد توقعات متاحة.

                                </td>

                            </tr>


                        <?php else: ?>


                            <?php foreach ($topPredictions as $index => $candidate): ?>


                                <tr>


                                    <td>

                                        <?= $index + 1 ?>

                                    </td>


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

                                        <strong>

                                            <?= htmlspecialchars(
                                                $candidate["confidence"] ?? 0
                                            ) ?>%

                                        </strong>

                                    </td>


                                    <td>

                                        <a
                                            href="account.php?account=<?= urlencode(
                                                $candidate["account"]
                                            ) ?>"
                                        >
                                            فتح الحساب
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
                            المسار المتوقع للأموال
                        </h2>

                        <p>
                            تسلسل الحسابات المتوقع أن تمر بها الأموال
                        </p>

                    </div>

                </div>


                <?php if (count($predictedPath) > 1): ?>


                    <div class="predicted-path">


                        <?php foreach ($predictedPath as $index => $pathAccount): ?>


                            <a
                                href="account.php?account=<?= urlencode($pathAccount) ?>"
                                class="path-account"
                            >

                                <?= htmlspecialchars($pathAccount) ?>

                            </a>


                            <?php if ($index < count($predictedPath) - 1): ?>

                                <span class="path-arrow">
                                    ←
                                </span>

                            <?php endif; ?>


                        <?php endforeach; ?>


                    </div>


                <?php else: ?>


                    <div class="empty-state">

                        لا يوجد مسار متوقع متاح.

                    </div>


                <?php endif; ?>


            </section>



            <?php if (!empty($pathSteps)): ?>


                <section class="panel">


                    <div class="panel-header">

                        <div>

                            <h2>
                                تفاصيل خطوات التوقع
                            </h2>

                            <p>
                                نسبة الثقة في كل انتقال متوقع
                            </p>

                        </div>

                    </div>


                    <div class="table-container">


                        <table>


                            <thead>

                                <tr>

                                    <th>
                                        الخطوة
                                    </th>

                                    <th>
                                        من
                                    </th>

                                    <th>
                                        إلى
                                    </th>

                                    <th>
                                        نسبة الثقة
                                    </th>

                                </tr>

                            </thead>


                            <tbody>


                                <?php foreach ($pathSteps as $index => $step): ?>


                                    <tr>


                                        <td>
                                            <?= $index + 1 ?>
                                        </td>


                                        <td>

                                            <?= htmlspecialchars(
                                                $step["from"]
                                            ) ?>

                                        </td>


                                        <td>

                                            <?= htmlspecialchars(
                                                $step["to"]
                                            ) ?>

                                        </td>


                                        <td>

                                            <?= htmlspecialchars(
                                                $step["confidence"]
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
                            إجراءات إضافية
                        </h2>

                        <p>
                            انتقل إلى تحليل الحساب أو الشبكة
                        </p>

                    </div>

                </div>


                <div class="action-buttons">


                    <a
                        href="account.php?account=<?= urlencode($accountId) ?>"
                        class="primary-button"
                    >
                        تفاصيل الحساب
                    </a>


                    <a
                        href="network.php?account=<?= urlencode($accountId) ?>"
                        class="secondary-button"
                    >
                        عرض الشبكة
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

                    أدخل رقم حساب بالأعلى لإنشاء توقع.

                </div>

            </section>


        <?php endif; ?>


    </main>


</div>


</body>

</html>
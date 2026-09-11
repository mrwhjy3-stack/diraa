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


function getRiskLevel($score)
{
    if ($score >= 85) {
        return "حرج";
    }

    if ($score >= 65) {
        return "مرتفع";
    }

    if ($score >= 40) {
        return "متوسط";
    }

    if ($score > 0) {
        return "منخفض";
    }

    return "طبيعي";
}


$fromAccount =
    trim($_POST["from_account"] ?? "");

$toAccount =
    trim($_POST["to_account"] ?? "");

$amount =
    (float) ($_POST["amount"] ?? 0);


$simulationResult = null;
$errorMessage = "";


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (
        $fromAccount === "" ||
        $toAccount === "" ||
        $amount <= 0
    ) {

        $errorMessage =
            "أدخل الحساب المرسل والمستقبل والمبلغ بشكل صحيح.";

    } elseif ($fromAccount === $toAccount) {

        $errorMessage =
            "الحساب المرسل والمستقبل لا يمكن أن يكونا نفس الحساب.";

    } else {

        $senderData = apiGet(
            "/api/account/" . urlencode($fromAccount)
        );

        $receiverData = apiGet(
            "/api/account/" . urlencode($toAccount)
        );


        if (!$senderData) {

            $errorMessage =
                "الحساب المرسل غير موجود.";

        } elseif (!$receiverData) {

            $errorMessage =
                "الحساب المستقبل غير موجود.";

        } else {

            $senderRisk =
                $senderData["risk"] ?? [];

            $receiverRisk =
                $receiverData["risk"] ?? [];


            $senderCurrentScore =
                $senderRisk["risk_score"] ?? 0;

            $receiverCurrentScore =
                $receiverRisk["risk_score"] ?? 0;


            $simulationScore = 0;
            $reasons = [];


            if ($amount >= 100000) {

                $simulationScore += 35;

                $reasons[] =
                    "قيمة التحويل الافتراضي مرتفعة جدًا.";

            } elseif ($amount >= 50000) {

                $simulationScore += 25;

                $reasons[] =
                    "قيمة التحويل الافتراضي مرتفعة.";

            } elseif ($amount >= 20000) {

                $simulationScore += 15;

                $reasons[] =
                    "قيمة التحويل أكبر من المعتاد نسبيًا.";

            } elseif ($amount >= 5000) {

                $simulationScore += 5;

                $reasons[] =
                    "قيمة التحويل تحتاج إلى مراجعة.";
            }


            if ($senderCurrentScore >= 85) {

                $simulationScore += 30;

                $reasons[] =
                    "الحساب المرسل مصنف حاليًا بخطورة حرجة.";

            } elseif ($senderCurrentScore >= 65) {

                $simulationScore += 20;

                $reasons[] =
                    "الحساب المرسل مصنف عالي الخطورة.";

            } elseif ($senderCurrentScore >= 40) {

                $simulationScore += 10;

                $reasons[] =
                    "الحساب المرسل لديه مؤشرات خطورة سابقة.";
            }


            if ($receiverCurrentScore >= 85) {

                $simulationScore += 25;

                $reasons[] =
                    "الحساب المستقبل مصنف حاليًا بخطورة حرجة.";

            } elseif ($receiverCurrentScore >= 65) {

                $simulationScore += 18;

                $reasons[] =
                    "الحساب المستقبل عالي الخطورة.";

            } elseif ($receiverCurrentScore >= 40) {

                $simulationScore += 8;

                $reasons[] =
                    "الحساب المستقبل لديه مؤشرات خطورة سابقة.";
            }


            $senderOutgoingAmount =
                $senderRisk["outgoing_amount"] ?? 0;


            if (
                $senderOutgoingAmount > 0 &&
                $amount >= $senderOutgoingAmount
            ) {

                $simulationScore += 10;

                $reasons[] =
                    "قيمة التحويل الافتراضي كبيرة مقارنة بحركة الحساب الصادرة السابقة.";
            }


            $simulationScore =
                min($simulationScore, 100);


            if (empty($reasons)) {

                $reasons[] =
                    "لم تظهر مؤشرات خطورة قوية في هذا السيناريو.";
            }


            $simulationResult = [
                "from_account" => $fromAccount,
                "to_account" => $toAccount,
                "amount" => $amount,
                "sender_score" => $senderCurrentScore,
                "receiver_score" => $receiverCurrentScore,
                "simulation_score" => $simulationScore,
                "simulation_level" => getRiskLevel(
                    $simulationScore
                ),
                "reasons" => $reasons
            ];
        }
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
        دِرع | المحاكاة
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
                class="nav-link"
            >
                التنبؤ بالحركة القادمة
            </a>


            <a
                href="simulation.php"
                class="nav-link active"
            >
                المحاكاة
            </a>


        </nav>


    </aside>



    <main class="main-content">


        <header class="topbar">


            <div>

                <h1>
                    المحاكاة
                </h1>

                <p>
                    اختبر سيناريو تحويل افتراضي قبل تنفيذه
                </p>

            </div>


            <div class="system-status">

                <span class="status-dot"></span>

                وضع المحاكاة

            </div>


        </header>



        <section class="panel">


            <div class="panel-header">

                <div>

                    <h2>
                        What-if Simulation
                    </h2>

                    <p>
                        أدخل عملية تحويل افتراضية لتحليل مخاطرها
                    </p>

                </div>

            </div>



            <form
                method="POST"
                action="simulation.php"
                class="case-form"
            >


                <div class="form-group">

                    <label>
                        الحساب المرسل
                    </label>

                    <input
                        type="text"
                        name="from_account"
                        placeholder="مثال: ACC-1001"
                        value="<?= htmlspecialchars($fromAccount) ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        الحساب المستقبل
                    </label>

                    <input
                        type="text"
                        name="to_account"
                        placeholder="مثال: ACC-8291"
                        value="<?= htmlspecialchars($toAccount) ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        مبلغ التحويل الافتراضي
                    </label>

                    <input
                        type="number"
                        name="amount"
                        min="1"
                        step="0.01"
                        placeholder="مثال: 50000"
                        value="<?= $amount > 0 ? htmlspecialchars($amount) : "" ?>"
                        required
                    >

                </div>


                <button
                    type="submit"
                    class="primary-button"
                >
                    تشغيل المحاكاة
                </button>


            </form>


        </section>



        <section class="panel">


            <div class="panel-header">

                <div>

                    <h2>
                        ملاحظة
                    </h2>

                </div>

            </div>


            <div class="explanation-box">

                هذه المحاكاة لا تنفذ أي تحويل مالي حقيقي
                ولا تعدل بيانات المشروع.
                يتم فقط تحليل السيناريو الافتراضي.

            </div>


        </section>



        <?php if ($errorMessage !== ""): ?>


            <section class="panel">

                <div class="empty-state">

                    <?= htmlspecialchars($errorMessage) ?>

                </div>

            </section>


        <?php endif; ?>



        <?php if ($simulationResult): ?>


            <section class="stats-grid">


                <div class="stat-card">

                    <p>
                        Risk Score للمحاكاة
                    </p>

                    <h2>

                        <?= htmlspecialchars(
                            $simulationResult["simulation_score"]
                        ) ?>%

                    </h2>

                </div>


                <div class="stat-card">

                    <p>
                        مستوى الخطورة
                    </p>

                    <h2>

                        <?= htmlspecialchars(
                            $simulationResult["simulation_level"]
                        ) ?>

                    </h2>

                </div>


                <div class="stat-card">

                    <p>
                        خطورة المرسل الحالية
                    </p>

                    <h2>

                        <?= htmlspecialchars(
                            $simulationResult["sender_score"]
                        ) ?>%

                    </h2>

                </div>


                <div class="stat-card">

                    <p>
                        خطورة المستقبل الحالية
                    </p>

                    <h2>

                        <?= htmlspecialchars(
                            $simulationResult["receiver_score"]
                        ) ?>%

                    </h2>

                </div>


            </section>



            <section class="panel">


                <div class="panel-header">

                    <div>

                        <h2>
                            مسار التحويل الافتراضي
                        </h2>

                        <p>
                            العملية التي يتم اختبارها حاليًا
                        </p>

                    </div>

                </div>


                <div class="prediction-card">


                    <div>

                        <span>
                            المرسل
                        </span>

                        <strong>

                            <?= htmlspecialchars(
                                $simulationResult["from_account"]
                            ) ?>

                        </strong>

                    </div>


                    <div class="prediction-arrow">

                        ←

                    </div>


                    <div>

                        <span>
                            المستقبل
                        </span>

                        <strong>

                            <?= htmlspecialchars(
                                $simulationResult["to_account"]
                            ) ?>

                        </strong>

                    </div>


                    <div>

                        <span>
                            المبلغ
                        </span>

                        <strong>

                            <?= number_format(
                                $simulationResult["amount"],
                                2
                            ) ?>

                            ريال

                        </strong>

                    </div>


                </div>


            </section>



            <section class="panel">


                <div class="panel-header">

                    <div>

                        <h2>
                            أسباب تقييم المخاطر
                        </h2>

                        <p>
                            العوامل التي أثرت في نتيجة المحاكاة
                        </p>

                    </div>

                </div>


                <div class="reasons-list">


                    <?php foreach (
                        $simulationResult["reasons"]
                        as $reason
                    ): ?>


                        <div class="reason-item">

                            <?= htmlspecialchars($reason) ?>

                        </div>


                    <?php endforeach; ?>


                </div>


            </section>



            <section class="panel">


                <div class="panel-header">

                    <div>

                        <h2>
                            قرار النظام
                        </h2>

                        <p>
                            التوصية المبنية على نتيجة المحاكاة
                        </p>

                    </div>

                </div>


                <div class="simulation-decision">


                    <?php if (
                        $simulationResult["simulation_score"] >= 85
                    ): ?>

                        <h2>
                            إيقاف ومراجعة فورية
                        </h2>

                        <p>
                            السيناريو يحمل مستوى خطورة حرجًا.
                        </p>


                    <?php elseif (
                        $simulationResult["simulation_score"] >= 65
                    ): ?>

                        <h2>
                            يحتاج موافقة محلل
                        </h2>

                        <p>
                            السيناريو يحمل مستوى خطورة مرتفعًا.
                        </p>


                    <?php elseif (
                        $simulationResult["simulation_score"] >= 40
                    ): ?>

                        <h2>
                            مراجعة إضافية
                        </h2>

                        <p>
                            توجد مؤشرات تحتاج إلى التحقق.
                        </p>


                    <?php else: ?>

                        <h2>
                            مخاطر منخفضة
                        </h2>

                        <p>
                            لم يتم اكتشاف مؤشرات خطورة قوية.
                        </p>


                    <?php endif; ?>


                </div>


            </section>



            <section class="panel">


                <div class="panel-header">

                    <div>

                        <h2>
                            تحليل الحسابات
                        </h2>

                    </div>

                </div>


                <div class="action-buttons">


                    <a
                        href="account.php?account=<?= urlencode($fromAccount) ?>"
                        class="primary-button"
                    >
                        تحليل المرسل
                    </a>


                    <a
                        href="account.php?account=<?= urlencode($toAccount) ?>"
                        class="secondary-button"
                    >
                        تحليل المستقبل
                    </a>


                    <a
                        href="network.php"
                        class="secondary-button"
                    >
                        عرض الشبكة
                    </a>


                </div>


            </section>


        <?php endif; ?>


    </main>


</div>


</body>

</html>
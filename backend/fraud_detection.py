def get_account_totals(graph, account_id):
    incoming_accounts = list(graph.predecessors(account_id))
    outgoing_accounts = list(graph.successors(account_id))

    incoming_amount = 0
    outgoing_amount = 0

    incoming_transactions = 0
    outgoing_transactions = 0

    for sender in incoming_accounts:
        edge = graph[sender][account_id]

        incoming_amount += edge.get("total_amount", 0)
        incoming_transactions += edge.get("count", 0)

    for receiver in outgoing_accounts:
        edge = graph[account_id][receiver]

        outgoing_amount += edge.get("total_amount", 0)
        outgoing_transactions += edge.get("count", 0)

    return {
        "incoming_accounts": incoming_accounts,
        "outgoing_accounts": outgoing_accounts,
        "incoming_count": len(incoming_accounts),
        "outgoing_count": len(outgoing_accounts),
        "incoming_amount": round(incoming_amount, 2),
        "outgoing_amount": round(outgoing_amount, 2),
        "incoming_transactions": incoming_transactions,
        "outgoing_transactions": outgoing_transactions,
    }


def get_risk_level(score):
    if score >= 85:
        return "حرج"

    if score >= 65:
        return "مرتفع"

    if score >= 40:
        return "متوسط"

    if score > 0:
        return "منخفض"

    return "طبيعي"


def calculate_account_risk(graph, account_id):
    if account_id not in graph:
        return {
            "account": account_id,
            "risk_score": 0,
            "risk_level": "طبيعي",
            "reasons": [],
            "incoming_accounts": 0,
            "outgoing_accounts": 0,
            "incoming_amount": 0,
            "outgoing_amount": 0,
            "incoming_transactions": 0,
            "outgoing_transactions": 0,
        }

    totals = get_account_totals(graph, account_id)

    incoming_count = totals["incoming_count"]
    outgoing_count = totals["outgoing_count"]

    incoming_amount = totals["incoming_amount"]
    outgoing_amount = totals["outgoing_amount"]

    incoming_transactions = totals["incoming_transactions"]
    outgoing_transactions = totals["outgoing_transactions"]

    risk_score = 0
    reasons = []

    if incoming_count >= 12:
        risk_score += 25
        reasons.append(
            f"استقبل أموالًا من عدد كبير من الحسابات ({incoming_count} حساب)"
        )

    elif incoming_count >= 7:
        risk_score += 18
        reasons.append(
            f"استقبل أموالًا من عدة حسابات ({incoming_count} حسابات)"
        )

    elif incoming_count >= 4:
        risk_score += 10
        reasons.append(
            f"عدد الحسابات المرسلة مرتفع نسبيًا ({incoming_count})"
        )

    if outgoing_count >= 10:
        risk_score += 20
        reasons.append(
            f"وزّع الأموال على عدد كبير من الحسابات ({outgoing_count} حساب)"
        )

    elif outgoing_count >= 5:
        risk_score += 14
        reasons.append(
            f"أرسل الأموال إلى عدة حسابات ({outgoing_count} حسابات)"
        )

    elif outgoing_count >= 3:
        risk_score += 8
        reasons.append(
            f"تم توزيع الأموال على {outgoing_count} حسابات"
        )

    outgoing_ratio = 0

    if incoming_amount > 0:
        outgoing_ratio = outgoing_amount / incoming_amount

        if outgoing_ratio >= 0.95:
            risk_score += 25
            reasons.append(
                "تم تحويل أكثر من 95% من الأموال المستلمة"
            )

        elif outgoing_ratio >= 0.80:
            risk_score += 20
            reasons.append(
                "تم تحويل أغلب الأموال المستلمة"
            )

        elif outgoing_ratio >= 0.60:
            risk_score += 10
            reasons.append(
                "تم تحويل نسبة كبيرة من الأموال المستلمة"
            )

    if incoming_amount >= 150000:
        risk_score += 15
        reasons.append(
            "إجمالي الأموال المستلمة مرتفع جدًا"
        )

    elif incoming_amount >= 75000:
        risk_score += 10
        reasons.append(
            "إجمالي الأموال المستلمة مرتفع"
        )

    elif incoming_amount >= 30000:
        risk_score += 5
        reasons.append(
            "قيمة الأموال المستلمة تحتاج للمراجعة"
        )

    if incoming_count >= 5 and outgoing_count >= 5:
        risk_score += 15
        reasons.append(
            "ظهر نمط تجميع أموال ثم توزيعها على عدة حسابات"
        )

    elif incoming_count >= 5 and outgoing_count >= 1:
        risk_score += 8
        reasons.append(
            "ظهر نمط تجميع أموال ثم نقلها إلى حساب آخر"
        )

    if incoming_transactions >= 15:
        risk_score += 5
        reasons.append(
            "عدد التحويلات الواردة مرتفع"
        )

    if outgoing_transactions >= 10:
        risk_score += 5
        reasons.append(
            "عدد التحويلات الصادرة مرتفع"
        )

    risk_score = min(risk_score, 100)

    return {
        "account": account_id,
        "risk_score": risk_score,
        "risk_level": get_risk_level(risk_score),
        "reasons": reasons,
        "incoming_accounts": incoming_count,
        "outgoing_accounts": outgoing_count,
        "incoming_amount": incoming_amount,
        "outgoing_amount": outgoing_amount,
        "incoming_transactions": incoming_transactions,
        "outgoing_transactions": outgoing_transactions,
        "outgoing_ratio": round(outgoing_ratio * 100, 2),
    }


def analyze_all_accounts(graph):
    results = []

    for account_id in graph.nodes():
        result = calculate_account_risk(
            graph,
            account_id
        )

        if result["risk_score"] > 0:
            results.append(result)

    results.sort(
        key=lambda account: account["risk_score"],
        reverse=True
    )

    return results


def get_high_risk_accounts(graph, minimum_score=65):
    all_accounts = analyze_all_accounts(graph)

    return [
        account
        for account in all_accounts
        if account["risk_score"] >= minimum_score
    ]


def get_fraud_statistics(graph):
    accounts = analyze_all_accounts(graph)

    critical = 0
    high = 0
    medium = 0
    low = 0

    for account in accounts:
        score = account["risk_score"]

        if score >= 85:
            critical += 1

        elif score >= 65:
            high += 1

        elif score >= 40:
            medium += 1

        else:
            low += 1

    highest_risk = 0
    highest_risk_account = None

    if accounts:
        highest_risk = accounts[0]["risk_score"]
        highest_risk_account = accounts[0]["account"]

    return {
        "critical": critical,
        "high": high,
        "medium": medium,
        "low": low,
        "total_suspicious": len(accounts),
        "highest_risk": highest_risk,
        "highest_risk_account": highest_risk_account,
    }
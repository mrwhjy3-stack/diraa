def get_outgoing_candidates(graph, account_id):
    if account_id not in graph:
        return []

    candidates = []

    for receiver in graph.successors(account_id):
        edge = graph[account_id][receiver]

        amount = edge.get("total_amount", 0)
        count = edge.get("count", 0)

        candidates.append({
            "account": receiver,
            "amount": round(amount, 2),
            "count": count,
        })

    return candidates


def calculate_candidate_score(candidate, total_outgoing_amount, total_outgoing_count):
    amount_score = 0
    transaction_score = 0

    if total_outgoing_amount > 0:
        amount_score = (
            candidate["amount"] / total_outgoing_amount
        ) * 70

    if total_outgoing_count > 0:
        transaction_score = (
            candidate["count"] / total_outgoing_count
        ) * 30

    score = amount_score + transaction_score

    return round(min(score, 100), 2)


def predict_next_hop(graph, account_id):
    if account_id not in graph:
        return {
            "account": account_id,
            "prediction": None,
            "confidence": 0,
            "candidates": [],
        }

    candidates = get_outgoing_candidates(
        graph,
        account_id
    )

    if not candidates:
        return {
            "account": account_id,
            "prediction": None,
            "confidence": 0,
            "candidates": [],
        }

    total_outgoing_amount = sum(
        candidate["amount"]
        for candidate in candidates
    )

    total_outgoing_count = sum(
        candidate["count"]
        for candidate in candidates
    )

    scored_candidates = []

    for candidate in candidates:
        score = calculate_candidate_score(
            candidate,
            total_outgoing_amount,
            total_outgoing_count
        )

        scored_candidates.append({
            "account": candidate["account"],
            "amount": candidate["amount"],
            "count": candidate["count"],
            "confidence": score,
        })

    scored_candidates.sort(
        key=lambda candidate: candidate["confidence"],
        reverse=True
    )

    best_candidate = scored_candidates[0]

    return {
        "account": account_id,
        "prediction": best_candidate["account"],
        "confidence": best_candidate["confidence"],
        "candidates": scored_candidates[:3],
    }


def predict_path(graph, account_id, steps=3):
    path = [account_id]
    current_account = account_id
    predictions = []

    visited = {account_id}

    for _ in range(steps):
        result = predict_next_hop(
            graph,
            current_account
        )

        next_account = result["prediction"]

        if next_account is None:
            break

        if next_account in visited:
            break

        predictions.append({
            "from": current_account,
            "to": next_account,
            "confidence": result["confidence"],
        })

        path.append(next_account)

        visited.add(next_account)

        current_account = next_account

    return {
        "start_account": account_id,
        "predicted_path": path,
        "steps": predictions,
    }


def get_top_predictions(graph, account_id, limit=3):
    result = predict_next_hop(
        graph,
        account_id
    )

    return {
        "account": account_id,
        "predictions": result["candidates"][:limit],
    }


def explain_prediction(graph, account_id):
    result = predict_next_hop(
        graph,
        account_id
    )

    if result["prediction"] is None:
        return {
            "account": account_id,
            "prediction": None,
            "confidence": 0,
            "explanation": "لا توجد تحويلات صادرة كافية لإنشاء توقع.",
        }

    predicted_account = result["prediction"]
    confidence = result["confidence"]

    explanation = (
        f"تم ترشيح الحساب {predicted_account} "
        f"لأنه يمثل الوجهة الأقوى تاريخيًا "
        f"من حيث قيمة وعدد التحويلات الصادرة "
        f"من الحساب {account_id}."
    )

    return {
        "account": account_id,
        "prediction": predicted_account,
        "confidence": confidence,
        "explanation": explanation,
    }
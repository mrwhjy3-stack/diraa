from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
import pandas as pd
import networkx as nx
from pathlib import Path

from backend.fraud_detection import (
    calculate_account_risk,
    analyze_all_accounts,
    get_high_risk_accounts,
    get_fraud_statistics
)

from backend.graph_analysis import (
    get_account_connections,
    get_account_network,
    find_path,
    get_transfer_path_details,
    get_most_connected_accounts,
    get_network_statistics,
    search_account
)

from backend.prediction import (
    predict_next_hop,
    predict_path,
    get_top_predictions,
    explain_prediction
)

from backend.database import (
    create_alert,
    get_alerts,
    update_alert_status,
    create_case,
    get_cases,
    get_case,
    update_case_status,
    get_database_statistics
)


app = FastAPI(
    title="دِرع",
    description="نظام ذكي لتحليل واكتشاف شبكات الاحتيال المالي",
    version="1.0"
)


app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"]
)


BASE_DIR = Path(__file__).resolve().parent
DATA_FILE = BASE_DIR / "data" / "transactions.csv"

transactions_df = None
financial_graph = nx.DiGraph()


def load_transactions():
    global transactions_df

    if not DATA_FILE.exists():
        raise FileNotFoundError(
            f"ملف التحويلات غير موجود هنا: {DATA_FILE}"
        )

    transactions_df = pd.read_csv(DATA_FILE)

    required_columns = {
        "from_account",
        "to_account",
        "amount",
        "timestamp"
    }

    missing_columns = required_columns - set(
        transactions_df.columns
    )

    if missing_columns:
        raise ValueError(
            f"أعمدة ناقصة في CSV: {missing_columns}"
        )

    transactions_df["amount"] = pd.to_numeric(
        transactions_df["amount"],
        errors="coerce"
    ).fillna(0)

    transactions_df["timestamp"] = pd.to_datetime(
        transactions_df["timestamp"],
        errors="coerce"
    )

    return transactions_df


def build_graph():
    global financial_graph

    financial_graph = nx.DiGraph()

    for _, transaction in transactions_df.iterrows():

        sender = str(transaction["from_account"])
        receiver = str(transaction["to_account"])
        amount = float(transaction["amount"])

        if financial_graph.has_edge(sender, receiver):

            financial_graph[sender][receiver]["total_amount"] += amount
            financial_graph[sender][receiver]["count"] += 1

        else:

            financial_graph.add_edge(
                sender,
                receiver,
                total_amount=amount,
                count=1
            )

    return financial_graph


@app.get("/")
def home():
    return {
        "name": "دِرع",
        "status": "online",
        "message": "دِرع API يعمل بنجاح"
    }


@app.get("/api/dashboard")
def dashboard():
    network_stats = get_network_statistics(
        financial_graph
    )

    fraud_stats = get_fraud_statistics(
        financial_graph
    )

    database_stats = get_database_statistics()

    return {
        "network": network_stats,
        "fraud": fraud_stats,
        "database": database_stats
    }


@app.get("/api/analyze")
def analyze():
    return {
        "accounts": analyze_all_accounts(
            financial_graph
        )
    }


@app.get("/api/high-risk")
def high_risk(minimum_score: int = 65):
    return {
        "accounts": get_high_risk_accounts(
            financial_graph,
            minimum_score
        )
    }


@app.get("/api/account/{account_id}")
def account_details(account_id: str):

    if account_id not in financial_graph:
        raise HTTPException(
            status_code=404,
            detail="الحساب غير موجود"
        )

    risk = calculate_account_risk(
        financial_graph,
        account_id
    )

    connections = get_account_connections(
        financial_graph,
        account_id
    )

    prediction = predict_next_hop(
        financial_graph,
        account_id
    )

    return {
        "risk": risk,
        "connections": connections,
        "prediction": prediction
    }


@app.get("/api/network/{account_id}")
def account_network(account_id: str, depth: int = 2):

    if account_id not in financial_graph:
        raise HTTPException(
            status_code=404,
            detail="الحساب غير موجود"
        )

    return get_account_network(
        financial_graph,
        account_id,
        depth
    )


@app.get("/api/network")
def full_network():

    nodes = []

    for account in financial_graph.nodes():

        risk = calculate_account_risk(
            financial_graph,
            account
        )

        nodes.append({
            "id": account,
            "risk_score": risk["risk_score"],
            "risk_level": risk["risk_level"]
        })

    edges = []

    for sender, receiver, data in financial_graph.edges(data=True):

        edges.append({
            "source": sender,
            "target": receiver,
            "amount": round(
                data.get("total_amount", 0),
                2
            ),
            "count": data.get("count", 0)
        })

    return {
        "nodes": nodes,
        "edges": edges
    }


@app.get("/api/predict/{account_id}")
def prediction(account_id: str):

    if account_id not in financial_graph:
        raise HTTPException(
            status_code=404,
            detail="الحساب غير موجود"
        )

    return {
        "next_hop": predict_next_hop(
            financial_graph,
            account_id
        ),
        "path": predict_path(
            financial_graph,
            account_id
        ),
        "top_predictions": get_top_predictions(
            financial_graph,
            account_id
        ),
        "explanation": explain_prediction(
            financial_graph,
            account_id
        )
    }


@app.get("/api/search/{account_id}")
def account_search(account_id: str):

    result = search_account(
        financial_graph,
        account_id
    )

    if not result["found"]:
        raise HTTPException(
            status_code=404,
            detail="الحساب غير موجود"
        )

    return result


@app.get("/api/path/{from_account}/{to_account}")
def transfer_path(
    from_account: str,
    to_account: str
):

    result = find_path(
        financial_graph,
        from_account,
        to_account
    )

    if not result["found"]:
        return {
            "found": False,
            "message": "لا يوجد مسار بين الحسابين"
        }

    details = get_transfer_path_details(
        financial_graph,
        result["path"]
    )

    return {
        "found": True,
        "path": result["path"],
        "length": result["length"],
        "details": details
    }


@app.get("/api/most-connected")
def most_connected(limit: int = 10):

    return {
        "accounts": get_most_connected_accounts(
            financial_graph,
            limit
        )
    }


@app.get("/api/alerts")
def alerts():
    return {
        "alerts": get_alerts()
    }


@app.post("/api/alerts/generate")
def generate_alerts():

    accounts = get_high_risk_accounts(
        financial_graph,
        65
    )

    created = []

    for account in accounts[:20]:

        reasons = account["reasons"]

        message = "، ".join(reasons[:3])

        alert = create_alert(
            account["account"],
            account["risk_score"],
            account["risk_level"],
            message
        )

        created.append(alert)

    return {
        "created": len(created),
        "alerts": created
    }


@app.put("/api/alerts/{alert_id}")
def change_alert_status(
    alert_id: int,
    status: str
):

    success = update_alert_status(
        alert_id,
        status
    )

    if not success:
        raise HTTPException(
            status_code=404,
            detail="التنبيه غير موجود"
        )

    return {
        "success": True,
        "status": status
    }


@app.get("/api/cases")
def cases():
    return {
        "cases": get_cases()
    }


@app.get("/api/cases/{case_id}")
def case_details(case_id: int):

    case = get_case(case_id)

    if case is None:
        raise HTTPException(
            status_code=404,
            detail="التحقيق غير موجود"
        )

    return case


@app.post("/api/cases")
def new_case(
    account_id: str,
    title: str,
    description: str = "",
    priority: str = "متوسط"
):

    if account_id not in financial_graph:
        raise HTTPException(
            status_code=404,
            detail="الحساب غير موجود"
        )

    return create_case(
        account_id,
        title,
        description,
        priority
    )


@app.put("/api/cases/{case_id}")
def change_case_status(
    case_id: int,
    status: str
):

    success = update_case_status(
        case_id,
        status
    )

    if not success:
        raise HTTPException(
            status_code=404,
            detail="التحقيق غير موجود"
        )

    return {
        "success": True,
        "status": status
    }


load_transactions()
build_graph()
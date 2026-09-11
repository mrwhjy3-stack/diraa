import sqlite3
from pathlib import Path
from datetime import datetime


BASE_DIR = Path(__file__).resolve().parent
DATABASE_FILE = BASE_DIR / "fraudgraph.db"


def get_connection():
    connection = sqlite3.connect(DATABASE_FILE)
    connection.row_factory = sqlite3.Row
    return connection


def initialize_database():
    connection = get_connection()
    cursor = connection.cursor()

    cursor.execute("""
        CREATE TABLE IF NOT EXISTS alerts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            account_id TEXT NOT NULL,
            risk_score INTEGER NOT NULL,
            risk_level TEXT NOT NULL,
            message TEXT NOT NULL,
            status TEXT DEFAULT 'جديد',
            created_at TEXT NOT NULL
        )
    """)

    cursor.execute("""
        CREATE TABLE IF NOT EXISTS cases (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            account_id TEXT NOT NULL,
            title TEXT NOT NULL,
            description TEXT,
            status TEXT DEFAULT 'مفتوح',
            priority TEXT DEFAULT 'متوسط',
            created_at TEXT NOT NULL
        )
    """)

    connection.commit()
    connection.close()


def create_alert(account_id, risk_score, risk_level, message):
    connection = get_connection()
    cursor = connection.cursor()

    created_at = datetime.now().strftime("%Y-%m-%d %H:%M:%S")

    cursor.execute("""
        INSERT INTO alerts (
            account_id,
            risk_score,
            risk_level,
            message,
            created_at
        )
        VALUES (?, ?, ?, ?, ?)
    """, (
        account_id,
        risk_score,
        risk_level,
        message,
        created_at
    ))

    alert_id = cursor.lastrowid

    connection.commit()
    connection.close()

    return {
        "id": alert_id,
        "account_id": account_id,
        "risk_score": risk_score,
        "risk_level": risk_level,
        "message": message,
        "status": "جديد",
        "created_at": created_at
    }


def get_alerts():
    connection = get_connection()
    cursor = connection.cursor()

    cursor.execute("""
        SELECT *
        FROM alerts
        ORDER BY id DESC
    """)

    rows = cursor.fetchall()
    connection.close()

    return [dict(row) for row in rows]


def update_alert_status(alert_id, status):
    connection = get_connection()
    cursor = connection.cursor()

    cursor.execute("""
        UPDATE alerts
        SET status = ?
        WHERE id = ?
    """, (
        status,
        alert_id
    ))

    updated = cursor.rowcount

    connection.commit()
    connection.close()

    return updated > 0


def create_case(account_id, title, description="", priority="متوسط"):
    connection = get_connection()
    cursor = connection.cursor()

    created_at = datetime.now().strftime("%Y-%m-%d %H:%M:%S")

    cursor.execute("""
        INSERT INTO cases (
            account_id,
            title,
            description,
            priority,
            created_at
        )
        VALUES (?, ?, ?, ?, ?)
    """, (
        account_id,
        title,
        description,
        priority,
        created_at
    ))

    case_id = cursor.lastrowid

    connection.commit()
    connection.close()

    return {
        "id": case_id,
        "account_id": account_id,
        "title": title,
        "description": description,
        "status": "مفتوح",
        "priority": priority,
        "created_at": created_at
    }


def get_cases():
    connection = get_connection()
    cursor = connection.cursor()

    cursor.execute("""
        SELECT *
        FROM cases
        ORDER BY id DESC
    """)

    rows = cursor.fetchall()
    connection.close()

    return [dict(row) for row in rows]


def get_case(case_id):
    connection = get_connection()
    cursor = connection.cursor()

    cursor.execute("""
        SELECT *
        FROM cases
        WHERE id = ?
    """, (case_id,))

    row = cursor.fetchone()
    connection.close()

    if row is None:
        return None

    return dict(row)


def update_case_status(case_id, status):
    connection = get_connection()
    cursor = connection.cursor()

    cursor.execute("""
        UPDATE cases
        SET status = ?
        WHERE id = ?
    """, (
        status,
        case_id
    ))

    updated = cursor.rowcount

    connection.commit()
    connection.close()

    return updated > 0


def get_database_statistics():
    connection = get_connection()
    cursor = connection.cursor()

    cursor.execute("SELECT COUNT(*) FROM alerts")
    total_alerts = cursor.fetchone()[0]

    cursor.execute("""
        SELECT COUNT(*)
        FROM alerts
        WHERE status = 'جديد'
    """)
    new_alerts = cursor.fetchone()[0]

    cursor.execute("SELECT COUNT(*) FROM cases")
    total_cases = cursor.fetchone()[0]

    cursor.execute("""
        SELECT COUNT(*)
        FROM cases
        WHERE status = 'مفتوح'
    """)
    open_cases = cursor.fetchone()[0]

    connection.close()

    return {
        "total_alerts": total_alerts,
        "new_alerts": new_alerts,
        "total_cases": total_cases,
        "open_cases": open_cases
    }


initialize_database()
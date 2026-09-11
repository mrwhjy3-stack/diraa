import networkx as nx


def get_account_connections(graph, account_id):
    if account_id not in graph:
        return {
            "account": account_id,
            "incoming": [],
            "outgoing": [],
            "total_connections": 0,
        }

    incoming = list(graph.predecessors(account_id))
    outgoing = list(graph.successors(account_id))

    return {
        "account": account_id,
        "incoming": incoming,
        "outgoing": outgoing,
        "total_connections": len(set(incoming + outgoing)),
    }


def get_account_network(graph, account_id, depth=2):
    if account_id not in graph:
        return {
            "center_account": account_id,
            "nodes": [],
            "edges": [],
        }

    visited = {account_id}
    current_level = {account_id}

    for _ in range(depth):
        next_level = set()

        for account in current_level:
            next_level.update(graph.predecessors(account))
            next_level.update(graph.successors(account))

        next_level -= visited
        visited.update(next_level)
        current_level = next_level

        if not current_level:
            break

    nodes = []

    for account in visited:
        nodes.append({
            "id": account,
            "is_center": account == account_id,
        })

    edges = []

    for sender, receiver, data in graph.edges(data=True):
        if sender in visited and receiver in visited:
            edges.append({
                "source": sender,
                "target": receiver,
                "amount": round(data.get("total_amount", 0), 2),
                "count": data.get("count", 0),
            })

    return {
        "center_account": account_id,
        "nodes": nodes,
        "edges": edges,
    }


def find_path(graph, from_account, to_account):
    if from_account not in graph or to_account not in graph:
        return {
            "found": False,
            "path": [],
            "length": 0,
        }

    try:
        path = nx.shortest_path(
            graph,
            source=from_account,
            target=to_account
        )

        return {
            "found": True,
            "path": path,
            "length": len(path) - 1,
        }

    except nx.NetworkXNoPath:
        return {
            "found": False,
            "path": [],
            "length": 0,
        }


def get_transfer_path_details(graph, path):
    transfers = []
    total_amount = 0

    if len(path) < 2:
        return {
            "transfers": [],
            "total_amount": 0,
        }

    for index in range(len(path) - 1):
        sender = path[index]
        receiver = path[index + 1]

        if graph.has_edge(sender, receiver):
            edge = graph[sender][receiver]

            amount = edge.get("total_amount", 0)
            count = edge.get("count", 0)

            total_amount += amount

            transfers.append({
                "from": sender,
                "to": receiver,
                "amount": round(amount, 2),
                "count": count,
            })

    return {
        "transfers": transfers,
        "total_amount": round(total_amount, 2),
    }


def get_most_connected_accounts(graph, limit=10):
    accounts = []

    for account_id in graph.nodes():
        incoming = graph.in_degree(account_id)
        outgoing = graph.out_degree(account_id)

        accounts.append({
            "account": account_id,
            "incoming_connections": incoming,
            "outgoing_connections": outgoing,
            "total_connections": incoming + outgoing,
        })

    accounts.sort(
        key=lambda account: account["total_connections"],
        reverse=True
    )

    return accounts[:limit]


def get_network_statistics(graph):
    total_accounts = graph.number_of_nodes()
    total_connections = graph.number_of_edges()

    total_amount = 0
    total_transactions = 0

    for _, _, data in graph.edges(data=True):
        total_amount += data.get("total_amount", 0)
        total_transactions += data.get("count", 0)

    return {
        "total_accounts": total_accounts,
        "total_connections": total_connections,
        "total_transactions": total_transactions,
        "total_amount": round(total_amount, 2),
    }


def search_account(graph, account_id):
    if account_id not in graph:
        return {
            "found": False,
            "account": account_id,
        }

    connections = get_account_connections(
        graph,
        account_id
    )

    return {
        "found": True,
        "account": account_id,
        "incoming": connections["incoming"],
        "outgoing": connections["outgoing"],
        "total_connections": connections["total_connections"],
    }
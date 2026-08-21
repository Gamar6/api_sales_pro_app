import xmlrpc.client
import pandas as pd
import config

# ===============================
# CONNECT
# ===============================
common = xmlrpc.client.ServerProxy(f"{config.ODOO_URL}/xmlrpc/2/common")
uid = common.authenticate(
    config.ODOO_DB,
    config.ODOO_USERNAME,
    config.ODOO_PASSWORD,
    {}
)

if not uid:
    raise Exception("Authentication failed")

models = xmlrpc.client.ServerProxy(f"{config.ODOO_URL}/xmlrpc/2/object")
print("Connected to Odoo as UID:", uid)

# ===============================
# 1️⃣ FETCH SALE ORDER HEADER
# ===============================
domain = [
    ("state", "in", ["sale", "done"]),
    ("date_order", ">=", config.START_DATE),
]

fields = [
    "id",
    "name",
    "date_order",
    "partner_id",
    "user_id",      # SALESPERSON
]

sale_orders = models.execute_kw(
    config.ODOO_DB,
    uid,
    config.ODOO_PASSWORD,
    "sale.order",
    "search_read",
    [domain],
    {"fields": fields, "limit": 0}
)

df_order = pd.DataFrame(sale_orders)

# Extract relational IDs
df_order["partner_id"] = df_order["partner_id"].apply(
    lambda x: x[0] if isinstance(x, list) else None
)

df_order["customer_name"] = df_order["partner_id"]

df_order["sales_id"] = df_order["user_id"].apply(
    lambda x: x[0] if isinstance(x, list) else None
)

df_order["sales_name"] = df_order["user_id"].apply(
    lambda x: x[1] if isinstance(x, list) else None
)

order_ids = df_order["id"].tolist()

print("Total Orders:", len(order_ids))

# ===============================
# 2️⃣ FETCH SALE ORDER LINE
# ===============================
line_domain = [
    ("order_id", "in", order_ids),
]

line_fields = [
    "order_id",
    "product_id",
    "product_uom_qty",
    "price_unit",
    "price_subtotal",
]

sale_lines = models.execute_kw(
    config.ODOO_DB,
    uid,
    config.ODOO_PASSWORD,
    "sale.order.line",
    "search_read",
    [line_domain],
    {"fields": line_fields, "limit": 0}
)

df_line = pd.DataFrame(sale_lines)

df_line["order_id"] = df_line["order_id"].apply(
    lambda x: x[0] if isinstance(x, list) else None
)

df_line["product_id"] = df_line["product_id"].apply(
    lambda x: x[0] if isinstance(x, list) else None
)

# ===============================
# 3️⃣ FETCH PRODUCT NAME
# ===============================
product_ids = df_line["product_id"].dropna().unique().tolist()

products = models.execute_kw(
    config.ODOO_DB,
    uid,
    config.ODOO_PASSWORD,
    "product.product",
    "search_read",
    [[("id", "in", product_ids)]],
    {"fields": ["id", "name"]}
)

df_product = pd.DataFrame(products)

# ===============================
# 4️⃣ MERGE ALL
# ===============================
df_final = df_line.merge(
    df_product,
    left_on="product_id",
    right_on="id",
    how="left"
)

df_final = df_final.merge(
    df_order[
        ["id", "name", "date_order", "partner_id", "sales_name"]
    ],
    left_on="order_id",
    right_on="id",
    how="left"
)

# Rename columns clean
df_final = df_final.rename(columns={
    "name_x": "SKU",
    "name_y": "SO_Number",
    "partner_id": "Customer_ID",
    "sales_name": "Salesperson",
    "product_uom_qty": "Qty",
    "price_unit": "Unit_Price",
    "price_subtotal": "Subtotal"
})

# ===============================
# SAVE
# ===============================
output_file = config.RAW_PATH + r"\raw_sales_detail_full.xlsx"
df_final.to_excel(output_file, index=False)

print("Full detail file saved:", output_file)

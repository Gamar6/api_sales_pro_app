import xmlrpc.client
import pandas as pd
import config

# ===============================
# CONNECT TO ODOO
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
# DOMAIN FILTER
# ===============================
domain = [
    ("state", "in", ["sale", "done"]),
    ("date_order", ">=", config.START_DATE),
]

fields = [
    "name",
    "date_order",
    "partner_id",
    "amount_total",
    "state",
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

print("Total Sales Fetched:", len(sale_orders))

if not sale_orders:
    print("No sales data found.")
    exit()

df = pd.DataFrame(sale_orders)

# ===============================
# CLEAN PARTNER ID
# ===============================
df["partner_id"] = df["partner_id"].apply(
    lambda x: x[0] if isinstance(x, list) else None
)

partner_ids = df["partner_id"].dropna().unique().tolist()

if not partner_ids:
    print("No partner IDs found.")
    exit()

# ===============================
# FETCH PARTNER DATA
# ===============================
partners = models.execute_kw(
    config.ODOO_DB,
    uid,
    config.ODOO_PASSWORD,
    "res.partner",
    "search_read",
    [[("id", "in", partner_ids)]],
    {"fields": ["id", "name", "city"]}
)

df_partner = pd.DataFrame(partners)

if df_partner.empty:
    print("No partner data found.")
    exit()

# ===============================
# NORMALIZE CITY TEXT (SAFE)
# ===============================
df_partner["city_clean"] = (
    df_partner["city"]
    .fillna("")
    .astype(str)
    .str.strip()
    .str.lower()
)

# ===============================
# TARGET CITIES
# ===============================
target_cities = ["bandung", "tasikmalaya", "garut"]
pattern = "|".join(target_cities)

df_partner_filtered = df_partner[
    df_partner["city_clean"].str.contains(pattern, na=False)
]

print("Total Partner in Target Cities:", len(df_partner_filtered))

# ===============================
# MERGE
# ===============================
df_final = df.merge(
    df_partner_filtered[["id", "name", "city"]],
    left_on="partner_id",
    right_on="id",
    how="inner"
)

print("Total Sales (BDG/TSM/GRT):", len(df_final))

# ===============================
# SAVE
# ===============================
output_file = config.RAW_PATH + r"\raw_sales_bandung_tasik_garut.xlsx"
df_final.to_excel(output_file, index=False)

print("File saved to:", output_file)

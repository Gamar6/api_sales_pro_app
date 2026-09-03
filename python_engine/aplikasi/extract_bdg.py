# ==========================================================
# EKSTRAKSI ANALISIS BANDUNG - SUPPLY CHAIN VERSION
# Fokus: Rolade Sapi - Weekly Absorption & Shipment Model
# ==========================================================

import xmlrpc.client
import pandas as pd
import numpy as np

# ==========================================================
# 1. KONFIGURASI ERP
# ==========================================================

ROLADENAME = "ROLADE SAPI"
TARGET_SHIPMENT_KG = 3000
WAREHOUSE_CAPACITY_KG = 6000

# ==========================================================
# 2. CONNECT
# ==========================================================

common = xmlrpc.client.ServerProxy(f"{url}/xmlrpc/2/common")
uid = common.authenticate(db, username, password, {})

if not uid:
    print("Autentikasi gagal.")
    exit()

models = xmlrpc.client.ServerProxy(f"{url}/xmlrpc/2/object")

# ==========================================================
# 3. CUSTOMER BANDUNG
# ==========================================================

bandung_customers = [ ... ]  # tetap seperti sebelumnya

partner_ids = models.execute_kw(
    db, uid, password,
    'res.partner', 'search',
    [[('name', 'in', bandung_customers)]]
)

# ==========================================================
# 4. AMBIL SALE ORDER LINE
# ==========================================================

domain = [
    ('order_partner_id', 'in', partner_ids),
    ('order_id.state', 'in', ['sale', 'done']),
    ('order_id.date_order', '>=', '2022-01-01')
]

fields = [
    'order_id',
    'order_partner_id',
    'product_id',
    'product_uom_qty',
    'price_subtotal',
    'create_date'
]

sale_lines = models.execute_kw(
    db, uid, password,
    'sale.order.line', 'search_read',
    [domain],
    {'fields': fields, 'limit': 200000}
)

# ==========================================================
# 5. AMBIL WEIGHT PRODUK
# ==========================================================

product_ids = list({
    line['product_id'][0]
    for line in sale_lines
    if line.get('product_id')
})

products = models.execute_kw(
    db, uid, password,
    'product.product', 'read',
    [product_ids],
    {'fields': ['id', 'name', 'weight']}
)

product_weight_map = {p['id']: (p['weight'] or 0) for p in products}

# ==========================================================
# 6. BUILD DATAFRAME
# ==========================================================

data = []

for line in sale_lines:

    if not line.get('product_id'):
        continue

    product_id = line['product_id'][0]
    product_name = line['product_id'][1]

    qty = line.get('product_uom_qty') or 0
    weight = product_weight_map.get(product_id, 0)

    data.append({
        'Product': product_name,
        'Total_Kg': qty * weight,
        'Create Date': line.get('create_date')
    })

df = pd.DataFrame(data)

df['Create Date'] = pd.to_datetime(df['Create Date'])
df['Year'] = df['Create Date'].dt.year
df['Week'] = df['Create Date'].dt.to_period('W')

# ==========================================================
# 7. FILTER ROLADE SAPI
# ==========================================================

df_rolade = df[df['Product'].str.contains(ROLADENAME, case=False)]

# ==========================================================
# 8. WEEKLY ABSORPTION
# ==========================================================

weekly_absorption = (
    df_rolade.groupby('Week')
    .agg(weekly_kg=('Total_Kg', 'sum'))
    .reset_index()
)

avg_weekly = weekly_absorption['weekly_kg'].mean()
std_weekly = weekly_absorption['weekly_kg'].std()
cv_weekly = std_weekly / avg_weekly if avg_weekly > 0 else 0
max_week = weekly_absorption['weekly_kg'].max()
min_week = weekly_absorption['weekly_kg'].min()

print("====================================")
print("ROLADESAPI WEEKLY ANALYSIS")
print("Avg Weekly (Kg):", round(avg_weekly, 2))
print("Std Dev Weekly:", round(std_weekly, 2))
print("CV:", round(cv_weekly, 2))
print("Max Week:", round(max_week, 2))
print("Min Week:", round(min_week, 2))
print("====================================")

# ==========================================================
# 9. SIMULASI PENGIRIMAN 3 TON PER MINGGU
# ==========================================================

inventory = 0
simulation = []

for _, row in weekly_absorption.iterrows():
    demand = row['weekly_kg']
    inventory += TARGET_SHIPMENT_KG  # kirim 3 ton
    inventory -= demand

    simulation.append({
        'Week': row['Week'],
        'Demand_Kg': demand,
        'Inventory_After_Demand': inventory
    })

sim_df = pd.DataFrame(simulation)

# ==========================================================
# 10. EXPORT
# ==========================================================

file_name = "ANALISIS_SUPPLY_CHAIN_BANDUNG.xlsx"

with pd.ExcelWriter(file_name) as writer:
    weekly_absorption.to_excel(writer, sheet_name="Weekly_Absorption", index=False)
    sim_df.to_excel(writer, sheet_name="Shipment_Simulation", index=False)

print("Analisis selesai.")

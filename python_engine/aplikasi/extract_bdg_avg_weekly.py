# ==========================================================
# ANALISIS RATA-RATA SERAPAN MINGGUAN BANDUNG PER SKU
# ==========================================================

import xmlrpc.client
import pandas as pd
import numpy as np

# ==========================================================
# 1. KONFIGURASI ERP
# ==========================================================

# ==========================================================
# 2. CONNECT ERP
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

bandung_customers = [
    "AJENG FROZEN (AJENG NUR AULYA LUKMAN)",
    "ANDIR FOOD (BANDUNG)",
    "AQILA FROZEN (LALU HIMAWAN SUTANTO)",
    "BANYU FROZEN FOOD (IVAN ADITYA PRATAMA)",
    "CV RUMAH BEKU (Bandung)",
    "HAVANA MENTARI (Bdg)",
    "JURAGAN FROZEN (Bdg)",
    "LANGIT FROZEN (BDG)",
    "PT LANGLANGBUANA MEGA PERKASA (BDG)",
    "PT. BILBARKAH INSANI SADAYA",
    "PT. MULYA RAHAYU PRATAMA (BDG)",
    "RISTINA SANEGA JAYA (DIAN WARDA VIRIZKIANA)",
    "RUMAH SOSIS MAJALAYA (MIA MARDIAN)",
    "SAEFUDIN (SUMEDANG)",
    "YANA, Bp ( TEGUH ) BDG ( CV DUA MUTIARA )",
    "YANA, Bp BDG ( CV DUA MUTIARA )"
]

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
    'product_id',
    'product_uom_qty',
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

    pid = line['product_id'][0]
    pname = line['product_id'][1]
    qty = line.get('product_uom_qty') or 0
    weight = product_weight_map.get(pid, 0)

    data.append({
        'Product': pname,
        'Total_Kg': qty * weight,
        'Create Date': line.get('create_date')
    })

df = pd.DataFrame(data)

df['Create Date'] = pd.to_datetime(df['Create Date'])
df['Week'] = df['Create Date'].dt.to_period('W')

# ==========================================================
# 7. WEEKLY PER SKU
# ==========================================================

weekly_per_sku = (
    df.groupby(['Week', 'Product'])
    .agg(weekly_kg=('Total_Kg', 'sum'))
    .reset_index()
)

# ==========================================================
# 8. HITUNG RATA-RATA & STD DEV PER SKU
# ==========================================================

avg_per_sku = (
    weekly_per_sku.groupby('Product')
    .agg(
        avg_weekly_kg=('weekly_kg', 'mean'),
        std_weekly_kg=('weekly_kg', 'std'),
        max_weekly_kg=('weekly_kg', 'max'),
        min_weekly_kg=('weekly_kg', 'min')
    )
    .reset_index()
)

# ==========================================================
# 9. HITUNG SHARE %
# ==========================================================

total_avg_weekly = avg_per_sku['avg_weekly_kg'].sum()

avg_per_sku['share_%'] = (
    avg_per_sku['avg_weekly_kg'] / total_avg_weekly * 100
)

avg_per_sku = avg_per_sku.sort_values(
    by='avg_weekly_kg', ascending=False
)

# ==========================================================
# 10. RATA-RATA TOTAL MINGGUAN
# ==========================================================

weekly_total = (
    df.groupby('Week')
    .agg(total_weekly_kg=('Total_Kg', 'sum'))
    .reset_index()
)

avg_total_weekly = weekly_total['total_weekly_kg'].mean()
std_total_weekly = weekly_total['total_weekly_kg'].std()

print("====================================")
print("RATA-RATA TOTAL MINGGUAN BANDUNG:", round(avg_total_weekly,2),"Kg")
print("STD DEV TOTAL MINGGUAN:", round(std_total_weekly,2),"Kg")
print("====================================")

# ==========================================================
# 11. EXPORT
# ==========================================================

file_name = "ANALISIS_AVG_WEEKLY_PER_SKU_BANDUNG.xlsx"

with pd.ExcelWriter(file_name) as writer:
    weekly_per_sku.to_excel(writer, sheet_name="Weekly_Per_SKU", index=False)
    avg_per_sku.to_excel(writer, sheet_name="Average_Per_SKU", index=False)
    weekly_total.to_excel(writer, sheet_name="Weekly_Total", index=False)

print("File dibuat:", file_name)

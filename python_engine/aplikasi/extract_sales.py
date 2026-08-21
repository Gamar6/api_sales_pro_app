import xmlrpc.client
import pandas as pd
from datetime import datetime
import os
import sys

# Import config
from config import (
    ODOO_URL,
    ODOO_DB,
    ODOO_USERNAME,
    ODOO_PASSWORD,
    START_DATE,
    RAW_SALES_FILE
)

# ===============================
# CONNECT TO ODOO
# ===============================

def connect_odoo():
    try:
        common = xmlrpc.client.ServerProxy(f"{ODOO_URL}/xmlrpc/2/common")
        uid = common.authenticate(ODOO_DB, ODOO_USERNAME, ODOO_PASSWORD, {})
        models = xmlrpc.client.ServerProxy(f"{ODOO_URL}/xmlrpc/2/object")

        if not uid:
            raise Exception("Authentication failed.")

        print("Connected to Odoo.")
        return uid, models

    except Exception as e:
        print("Connection Error:", e)
        sys.exit(1) # Penyesuaian: Mengembalikan status error (1) ke pipeline


# ===============================
# EXTRACT SALES DATA
# ===============================

def extract_sales(uid, models):

    print("Extracting sales data...")

    orders = models.execute_kw(
        ODOO_DB,
        uid,
        ODOO_PASSWORD,
        'sale.order',
        'search_read',
        [[
            ['state', 'in', ['sale', 'done']],
            ['date_order', '>=', START_DATE],
            ['partner_id.city', '!=', False]
        ]],
        {
            'fields': [
                'partner_id',
                'date_order',
                'amount_total',
                'user_id'
            ],
            'limit': 50000
        }
    )

    if not orders:
        print("No sales data found.")
        return pd.DataFrame()

    df = pd.DataFrame(orders)

    # ===============================
    # CLEAN CUSTOMER FIELD
    # ===============================

    df['partner_name'] = df['partner_id'].apply(
        lambda x: x[1] if isinstance(x, list) else None
    )

    df['partner_id'] = df['partner_id'].apply(
        lambda x: x[0] if isinstance(x, list) else None
    )

    # ===============================
    # CLEAN SALES FIELD
    # ===============================

    df['sales_name'] = df['user_id'].apply(
        lambda x: x[1] if isinstance(x, list) else None
    )

    df['sales_id'] = df['user_id'].apply(
        lambda x: x[0] if isinstance(x, list) else None
    )

    df.drop(columns=['user_id'], inplace=True)

    df['date_order'] = pd.to_datetime(df['date_order'])

    # ===============================
    # GET PARTNER DATA (CITY + PHONE)
    # ===============================

    unique_partner_ids = df['partner_id'].dropna().unique().tolist()

    partners = models.execute_kw(
        ODOO_DB,
        uid,
        ODOO_PASSWORD,
        'res.partner',
        'search_read',
        [[['id', 'in', unique_partner_ids]]],
        {
            'fields': ['id', 'city', 'phone', 'mobile'],
            'limit': len(unique_partner_ids)
        }
    )

    df_partner = pd.DataFrame(partners)

    df = df.merge(
        df_partner,
        left_on='partner_id',
        right_on='id',
        how='left'
    )

    df.rename(columns={'city': 'kota'}, inplace=True)

    # Gabungkan phone & mobile
    df['phone_clean'] = df['phone'].fillna(df['mobile'])

    # ===============================
    # FINAL COLUMN STRUCTURE
    # ===============================

    df = df[[
        'partner_id',
        'partner_name',
        'kota',
        'phone_clean',
        'sales_id',
        'sales_name',
        'date_order',
        'amount_total'
    ]]

    print("Sales records extracted:", len(df))

    return df


# ===============================
# SAVE RAW SNAPSHOT
# ===============================

def save_raw(df):

    if df.empty:
        print("Empty dataframe. Nothing saved.")
        return

    raw_folder = os.path.dirname(RAW_SALES_FILE)
    os.makedirs(raw_folder, exist_ok=True)

    today_str = datetime.today().strftime("%Y-%m-%d")

    output_file = os.path.join(
        raw_folder,
        f"{today_str}_raw_sales.xlsx"
    )

    df.to_excel(output_file, index=False)

    print("Raw sales snapshot saved:")
    print(output_file)


# ===============================
# MAIN
# ===============================

if __name__ == "__main__":
    uid, models = connect_odoo()
    df_sales = extract_sales(uid, models)
    save_raw(df_sales)
    print("Extraction completed successfully.")
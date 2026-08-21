import pandas as pd
from datetime import datetime
import os
import sys
import numpy as np

from config import (
    RAW_PATH,
    PROCESSED_PATH,
    MASTER_CUSTOMER_PATH
)

# ===============================
# LOAD RAW
# ===============================

def load_raw():

    files = [f for f in os.listdir(RAW_PATH)
             if f.endswith("_raw_sales.xlsx")]

    if not files:
        print("No raw sales snapshot found.")
        sys.exit(1) # Penyesuaian: Mengembalikan status error (1) ke pipeline

    files.sort()
    latest_path = os.path.join(RAW_PATH, files[-1])

    print("Using latest raw snapshot:")
    print(latest_path)

    df = pd.read_excel(latest_path)
    df['date_order'] = pd.to_datetime(df['date_order'])

    return df


# ===============================
# LOAD MASTER JABODETABEK
# ===============================

def load_master_jabodetabek():

    master = pd.read_excel(MASTER_CUSTOMER_PATH)

    master_jabodetabek = master[
        master['delivery_route'] != "FALSE"
    ]

    return master_jabodetabek[['partner_id', 'delivery_route']]


# ===============================
# CLASSIFICATION (RUMUS TETAP)
# ===============================

def classify_retensi(weeks):

    if weeks <= 2:
        return "ACTIVE"
    elif weeks <= 4:
        return "WARNING"
    elif weeks <= 8:
        return "RED FLAG"
    elif weeks <= 12:
        return "WARM"
    elif weeks <= 24:
        return "COLD"
    else:
        return "DEAD ZONE"


def classify_group(status):

    if status in ["ACTIVE", "WARNING", "RED FLAG"]:
        return "AKTIF"
    else:
        return "NON_AKTIF"


def priority_value(status):

    mapping = {
        "RED FLAG": 1,
        "WARNING": 2,
        "ACTIVE": 3,
        "WARM": 4,
        "COLD": 5,
        "DEAD ZONE": 6
    }

    return mapping.get(status, 99)


# ===============================
# CLEAN AVERAGE RETENSI (RUMUS TETAP)
# ===============================

def calculate_avg_retensi(group):

    dates = group.sort_values().values

    if len(dates) < 3:
        return np.nan

    intervals = np.diff(dates) / np.timedelta64(1, 'D')

    if len(intervals) < 2:
        return np.mean(intervals) / 7

    Q1 = np.percentile(intervals, 25)
    Q3 = np.percentile(intervals, 75)
    IQR = Q3 - Q1
    upper_bound = Q3 + 1.5 * IQR

    intervals_clean = intervals[intervals <= upper_bound]

    if len(intervals_clean) == 0:
        return np.mean(intervals) / 7

    return np.mean(intervals_clean) / 7


# ===============================
# TRANSFORM
# ===============================

def transform_retensi(df, master_jabodetabek):

    # Penyesuaian: Menggunakan Timestamp Pandas agar operasi pengurangan datetime stabil
    today = pd.Timestamp.now()

    df = df[df['partner_id'].isin(master_jabodetabek['partner_id'])]

    df_sorted = df.sort_values(['partner_id', 'date_order'])

    last_orders = df_sorted.groupby('partner_id', as_index=False).tail(1).copy()

    total_sales = df.groupby('partner_id', as_index=False)['amount_total'].sum()

    last_orders = last_orders.merge(
        total_sales,
        on='partner_id',
        suffixes=('', '_total')
    )

    last_orders.rename(columns={
        'date_order': 'last_order_date',
        'amount_total_total': 'total_sales_2024_plus'
    }, inplace=True)

    # Pengurangan tanggal yang aman
    last_orders['last_order_date'] = pd.to_datetime(last_orders['last_order_date'])
    last_orders['days_since'] = (today - last_orders['last_order_date']).dt.days
    last_orders['weeks_since'] = (last_orders['days_since'] // 7).astype(int)

    last_orders['retensi_status'] = last_orders['weeks_since'].apply(classify_retensi)

    avg_retensi = df.groupby('partner_id')['date_order'].apply(
        calculate_avg_retensi
    ).reset_index()

    avg_retensi.rename(columns={
        'date_order': 'avg_retensi_weeks'
    }, inplace=True)

    last_orders = last_orders.merge(avg_retensi, on='partner_id', how='left')

    last_orders['avg_retensi_weeks'] = last_orders['avg_retensi_weeks'].round(1)

    last_orders['gap_vs_average'] = (
        last_orders['weeks_since'] -
        last_orders['avg_retensi_weeks']
    ).round(1)

    last_orders['aktif_group'] = last_orders['retensi_status'].apply(classify_group)
    last_orders['priority'] = last_orders['retensi_status'].apply(priority_value)

    last_orders = last_orders.merge(master_jabodetabek,
                                    on='partner_id',
                                    how='left')

    last_orders['snapshot_date'] = today.strftime("%Y-%m-%d")

    # FINAL SORT
    last_orders = last_orders.sort_values(
        by=['aktif_group', 'priority', 'weeks_since'],
        ascending=[True, True, False]
    )

    return last_orders


# ===============================
# SAVE WITH COLOR
# ===============================

def save_output(df):

    os.makedirs(PROCESSED_PATH, exist_ok=True)

    today_str = datetime.today().strftime("%Y-%m-%d")

    output_file = os.path.join(
        PROCESSED_PATH,
        f"{today_str}_retensi_jabodetabek.xlsx"
    )

    writer = pd.ExcelWriter(output_file, engine='xlsxwriter')
    df.to_excel(writer, index=False, sheet_name='RETENSI')

    workbook = writer.book
    worksheet = writer.sheets['RETENSI']

    formats = {
        "RED FLAG": workbook.add_format({'bg_color': '#FF4C4C'}),
        "WARNING": workbook.add_format({'bg_color': '#FFC000'}),
        "ACTIVE": workbook.add_format({'bg_color': '#92D050'}),
        "WARM": workbook.add_format({'bg_color': '#F4B084'}),
        "COLD": workbook.add_format({'bg_color': '#BFBFBF'}),
        "DEAD ZONE": workbook.add_format({'bg_color': '#7F7F7F'})
    }

    for row_num, status in enumerate(df['retensi_status'], start=1):
        fmt = formats.get(status)
        if fmt:
            worksheet.set_row(row_num, None, fmt)

    writer.close()

    print("Retensi dashboard saved:")
    print(output_file)


# ===============================
# MAIN
# ===============================

if __name__ == "__main__":

    df_raw = load_raw()
    master = load_master_jabodetabek()

    df_retensi = transform_retensi(df_raw, master)
    save_output(df_retensi)

    print("Retensi Jabodetabek dashboard completed.")
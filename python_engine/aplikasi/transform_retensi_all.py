import pandas as pd
from datetime import datetime
import os
import sys
import numpy as np

from config import (
    RAW_PATH,
    PROCESSED_PATH
)

# ===============================
# LOAD LATEST RAW SNAPSHOT
# ===============================

def load_raw():

    if not os.path.exists(RAW_PATH):
        print("Raw folder not found.")
        sys.exit()

    files = [
        f for f in os.listdir(RAW_PATH)
        if f.endswith("_raw_sales.xlsx")
    ]

    if not files:
        print("No raw sales snapshot found.")
        sys.exit()

    files.sort()
    latest_file = files[-1]
    latest_path = os.path.join(RAW_PATH, latest_file)

    print("Using latest raw snapshot:")
    print(latest_path)

    df = pd.read_excel(latest_path)
    df['date_order'] = pd.to_datetime(df['date_order'])

    return df


# ===============================
# CURRENT STATUS CLASSIFICATION
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


# ===============================
# RETENSI SIGNAL BASED ON GAP
# ===============================

def classify_signal(gap):

    if pd.isna(gap):
        return "INSUFFICIENT_DATA"

    if gap >= 2:
        return "URGENT"
    elif gap >= 1:
        return "FOLLOW_UP"
    else:
        return "NORMAL"


# ===============================
# CALCULATE CLEAN AVERAGE RETENSI
# ===============================

def calculate_avg_retensi(group):

    dates = group.sort_values().values

    if len(dates) < 3:
        return np.nan

    intervals = np.diff(dates) / np.timedelta64(1, 'D')

    if len(intervals) < 2:
        return np.mean(intervals) / 7

    # IQR OUTLIER REMOVAL
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

def transform_retensi(df):

    today = datetime.today()

    # ===============================
    # LAST ORDER PER OUTLET
    # ===============================

    df_sorted = df.sort_values(
        by=['partner_id', 'date_order']
    )

    last_orders = df_sorted.groupby(
        'partner_id',
        as_index=False
    ).tail(1)

    # ===============================
    # TOTAL SALES SINCE 2024
    # ===============================

    total_sales = df.groupby(
        'partner_id',
        as_index=False
    )['amount_total'].sum()

    last_orders = last_orders.merge(
        total_sales,
        on='partner_id',
        suffixes=('', '_total')
    )

    last_orders.rename(columns={
        'date_order': 'last_order_date',
        'amount_total_total': 'total_sales_2024_plus'
    }, inplace=True)

    # ===============================
    # CURRENT AGING
    # ===============================

    last_orders['days_since'] = (
        today - last_orders['last_order_date']
    ).dt.days

    last_orders['weeks_since'] = (
        last_orders['days_since'] // 7
    ).astype(int)

    last_orders['retensi_status'] = last_orders['weeks_since'].apply(classify_retensi)

    # ===============================
    # AVERAGE RETENSI HISTORICAL
    # ===============================

    avg_retensi = df.groupby('partner_id')['date_order'].apply(
        calculate_avg_retensi
    ).reset_index()

    avg_retensi.rename(columns={
        'date_order': 'avg_retensi_weeks'
    }, inplace=True)

    last_orders = last_orders.merge(
        avg_retensi,
        on='partner_id',
        how='left'
    )

    # BULATKAN AVG
    last_orders['avg_retensi_weeks'] = last_orders['avg_retensi_weeks'].round(1)

    # ===============================
    # GAP VS AVERAGE
    # ===============================

    last_orders['gap_vs_average'] = (
        last_orders['weeks_since'] - last_orders['avg_retensi_weeks']
    )

    last_orders['gap_vs_average'] = last_orders['gap_vs_average'].round(1)

    # ===============================
    # SIGNAL
    # ===============================

    last_orders['retensi_signal'] = last_orders['gap_vs_average'].apply(classify_signal)

    # ===============================
    # SNAPSHOT DATE
    # ===============================

    snapshot_date = today.strftime("%Y-%m-%d")
    last_orders['snapshot_date'] = snapshot_date

    # SORT: paling urgent di bawah
    last_orders = last_orders.sort_values(
        by=['weeks_since'],
        ascending=True
    )

    print("Total outlet analyzed:", len(last_orders))

    return last_orders


# ===============================
# SAVE OUTPUT
# ===============================

def save_output(df):

    os.makedirs(PROCESSED_PATH, exist_ok=True)

    today_str = datetime.today().strftime("%Y-%m-%d")

    output_file = os.path.join(
        PROCESSED_PATH,
        f"{today_str}_retensi_output.xlsx"
    )

    writer = pd.ExcelWriter(output_file, engine='xlsxwriter')

    df_active = df[df['retensi_status'] == "ACTIVE"]
    df_warning = df[df['retensi_status'] == "WARNING"]
    df_redflag = df[df['retensi_status'] == "RED FLAG"]
    df_dormant = df[df['retensi_status'].isin(["WARM", "COLD", "DEAD ZONE"])]

    df_active.to_excel(writer, index=False, sheet_name='ACTIVE')
    df_warning.to_excel(writer, index=False, sheet_name='WARNING')
    df_redflag.to_excel(writer, index=False, sheet_name='RED_FLAG')
    df_dormant.to_excel(writer, index=False, sheet_name='DORMANT')

    writer.close()

    print("Retensi snapshot saved:")
    print(output_file)


# ===============================
# MAIN
# ===============================

if __name__ == "__main__":
    df_raw = load_raw()
    df_retensi = transform_retensi(df_raw)
    save_output(df_retensi)
    print("Retensi transformation completed.")

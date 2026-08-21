import subprocess
import sys
import os

# Set direktori kerja ke folder 'aplikasi' agar import config.py tidak error
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
APLIKASI_DIR = os.path.join(BASE_DIR, 'aplikasi')

print("=== [1/2] MENJALANKAN EXTRACT SALES ===")
step1 = subprocess.run([sys.executable, 'extract_sales.py'], cwd=APLIKASI_DIR)

if step1.returncode != 0:
    print("❌ ERROR: Eksekusi extract_sales.py gagal. Proses dihentikan.")
    sys.exit(1)

print("\n=== [2/2] MENJALANKAN TRANSFORM RETENSI ===")
step2 = subprocess.run([sys.executable, 'transform_retensi.py'], cwd=APLIKASI_DIR)

if step2.returncode != 0:
    print("❌ ERROR: Eksekusi transform_retensi.py gagal.")
    sys.exit(1)

print("\n✅ SUCCESS: Seluruh pipeline retensi selesai diproses!")
@echo off
cd /d C:\Mes_Dossiers\Memoire\VerifCertif_IA
call venv\Scripts\activate.bat
uvicorn main:app --host 0.0.0.0 --port 8000

import imaplib
import zipfile
import os
import time
import shutil
import email
import email.utils
import tkinter as tk
from tkinter import filedialog, messagebox
import re

# ====== FUNCIONES ======

def seleccionar_zip():
    ruta = filedialog.askopenfilename(filetypes=[("ZIP files", "*.zip")])
    if ruta:
        entry_zip.delete(0, tk.END)
        entry_zip.insert(0, ruta)

def conectar_imap(email_user, password, server):
    imap = imaplib.IMAP4_SSL(server)
    imap.login(email_user, password)
    return imap

def obtener_fecha(msg_bytes):
    try:
        parsed = email.message_from_bytes(msg_bytes)
        date_header = parsed.get("Date")
        if date_header:
            fecha = email.utils.parsedate(date_header)
            if fecha:
                return imaplib.Time2Internaldate(time.mktime(fecha))
    except:
        pass
    return imaplib.Time2Internaldate(time.time())

def asegurar_subject(msg_bytes):
    if b"Subject:" not in msg_bytes:
        return b"Subject: (Sin asunto)\n" + msg_bytes
    return msg_bytes

def subir_correos():
    zip_path = entry_zip.get()
    email_user = entry_email.get()
    password = entry_password.get()
    imap_server = entry_imap.get()

    if not zip_path or not email_user or not password or not imap_server:
        messagebox.showerror("Error", "Completa todos los campos")
        return

    extract_folder = "temp_restore"

    try:
        status_label.config(text="Conectando al servidor...")
        root.update()
        imap = conectar_imap(email_user, password, imap_server)

        status_label.config(text="Extrayendo archivos...")
        root.update()
        if os.path.exists(extract_folder):
            shutil.rmtree(extract_folder)
        
        with zipfile.ZipFile(zip_path, 'r') as zip_ref:
            zip_ref.extractall(extract_folder)

        # Listar todos los archivos .eml y su estructura
        archivos_eml = []
        for root_dir, _, files in os.walk(extract_folder):
            for file in files:
                if file.endswith(".eml"):
                    archivos_eml.append(os.path.join(root_dir, file))

        total = len(archivos_eml)
        if total == 0:
            messagebox.showerror("Error", "No se encontraron correos en el ZIP")
            return

        subidos = 0
        carpetas_creadas = set()

        for path in archivos_eml:
            try:
                # 1. Determinar la carpeta destino basada en la ruta del archivo
                # Ejemplo: temp_restore/[Gmail]/Enviados/msg_1.eml -> [Gmail]/Enviados
                rel_path = os.path.relpath(os.path.dirname(path), extract_folder)
                
                # Si el archivo está en la raíz del ZIP, rel_path será "."
                target_folder = "INBOX" if rel_path == "." else rel_path.replace(os.sep, '/')

                # 2. Crear la carpeta en el IMAP si no la hemos verificado en esta sesión
                if target_folder not in carpetas_creadas:
                    imap.create(f'"{target_folder}"')
                    carpetas_creadas.add(target_folder)

                # 3. Leer y preparar el correo
                with open(path, "rb") as f:
                    msg_content = f.read()
                
                msg_content = asegurar_subject(msg_content)
                internal_date = obtener_fecha(msg_content)

                # 4. Subir (Append)
                # Usamos f'"{target_folder}"' para manejar nombres con espacios o corchetes
                imap.append(f'"{target_folder}"', "\\Seen", internal_date, msg_content)

                subidos += 1
                progreso = int((subidos / total) * 100)
                progress_var.set(progreso)
                status_label.config(text=f"Subiendo a {target_folder}: {subidos}/{total}")
                root.update()

            except Exception as e_file:
                print(f"Error subiendo {path}: {e_file}")

        imap.logout()
        shutil.rmtree(extract_folder)
        messagebox.showinfo("Éxito", f"Se han restaurado {subidos} correos respetando la estructura original.")

    except Exception as e:
        messagebox.showerror("Error Crítico", str(e))

# ====== INTERFAZ ======

root = tk.Tk()
root.title("Restaurador de Estructura IMAP")
root.geometry("500x450")

main_frame = tk.Frame(root, padx=20, pady=20)
main_frame.pack(expand=True, fill="both")

tk.Label(main_frame, text="Archivo ZIP de Backup:").pack(anchor="w")
entry_zip = tk.Entry(main_frame, width=50)
entry_zip.pack(fill="x", pady=2)
tk.Button(main_frame, text="Seleccionar archivo...", command=seleccionar_zip).pack(anchor="e", pady=2)

tk.Label(main_frame, text="Correo Destino:").pack(anchor="w", pady=(10,0))
entry_email = tk.Entry(main_frame, width=50)
entry_email.pack(fill="x", pady=2)

tk.Label(main_frame, text="Contraseña (App Password):").pack(anchor="w")
entry_password = tk.Entry(main_frame, show="*", width=50)
entry_password.pack(fill="x", pady=2)

tk.Label(main_frame, text="Servidor IMAP (ej: imap.gmail.com):").pack(anchor="w")
entry_imap = tk.Entry(main_frame, width=50)
entry_imap.pack(fill="x", pady=2)
entry_imap.insert(0, "imap.gmail.com")

tk.Button(main_frame, text="RESTAURAR ESTRUCTURA EN SERVIDOR", command=subir_correos, bg="#28a745", fg="white", font=("Arial", 10, "bold"), height=2).pack(fill="x", pady=20)

progress_var = tk.DoubleVar()
progress = tk.Scale(main_frame, variable=progress_var, orient="horizontal", from_=0, to=100, state="disabled")
progress.pack(fill="x")

status_label = tk.Label(main_frame, text="Esperando inicio...", fg="grey")
status_label.pack(pady=5)

root.mainloop()
import imaplib
import email
import os
import time
import zipfile
import tkinter as tk
from tkinter import filedialog, messagebox
import shutil
import re

# ====== FUNCIONES ======

def seleccionar_carpeta():
    ruta = filedialog.askdirectory()
    if ruta:
        entry_destino.delete(0, tk.END)
        entry_destino.insert(0, ruta)

def conectar_imap(email_user, password, server):
    imap = imaplib.IMAP4_SSL(server)
    imap.login(email_user, password)
    return imap

def asegurar_subject(msg_bytes):
    parsed = email.message_from_bytes(msg_bytes)
    if not parsed.get("Subject"):
        return b"Subject: (Sin asunto)\n" + msg_bytes
    return msg_bytes

def descargar_todo():
    destino = entry_destino.get()
    email_user = entry_email.get()
    password = entry_password.get()
    imap_server = entry_imap.get()
    limite_str = entry_limite.get().strip()

    try:
        max_correos = int(limite_str) if limite_str else None
    except ValueError:
        messagebox.showerror("Error", "El límite debe ser un número entero")
        return

    if not destino or not email_user or not password or not imap_server:
        messagebox.showerror("Error", "Completa todos los campos")
        return

    try:
        status_label.config(text="Conectando a IMAP...")
        root.update()

        imap = conectar_imap(email_user, password, imap_server)

        # Obtener lista de carpetas
        typ, folders_raw = imap.list()
        carpetas = []
        for f in folders_raw:
            # Parseo robusto del nombre de la carpeta
            match = re.search(r'\((?P<flags>.*?)\)\s+"(?P<delimiter>.*?)"\s+"?(?P<name>.*?)"?$', f.decode())
            if match:
                carpetas.append(match.group('name').strip('"'))

        total_descargados = 0
        root_folder_name = f"{email_user.replace('@', '_')}_{time.strftime('%Y-%m-%d_%H-%M-%S')}"
        temp_folder = os.path.join(destino, root_folder_name)
        os.makedirs(temp_folder, exist_ok=True)

        for folder in carpetas:
            if max_correos is not None and total_descargados >= max_correos:
                break

            status_label.config(text=f"Abriendo: {folder}")
            root.update()

            # Seleccionamos la carpeta. Si falla, pasamos a la siguiente.
            res, _ = imap.select(f'"{folder}"', readonly=True)
            if res != 'OK':
                continue

            typ, data = imap.search(None, "ALL")
            mail_ids = data[0].split()

            # Crear subcarpetas (ej: [Gmail]/Enviados)
            sub_path = folder.replace('/', os.sep).replace('\\', os.sep)
            folder_path = os.path.join(temp_folder, sub_path)
            os.makedirs(folder_path, exist_ok=True)

            for i, num in enumerate(mail_ids, start=1):
                if max_correos is not None and total_descargados >= max_correos:
                    break

                try:
                    # Intentar descargar el correo
                    typ, data = imap.fetch(num, "(RFC822)")
                    if typ != 'OK':
                        print(f"Error al obtener msg ID {num} en {folder}")
                        continue
                    
                    msg_bytes = data[0][1]
                    msg_bytes = asegurar_subject(msg_bytes)

                    filename = f"msg_{i}.eml"
                    with open(os.path.join(folder_path, filename), "wb") as f:
                        f.write(msg_bytes)

                    total_descargados += 1
                    if total_descargados % 10 == 0: # Actualizar UI cada 10 para no saturar
                        status_label.config(text=f"Descargados: {total_descargados} correos...")
                        root.update()
                        
                except Exception as e_mail:
                    # SI UN CORREO FALLA, EL PROGRAMA SIGUE CON EL SIGUIENTE
                    print(f"Saltando correo por error: {e_mail}")
                    continue

        # Crear el ZIP
        if total_descargados > 0:
            zip_name = os.path.join(destino, root_folder_name + ".zip")
            status_label.config(text="Comprimiendo backup...")
            root.update()
            
            with zipfile.ZipFile(zip_name, "w", zipfile.ZIP_DEFLATED) as zipf:
                for root_dir, _, files in os.walk(temp_folder):
                    for file in files:
                        file_path = os.path.join(root_dir, file)
                        arcname = os.path.relpath(file_path, temp_folder)
                        zipf.write(file_path, arcname)
            
            shutil.rmtree(temp_folder)
            imap.logout()
            messagebox.showinfo("Hecho", f"Backup finalizado.\nTotal: {total_descargados} correos.")
        else:
            if os.path.exists(temp_folder): shutil.rmtree(temp_folder)
            messagebox.showwarning("Aviso", "No se descargó ningún correo.")

    except Exception as e:
        messagebox.showerror("Error General", f"Se detuvo la conexión: {str(e)}")

# ====== INTERFAZ ======

root = tk.Tk()
root.title("Email Backup Tool")
root.geometry("450x500")

main_frame = tk.Frame(root, padx=20, pady=20)
main_frame.pack(expand=True, fill="both")

tk.Label(main_frame, text="Carpeta de destino:").pack(anchor="w")
entry_destino = tk.Entry(main_frame, width=50)
entry_destino.pack(fill="x", pady=2)
tk.Button(main_frame, text="Seleccionar", command=seleccionar_carpeta).pack(anchor="e", pady=2)

tk.Label(main_frame, text="Email:").pack(anchor="w")
entry_email = tk.Entry(main_frame, width=50)
entry_email.pack(fill="x", pady=2)

tk.Label(main_frame, text="Contraseña (App Password):").pack(anchor="w")
entry_password = tk.Entry(main_frame, show="*", width=50)
entry_password.pack(fill="x", pady=2)

tk.Label(main_frame, text="Servidor IMAP:").pack(anchor="w")
entry_imap = tk.Entry(main_frame, width=50)
entry_imap.pack(fill="x", pady=2)
entry_imap.insert(0, "imap.gmail.com")

tk.Label(main_frame, text="Cant. correos (vacío = todos):", fg="blue").pack(anchor="w", pady=(10, 0))
entry_limite = tk.Entry(main_frame, width=15)
entry_limite.pack(anchor="w", pady=2)

tk.Button(main_frame, text="INICIAR DESCARGA", command=descargar_todo, bg="#007bff", fg="white", font=("Arial", 10, "bold"), height=2).pack(fill="x", pady=20)

status_label = tk.Label(main_frame, text="Listo", fg="grey")
status_label.pack()

root.mainloop()
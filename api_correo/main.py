from fastapi import FastAPI, Form, File, UploadFile, HTTPException
from fastapi.middleware.cors import CORSMiddleware
import smtplib
from email.message import EmailMessage

app = FastAPI()

# Configurar CORS para permitir comunicación con PHP
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

@app.post("/enviar-factura-correo/")
async def enviar_factura_correo(
    correo: str = Form(...),
    nombre_cliente: str = Form(...),
    codigo_pedido: str = Form(...),
    pdf_file: UploadFile = File(...)
):
    try:
        # Credenciales de correo (Ejemplo usando Gmail con Contraseña de Aplicación)
        remitente = "dysale99@gmail.com"
        password = "hksc cpze fsmj kgtp"
        
        msg = EmailMessage()
        msg['Subject'] = f"Factura de Compra - Pedido #{codigo_pedido}"
        msg['From'] = remitente
        msg['To'] = correo
        
        msg.set_content(f"Hola {nombre_cliente},\n\nAdjunto encontrarás la factura oficial correspondiente a tu pedido con código {codigo_pedido}.\n\nGracias por tu preferencia en E-Commerce M.A.")
        
        # Leer el contenido binario del PDF recibido desde PHP
        pdf_content = await pdf_file.read()
        msg.add_attachment(
            pdf_content,
            maintype='application',
            subtype='pdf',
            filename=f"factura_{codigo_pedido}.pdf"
        )
        
        # Conexión segura con el servidor SMTP de Gmail
        with smtplib.SMTP_SSL('smtp.gmail.com', 465) as smtp:
            smtp.login(remitente, password)
            smtp.send_message(msg)
            
        return {"status": "success", "message": "Correo enviado exitosamente"}
    
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))
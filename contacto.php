<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Contacto - Comidas Típicas</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="styles.css" />
  </head>
  <body>
    <main class="page">
      <nav class="top-nav" style="margin-bottom: 24px;">
        <a href="index.html">Inicio</a>
        <a href="menu.html">Menú</a>
        <a href="contacto.html" class="active">Contacto</a>
      </nav>

      <section class="page-intro">
        <h2>Contáctanos</h2>
        <p>Comparte tus comentarios, reserva una experiencia o solicita información sobre nuestros platos.</p>
      </section>

      <section class="contact-card">
        <article class="card">
          <h3>Escríbenos</h3>
          <p>Estamos listos para responder tus preguntas y ayudarte a descubrir nuevas ideas.</p>
          <p><strong>Email:</strong> contacto@comidastipicas.com</p>
          <p><strong>Teléfono:</strong> +51 999 888 777</p>
        </article>

        <article class="card">
          <div id="formMessage" style="margin-bottom: 18px; padding: 12px; border-radius: 10px; display: none;"></div>

          <label for="name">Nombre</label>
          <input id="name" name="nombre" type="text" required placeholder="Tu nombre" />

          <label for="email">Correo</label>
          <input id="email" name="correo" type="email" required placeholder="tu@email.com" />

          <label for="message">Mensaje</label>
          <textarea id="message" name="mensaje" rows="4" required placeholder="Cuéntanos qué te gustaría probar..."></textarea>

          <button id="sendBtn">Enviar</button>
        </article>
      </section>
    </main>

    <script>
      const API_BASE = 'http://localhost:3000';
      document.getElementById('sendBtn').addEventListener('click', async (e) => {
        e.preventDefault();
        const nombre = document.getElementById('name').value.trim();
        const correo = document.getElementById('email').value.trim();
        const mensaje = document.getElementById('message').value.trim();
        const msgEl = document.getElementById('formMessage');

        if (!nombre || !correo || !mensaje) {
          msgEl.style.display = 'block';
          msgEl.style.background = '#fee2e2';
          msgEl.textContent = 'Completa todos los campos.';
          return;
        }

        try {
          const res = await fetch(API_BASE + '/contactos', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ nombre, correo, mensaje })
          });
          const data = await res.json();
          if (res.ok) {
            msgEl.style.display = 'block';
            msgEl.style.background = '#ecfdf5';
            msgEl.textContent = 'Mensaje enviado correctamente. Gracias por contactarnos.';
            document.getElementById('name').value = '';
            document.getElementById('email').value = '';
            document.getElementById('message').value = '';
          } else {
            msgEl.style.display = 'block';
            msgEl.style.background = '#fee2e2';
            msgEl.textContent = data.message || 'Ocurrió un error al enviar tu mensaje.';
          }
        } catch (err) {
          msgEl.style.display = 'block';
          msgEl.style.background = '#fee2e2';
          msgEl.textContent = 'Error de red. Intenta de nuevo.';
        }
      });
    </script>
  </body>
</html>

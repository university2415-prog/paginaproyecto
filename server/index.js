const express = require('express');
const cors = require('cors');
const dotenv = require('dotenv');
const supabase = require('./lib/supabaseClient');

dotenv.config({ path: '../.env' });

const app = express();
app.use(cors());
app.use(express.json());

app.get('/productos', async (req, res) => {
  try {
    const { data, error, status } = await supabase.from('productos').select('*');
    if (error) return res.status(status || 500).json({ error: error.message });
    res.json(data);
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.post('/contactos', async (req, res) => {
  try {
    const payload = req.body;
    const { data, error } = await supabase.from('contactos').insert(payload).select();
    if (error) return res.status(400).json({ error: error.message });
    res.json(data[0]);
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.post('/pedidos', async (req, res) => {
  try {
    const { usuario_id, subtotal, impuestos, total, items } = req.body;
    // Insert pedido
    const { data: pedidoData, error: pedidoError } = await supabase.from('pedidos').insert({ usuario_id, subtotal, impuestos, total }).select();
    if (pedidoError) return res.status(400).json({ error: pedidoError.message });
    const pedido = pedidoData[0];

    // Insert detalle rows
    if (Array.isArray(items) && items.length) {
      const detalleRows = items.map(it => ({
        pedido_id: pedido.id,
        producto_id: it.producto_id,
        cantidad: it.cantidad || 1,
        precio_unitario: it.precio_unitario || 0
      }));
      const { data: detalles, error: detalleError } = await supabase.from('pedido_detalle').insert(detalleRows).select();
      if (detalleError) return res.status(400).json({ error: detalleError.message });
      pedido.detalles = detalles;
    }

    res.json(pedido);
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

// Registro de usuario (almacena hash de contraseña en usuarios)
const bcrypt = require('bcrypt');

app.post('/registro', async (req, res) => {
  try {
    const { nombre, primer_apellido, segundo_apellido, correo, password, pais } = req.body;
    if (!nombre || !primer_apellido || !correo || !password || !pais) {
      return res.status(400).json({ success: false, message: 'Faltan campos obligatorios.' });
    }

    // comprobar si existe
    const { data: existing, error: selErr } = await supabase.from('usuarios').select('id').or(`correo.eq.${correo}`).limit(1);
    if (selErr) return res.status(500).json({ success: false, message: selErr.message });
    if (existing && existing.length) {
      return res.status(400).json({ success: false, message: 'Este correo ya está registrado.' });
    }

    const hash = await bcrypt.hash(password, 10);
    const { data, error } = await supabase.from('usuarios').insert([{ nombre, primer_apellido, segundo_apellido, correo, contrasena: hash, pais }]).select();
    if (error) return res.status(500).json({ success: false, message: error.message });

    // también insertar en contactos tabla (opcional)
    await supabase.from('contactos').insert([{ nombre, primer_apellido, segundo_apellido, correo, contrasena: hash, pais }]);

    res.json({ success: true, message: 'Registro exitoso.', usuario: data[0] });
  } catch (err) {
    res.status(500).json({ success: false, message: err.message });
  }
});

// Login simple que valida contra la tabla `usuarios`
app.post('/login', async (req, res) => {
  try {
    const { user, password } = req.body;
    if (!user || !password) return res.status(400).json({ success: false, message: 'Usuario y contraseña son requeridos.' });

    const { data, error } = await supabase.from('usuarios').select('id, nombre, correo, contrasena').or(`correo.eq.${user},nombre.eq.${user}`).limit(1);
    if (error) return res.status(500).json({ success: false, message: error.message });
    if (!data || data.length === 0) return res.status(400).json({ success: false, message: 'Usuario o contraseña incorrectos.' });

    const u = data[0];
    const match = await bcrypt.compare(password, u.contrasena);
    if (!match) return res.status(400).json({ success: false, message: 'Usuario o contraseña incorrectos.' });

    res.json({ success: true, usuario: { id: u.id, nombre: u.nombre, correo: u.correo } });
  } catch (err) {
    res.status(500).json({ success: false, message: err.message });
  }
});

app.get('/pedidos', async (req, res) => {
  try {
    const { data, error } = await supabase.from('pedidos').select('*, pedido_detalle(*)');
    if (error) return res.status(500).json({ error: error.message });
    res.json(data);
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => console.log(`Server listening on ${PORT}`));

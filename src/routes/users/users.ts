// users.js (el archivo de rutas)
import express from 'express';
import { GetUsers, addUser } from './instaceUsers';
const router = express.Router();

router.get('/', async(_req, res) => {
    const data = await GetUsers()
    res.send({  data });
});

router.post('/', async (req, res) => {
    try {
        console.log('Datos recibidos:', req.body);
        await addUser(req.body);
        res.status(201).send({ message: 'Usuario agregado correctamente' });
    } catch (error) {
        console.error('Error al procesar la solicitud:', error);
        res.status(500).send({ error: 'Error en el servidor' });
    }
});

export default router;

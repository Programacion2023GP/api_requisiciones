// users.js (el archivo de rutas)
import express from 'express';
import { GetDepartamentos } from './instaceDepartamentos';
const router = express.Router();

router.get('/', async(_req, res) => {
    const data = await GetDepartamentos()
    res.send({  data });
});

// router.post('/', async(_req, res) => {
//     await addUser()
//     res.send({ message: 'Welcome to the API!' });
// });

export default router;

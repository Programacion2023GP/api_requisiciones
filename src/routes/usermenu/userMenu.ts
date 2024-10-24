// users.js (el archivo de rutas)
import express from "express";
import { GetUserMenu} from "./instaceUserMenu";
const router = express.Router();

router.get("/:id", async (_req, res) => {
    const { id } = _req.params; // Obtiene el parámetro 'id' de la URL
 console.log("here", id);
  const data = await GetUserMenu(parseInt(id));
  res.send({ data });
});

// router.post('/', async (req, res) => {
//     try {
//         console.log('Datos recibidos:', req.body);
//         const user = await addStatus(req.body);
//         res.status(201).send({ 
//             title: "Éxito",
//             message: 'Status agregado correctamente',
//             status: 200,
//             data: user 
//         });
//     } catch (error) {
//         console.error('Error al procesar la solicitud:', error);

//         // Aserción de tipo para el error
//         const errorMessage = (error as Error).message || 'Ocurrió un error al procesar la solicitud';
        
//         res.status(400).send({ 
//             title: "Error",
//             message: errorMessage,
//             status: 400
//         });
//     }
// });

// router.delete("/:id", async (req, res) => {
//   const { id } = req.params; // Obtiene el parámetro 'id' de la URL

//   try {
//     DeleteStatus(id);
//     // Aquí podrías ejecutar la lógica para eliminar el recurso con el id
//     // Por ejemplo, si estás eliminando un registro de una base de datos:
//     // await deleteResourceById(id);

//     res
//       .status(201)
//       .send({
//         title: "Exito",
//         message: "Status eleminado correctamente",
//         status: 200,
//       });
//   } catch (error) {
//     res.status(500).json({ message: "Error eliminando el recurso" });
//   }
// });
// router.put("/:id", async (req, res) => {
//     const { id } = req.params; // Obtiene el parámetro 'id' de la URL
//     const userData = req.body; // Obtiene los nuevos datos del usuario
//     try {
//        const user = updateStatus(id, userData);
//         res.status(200).send({
//             title: "Éxito",
//             message: "Status actualizado correctamente",
//             status: 200,
//             data: user,
//         });
//     } catch (error) {
//         const errorMessage = (error as Error).message || 'Ocurrió un error al procesar la solicitud';

//         res.status(200).send({
//             title: "Éxito",
//             message: errorMessage,
//             status: 200,
//         });
//     }
   
//   });
export default router;

import express from "express";
import cors from "cors"; // Importa el middleware cors
import UserRoutes from './routes/users/users';
import DepartamentosRoutes from './routes/departamentos/departamentos';
import sequelize from './db'; 

const app = express();
const Port = 8080;

// Usa el middleware cors
app.use(cors()); // Esto habilitará CORS para todas las rutas

app.use(express.json());

app.get("/ping", (_req, res) => {
    try {
        console.log("saludos!");
        res.send("pong!");
    } catch (error) {
        console.log("🚀 ~ app.get ~ error:", error);
    }
});

app.use('/api/users', UserRoutes);
app.use('/api/departamentos', DepartamentosRoutes);

app.listen(Port, async () => {
    console.log(`Server is running on port ${Port}`);
    await sequelize.sync(); // Sincroniza tus modelos con la base de datos
});

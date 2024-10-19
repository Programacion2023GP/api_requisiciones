import express from "express";
import UserRoutes from './routes/users/users';
import sequelize from './db'; 
// import cors from 'cors';
const app = express();
const Port = 8080;

app.use(express.json());
// app.use(cors());

app.get("/ping", (_req, res) => {
  try {
    console.log("saludos!");
    res.send("pong!");
  } catch (error) {
    console.log("🚀 ~ app.get ~ error:", error);
  }
});
app.use('/api/users',UserRoutes)
app.listen(Port, async () => {
    console.log(`Server is running on port ${Port}`);
    await sequelize.sync(); // Sincroniza tus modelos con la base de datos
});

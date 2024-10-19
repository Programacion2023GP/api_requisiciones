// src/db.ts
import { Sequelize } from 'sequelize';

const sequelize = new Sequelize('requisiciones', 'root', '', {
    host: 'localhost',
    port: 3306, // Cambia este número si usas un puerto diferente
    dialect: 'mysql',
});


// src/db.ts
const testConnection = async () => {
    try {
        await sequelize.authenticate();

        console.log('Connection to MySQL has been established successfully.');

        // await sequelize.sync({ force: true }); // Esto crea la tabla
        console.log('All models were synchronized successfully.');
    } catch (error) {
        console.error('Unable to connect to the database:', error);
    }
};


testConnection();

export default sequelize;

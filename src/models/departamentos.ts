// src/models/user.ts
import { Model, DataTypes, Optional } from 'sequelize';
import sequelize from '../db'; 

// Definir los atributos del modelo
interface UserAttributes {
    id?: number;
    Departamento: string;
    Dependencia: string;

}

// Opciones para crear un nuevo usuario (al crear, el ID es opcional)
interface UserCreationAttributes extends Optional<UserAttributes, 'id'> {}

// Definir la clase del modelo con los tipos
class Departamentos extends Model<UserAttributes, UserCreationAttributes> implements UserAttributes {
    public id!: number;
    public Departamento!: string;
    public Dependencia!: string;
   
}

Departamentos.init(
    {
        id: {
            type: DataTypes.INTEGER.UNSIGNED,
            autoIncrement: true,
            primaryKey: true,
        },
        Departamento: {
            type: DataTypes.STRING,
            allowNull: false,
        },
        Dependencia: {
            type: DataTypes.STRING,
            allowNull: false,
        },
       
    },
    {
        sequelize,
        modelName: 'Departamentos',
        tableName: 'departamentos',
        timestamps: true,
    }
);

export default Departamentos;

// src/models/user.ts
import { Model, DataTypes, Optional } from 'sequelize';
import sequelize from '../db'; 
import User from './user';

// Definir los atributos del modelo
interface UserAttributes {
    id?: number;
    group: string;
    dependence: string;

}

// Opciones para crear un nuevo usuario (al crear, el ID es opcional)
interface UserCreationAttributes extends Optional<UserAttributes, 'id'> {}

// Definir la clase del modelo con los tipos
class Departamentos extends Model<UserAttributes, UserCreationAttributes> implements UserAttributes {
    public id!: number;
    public group!: string;
    public dependence!: string;
   
}

Departamentos.init(
    {
        id: {
            type: DataTypes.INTEGER.UNSIGNED,
            autoIncrement: true,
            primaryKey: true,
        },
        group: {
            type: DataTypes.STRING,
            allowNull: false,
        },
        dependence: {
            type: DataTypes.STRING,
            allowNull: false,
        },
       
    },
    {
        sequelize,
        modelName: 'Departamentos',
        tableName: 'groups',
        timestamps: true,
    }
);
// Departamentos.hasMany(User, {
//     foreignKey: 'id_group', // clave foránea en User
//     sourceKey: 'id', // clave primaria en Departamentos
//     as: 'usuarios' // opcional, un alias para la relación
// });
export default Departamentos;

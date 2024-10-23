// src/models/user.ts
import { Model, DataTypes, Optional } from 'sequelize';
import sequelize from '../db'; 

// Definir los atributos del modelo
interface UserAttributes {
    id?: number;
    name: string;
    active: boolean;

}

// Opciones para crear un nuevo usuario (al crear, el ID es opcional)
interface UserCreationAttributes extends Optional<UserAttributes, 'id'> {}

// Definir la clase del modelo con los tipos
class Status extends Model<UserAttributes, UserCreationAttributes> implements UserAttributes {
    public id!: number;
    public name!: string;
    public active!: boolean;
   
}

Status.init(
    {
        id: {
            type: DataTypes.INTEGER.UNSIGNED,
            autoIncrement: true,
            primaryKey: true,
        },
        name: {
            type: DataTypes.STRING,
            allowNull: false,
        },
        active: {
            type: DataTypes.BOOLEAN,
            allowNull: true,
        },
       
    },
    {
        sequelize,
        modelName: 'Status',
        tableName: 'status',
        timestamps: true,
    }
);
// Departamentos.hasMany(User, {
//     foreignKey: 'id_group', // clave foránea en User
//     sourceKey: 'id', // clave primaria en Departamentos
//     as: 'usuarios' // opcional, un alias para la relación
// });
export default Status;

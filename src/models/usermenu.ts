// src/models/user.ts
import { Model, DataTypes, Optional } from 'sequelize';
import sequelize from '../db'; 

// Definir los atributos del modelo
interface UserAttributes {
    id?: number;
    id_user: number;
    id_menu: number;
    read: boolean;
    write: boolean;
    edit: boolean;
    delete: boolean;

}

// Opciones para crear un nuevo usuario (al crear, el ID es opcional)
interface UserCreationAttributes extends Optional<UserAttributes, 'id'> {}

// Definir la clase del modelo con los tipos
class UserMenu extends Model<UserAttributes, UserCreationAttributes> implements UserAttributes {
    public id!: number;
    public id_user!: number;
    public id_menu!: number;
    public read!: boolean;
    public write!: boolean;
    public edit!: boolean;
    public delete!: boolean;
}

UserMenu.init(
    {
        id: {
            type: DataTypes.INTEGER.UNSIGNED,
            autoIncrement: true,
            primaryKey: true,
        },
        id_user: {
            type: DataTypes.INTEGER,
            allowNull: false,
        },
        id_menu: {
            type: DataTypes.INTEGER,
            allowNull: false,
        },
        read: {
            type: DataTypes.BOOLEAN,
            defaultValue: false,
          },
          write: { type: DataTypes.BOOLEAN, defaultValue: false },
          edit: { type: DataTypes.BOOLEAN, defaultValue: false },
          delete: { type: DataTypes.BOOLEAN, defaultValue: false },
       
    },
    {
        sequelize,
        modelName: 'UserMenu',
        tableName: 'usermenus',
        timestamps: true,
    }
);
// Departamentos.hasMany(User, {
//     foreignKey: 'id_group', // clave foránea en User
//     sourceKey: 'id', // clave primaria en Departamentos
//     as: 'usuarios' // opcional, un alias para la relación
// });
export default UserMenu;

// src/models/user.ts
import { Model, DataTypes, Optional } from 'sequelize';
import sequelize from '../db'; 
import bcrypt from 'bcrypt';
import Departamentos from './departamentos';

// Definir los atributos del modelo
interface UserAttributes {
    id?: number;
    name: string;
    paternalname: string;
    maternalname?: string | null;  // MaternalName puede ser string o null
    email: string;
    password: string;  // Cambiado a requerido
    id_group: number; // Asegúrate de que sea un número
    active:boolean;
}

// Opciones para crear un nuevo usuario (al crear, el ID es opcional)
interface UserCreationAttributes extends Optional<UserAttributes, 'id'> {}

// Definir la clase del modelo con los tipos
class User extends Model<UserAttributes, UserCreationAttributes> implements UserAttributes {
    public id!: number;
    public name!: string;
    public paternalname!: string;
    public maternalname!: string | null;  // Definirla como string | null
    public email!: string;
    public password!: string; // Cambiado a requerido
    public id_group!: number; // Corregido a number
    public active!:boolean;
    // Método para verificar la contraseña
    public validatePassword(password: string): boolean {
        return bcrypt.compareSync(password, this.password);
    }
}

User.init(
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
        paternalname: {
            type: DataTypes.STRING,
            allowNull: false,
        },
        maternalname: {
            type: DataTypes.STRING,
            allowNull: true, // Puede ser null
        },
        email: {
            type: DataTypes.STRING,
            allowNull: false,
            unique: true,
        },
        password: {
            type: DataTypes.STRING,
            allowNull: false,
            defaultValue: "123456" // Esto puede ser reemplazado por un hash
        },
        id_group: {
            type: DataTypes.INTEGER, // Cambiado a INTEGER
            allowNull: false,
        },
        active:{
            type: DataTypes.BOOLEAN,
            defaultValue: true,  // Por defecto, todos los usuarios son activos
            allowNull: false,  // No puede ser null
        }
    },
    {
        sequelize,
        modelName: 'User',
        tableName: 'users',
        timestamps: true,
        hooks: {
            beforeSave: async (user: User) => {
                if (user.changed('password')) {
                    // Hashea la contraseña antes de guardar
                    const salt = await bcrypt.genSalt(10);
                    user.password = await bcrypt.hash(user.password, salt);
                }
            },
        },
    }
);
User.belongsTo(Departamentos, {
    foreignKey: 'id_group', // clave foránea en User
    targetKey: 'id', // clave primaria en Departamentos
    as: 'departamento' // opcional, un alias para la relación
});
export default User;
